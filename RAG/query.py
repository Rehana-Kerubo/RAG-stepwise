"""
query.py

The RAG loop: takes a question, retrieves the most relevant chunks
from ChromaDB, and passes them to a local Ollama model to generate
a grounded answer.

Run ingest.py first to build the vector store.
"""

import chromadb
from sentence_transformers import SentenceTransformer
import ollama

DB_FOLDER = "chroma_db"
COLLECTION_NAME = "onboarding_docs"
OLLAMA_MODEL = "llama3.2:3b"
TOP_K = 3   # how many chunks to retrieve per question

SYSTEM_PROMPT = """You are Stepwise, an onboarding assistant for new employees.
Answer the employee's question using ONLY the context provided below.
If the answer is not in the context, say you don't have that information
and suggest they check with HR or their line manager.
Keep answers short and direct.
"""


def retrieve(question, collection, model, top_k=TOP_K):
    """Embed the question and pull the top_k most similar chunks."""
    query_embedding = model.encode([question]).tolist()
    results = collection.query(
        query_embeddings=query_embedding,
        n_results=top_k,
    )
    chunks = results["documents"][0]
    sources = [meta["source"] for meta in results["metadatas"][0]]
    return chunks, sources


def build_prompt(question, chunks):
    context = "\n\n---\n\n".join(chunks)
    return f"""Context:
{context}

Question: {question}

Answer:"""


def ask_ollama(prompt):
    response = ollama.chat(
        model=OLLAMA_MODEL,
        messages=[
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": prompt},
        ],
    )
    return response["message"]["content"]


def main():
    print("Loading embedding model...")
    embed_model = SentenceTransformer("all-MiniLM-L6-v2")

    print("Connecting to ChromaDB...")
    client = chromadb.PersistentClient(path=DB_FOLDER)
    collection = client.get_collection(COLLECTION_NAME)

    print(f"Ready. Using model '{OLLAMA_MODEL}'. Type 'exit' to quit.\n")

    while True:
        question = input("You: ").strip()
        if question.lower() in ("exit", "quit"):
            break
        if not question:
            continue

        chunks, sources = retrieve(question, collection, embed_model)
        prompt = build_prompt(question, chunks)
        answer = ask_ollama(prompt)

        print(f"\nStepwise: {answer}")
        print(f"(sources: {', '.join(set(sources))})\n")


if __name__ == "__main__":
    main()