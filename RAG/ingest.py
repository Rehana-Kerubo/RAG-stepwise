"""
ingest.py

Reads onboarding documents from sample_docs/, splits them into chunks,
embeds each chunk, and stores them in a local ChromaDB collection.

Documents are grouped by gig: the name of the folder a file sits in becomes
its "gig" tag (for example sample_docs/airtel-line-registration/xyz.md is
tagged "airtel-line-registration"). Files placed directly in sample_docs/
are tagged "general" and are available to every gig.

Run this once to build the vector store, and again any time the
onboarding docs change.
    python ingest.py           # download from Google Drive, then ingest
    python ingest.py --local   # skip the download, ingest what is already in sample_docs/
"""
import os
import re
import sys

import chromadb
import gdown
from sentence_transformers import SentenceTransformer

DRIVE_FOLDER_ID = "1WXMzsx8tAeVNyPMJunEV6yrtL3VuPzP5"
DOCS_FOLDER = "sample_docs"
DB_FOLDER = "chroma_db"
COLLECTION_NAME = "onboarding_docs"
SHARED_GIG = "general"


def download_from_drive():
    url = f"https://drive.google.com/drive/folders/{DRIVE_FOLDER_ID}"
    gdown.download_folder(url, output=DOCS_FOLDER, quiet=False)


def chunk_text(text, max_length=500):
    paragraphs = text.split("\n\n")
    chunks = []
    current_chunk = ""
    for paragraph in paragraphs:
        if len(current_chunk) + len(paragraph) + 2 <= max_length:
            if current_chunk:
                current_chunk += "\n\n" + paragraph
            else:
                current_chunk = paragraph
        else:
            if current_chunk:
                chunks.append(current_chunk)
            current_chunk = paragraph
            # If the paragraph itself is too large, split it further
            if len(current_chunk) > max_length:
                oversized_chunks = split_oversized_paragraph(current_chunk, max_length)
                chunks.extend(oversized_chunks)
                current_chunk = ""
    if current_chunk:
        chunks.append(current_chunk)
    return chunks


def split_oversized_paragraph(paragraph, max_length=500):
    """Split a single paragraph into smaller chunks if it exceeds max_length."""
    sentences = paragraph.split(". ")
    chunks = []
    current_chunk = ""
    for sentence in sentences:
        if len(current_chunk) + len(sentence) + 2 <= max_length:
            if current_chunk:
                current_chunk += ". " + sentence
            else:
                current_chunk = sentence
        else:
            if current_chunk:
                chunks.append(current_chunk)
            current_chunk = sentence
    if current_chunk:
        chunks.append(current_chunk)
    return chunks


def slugify(name):
    """'Airtel Line Registration' -> 'airtel-line-registration'"""
    return re.sub(r"[^a-z0-9]+", "-", name.lower()).strip("-")


def load_documents(folder):
    """
    Read every .md file under the folder, including subfolders.
    Returns a list of (relative_path, filename, gig, text).
    """
    docs = []
    for root, _dirs, files in os.walk(folder):
        for filename in sorted(files):
            if not filename.endswith(".md"):
                continue

            path = os.path.join(root, filename)
            rel_path = os.path.relpath(path, folder).replace(os.sep, "/")

            # The folder the file sits in is its gig. Files directly in the
            # docs folder are shared by every gig.
            if os.path.abspath(root) == os.path.abspath(folder):
                gig = SHARED_GIG
            else:
                gig = slugify(os.path.basename(root))

            with open(path, "r", encoding="utf-8") as f:
                docs.append((rel_path, filename, gig, f.read()))
    return docs


def main():
    if "--local" not in sys.argv:
        download_from_drive()

    print("Loading embedding model...")
    model = SentenceTransformer("all-MiniLM-L6-v2")

    print(f"Loading documents from {DOCS_FOLDER}/...")
    docs = load_documents(DOCS_FOLDER)

    if not docs:
        print(f"No .md files found under {DOCS_FOLDER}/. Nothing to ingest.")
        return

    print(f"Found {len(docs)} documents.")

    all_chunks = []
    all_ids = []
    all_metadata = []
    per_gig = {}

    for rel_path, filename, gig, text in docs:
        chunks = chunk_text(text)
        per_gig[gig] = per_gig.get(gig, 0) + len(chunks)
        for i, chunk in enumerate(chunks):
            all_chunks.append(chunk)
            # The path (not just the filename) keeps ids unique when two gigs
            # have a file with the same name.
            all_ids.append(f"{rel_path}-{i}")
            all_metadata.append({"source": filename, "gig": gig})

    print("Chunks per gig:")
    for gig, count in sorted(per_gig.items()):
        print(f"  {gig}: {count}")

    print(f"Split into {len(all_chunks)} chunks. Embedding...")
    embeddings = model.encode(all_chunks).tolist()

    print("Setting up ChromaDB...")
    client = chromadb.PersistentClient(path=DB_FOLDER)
    # Wipe and recreate the collection each run, so ingest.py is idempotent
    try:
        client.delete_collection(COLLECTION_NAME)
    except Exception:
        pass
    collection = client.create_collection(COLLECTION_NAME)

    print("Storing in ChromaDB...")
    collection.add(
        ids=all_ids,
        embeddings=embeddings,
        documents=all_chunks,
        metadatas=all_metadata,
    )

    print(f"Done. {len(all_chunks)} chunks stored in '{COLLECTION_NAME}'.")


if __name__ == "__main__":
    main()