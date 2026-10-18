"""
debug_retrieve.py

Shows which document chunks the search returns for a query, best match first.
Lower distance means a closer match.

Run from the RAG folder:
    python debug_retrieve.py "Collect Customer ID"
    python debug_retrieve.py "I am currently on the onboarding step Collect Customer ID. Please explain how to complete this step and what I should check before submitting it."
"""

import sys

import chromadb
from sentence_transformers import SentenceTransformer

DB_FOLDER = "chroma_db"
COLLECTION_NAME = "onboarding_docs"
HOW_MANY = 5

query = " ".join(sys.argv[1:]).strip() or "Collect Customer ID"

model = SentenceTransformer("all-MiniLM-L6-v2")
collection = chromadb.PersistentClient(path=DB_FOLDER).get_collection(COLLECTION_NAME)

result = collection.query(
    query_embeddings=model.encode([query]).tolist(),
    n_results=HOW_MANY,
    include=["documents", "metadatas", "distances"],
)

print(f'\nQuery: "{query}"')
print(f"Chunks in the store: {collection.count()}\n")

for rank, (doc, meta, dist) in enumerate(
    zip(result["documents"][0], result["metadatas"][0], result["distances"][0]), start=1
):
    preview = doc[:150].replace("\n", " ")
    print(f"#{rank}  distance {dist:.3f}  {meta.get('source', 'unknown')}")
    print(f"    {preview}...\n")