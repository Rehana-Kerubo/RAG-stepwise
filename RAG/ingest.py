"""
ingest.py

Reads onboarding documents from sample_docs/, splits them into chunks,
embeds each chunk, and stores them in a local ChromaDB collection.

Run this once to build the vector store, and again any time the
onboarding docs change.
"""
import gdown
import os

# Download documents from Google Drive
DRIVE_FOLDER_ID = "1WXMzsx8tAeVNyPMJunEV6yrtL3VuPzP5"
DOCS_PATH = "sample_docs/"

def download_from_drive():
    url = f"https://drive.google.com/drive/folders/{DRIVE_FOLDER_ID}"
    gdown.download_folder(url, output=DOCS_PATH, quiet=False)

# Call this before ingesting
download_from_drive()

import os
import chromadb
from sentence_transformers import SentenceTransformer

DOCS_FOLDER = "sample_docs"
DB_FOLDER = "chroma_db"
COLLECTION_NAME = "onboarding_docs"

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

def load_documents(folder):
    """Read every .md file in the folder, return list of (filename, text)."""
    docs = []
    for filename in os.listdir(folder):
        if filename.endswith(".md"):
            path = os.path.join(folder, filename)
            with open(path, "r", encoding="utf-8") as f:
                docs.append((filename, f.read()))
    return docs


def main():
    print("Loading embedding model...")
    model = SentenceTransformer("all-MiniLM-L6-v2")

    print("Setting up ChromaDB...")
    client = chromadb.PersistentClient(path=DB_FOLDER)
    # Wipe and recreate the collection each run, so ingest.py is idempotent
    try:
        client.delete_collection(COLLECTION_NAME)
    except Exception:
        pass
    collection = client.create_collection(COLLECTION_NAME)

    print(f"Loading documents from {DOCS_FOLDER}/...")
    docs = load_documents(DOCS_FOLDER)
    print(f"Found {len(docs)} documents.")

    all_chunks = []
    all_ids = []
    all_metadata = []

    for filename, text in docs:
        chunks = chunk_text(text)
        for i, chunk in enumerate(chunks):
            all_chunks.append(chunk)
            all_ids.append(f"{filename}-{i}")
            all_metadata.append({"source": filename})

    print(f"Split into {len(all_chunks)} chunks. Embedding...")
    embeddings = model.encode(all_chunks).tolist()

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