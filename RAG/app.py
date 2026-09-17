"""
app.py

Flask API wrapper around the RAG pipeline. Laravel calls this over HTTP
instead of talking to ChromaDB/Ollama directly.

Run with: python app.py
Then it's available at http://localhost:5000

Endpoints:
  GET  /health          -> quick check that the service is up
  POST /ask              -> { "question": "..." } -> { "answer": "...", "sources": [...] }
"""

from flask import Flask, request, jsonify
import chromadb
from sentence_transformers import SentenceTransformer
import ollama

DB_FOLDER = "chroma_db"
COLLECTION_NAME = "onboarding_docs"
OLLAMA_MODEL = "llama3.2:3b"
TOP_K = 3

SYSTEM_PROMPT = """You are Stepwise, an onboarding assistant for new employees.
Answer the employee's question using ONLY the context provided below.
If the answer is not in the context, say you don't have that information
and suggest they check with HR or their line manager.
Keep answers short and direct.
"""

app = Flask(__name__)

# Load models/DB connection once at startup, not per-request.
print("Loading embedding model...")
embed_model = SentenceTransformer("all-MiniLM-L6-v2")

print("Connecting to ChromaDB...")
chroma_client = chromadb.PersistentClient(path=DB_FOLDER)
collection = chroma_client.get_collection(COLLECTION_NAME)

print("Stepwise RAG service ready.")


def retrieve(question, top_k=TOP_K):
    query_embedding = embed_model.encode([question]).tolist()
    results = collection.query(query_embeddings=query_embedding, n_results=top_k)
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


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok"})


@app.route("/ask", methods=["POST"])
def ask():
    data = request.get_json(silent=True) or {}
    question = data.get("question", "").strip()

    if not question:
        return jsonify({"error": "Missing 'question' in request body"}), 400

    chunks, sources = retrieve(question)
    prompt = build_prompt(question, chunks)
    answer = ask_ollama(prompt)

    return jsonify({
        "answer": answer,
        "sources": list(set(sources)),
    })


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=False)