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

SYSTEM_PROMPT = """
You are StepWise, an AI onboarding assistant helping Sales Agents onboard merchants.
Answer the Sales Agent's question using ONLY the context provided below.
Use the available context to give clear, practical, and actionable guidance for the merchant onboarding process.
If the provided context does not directly answer the question, say:
"I don't have that information in my current onboarding knowledge base."
Do not infer, assume, or create additional procedures from general knowledge.
Do not combine unrelated information from different documents to answer the question.
Only state a procedure if it is explicitly supported by the provided context.Keep answers concise and easy for a Sales Agent to follow.
Do not override the StepWise onboarding workflow or its validation rules.
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


def build_prompt(question, chunks, step=None):

    context = "\n\n---\n\n".join(chunks)

    step_context = ""

    if step:
        step_context = f"""
Current StepWise Onboarding Step:
Step {step.get('order_number')}: {step.get('title')}

Step Instructions:
{step.get('instructions')}

Guidance:
{step.get('guidance_label')}

Validation Rules:
{step.get('validation_rules')}
"""

    return f"""You are StepWise, an AI onboarding assistant helping Sales Agents onboard merchants.

The Sales Agent is currently working on the onboarding step shown below.

{step_context}

Answer the Sales Agent's question using ONLY the information explicitly supported by the provided knowledge-base context and the current StepWise step.

STRICT RULES:
- Use the current step to understand what the Sales Agent is trying to accomplish.
- Use the knowledge-base context to provide detailed guidance about that task.
- Do not use general knowledge.
- Do not guess or infer missing information.
- Do not create procedures that are not explicitly stated in the context.
- Do not override the current step's instructions or validation rules.
- Do not combine unrelated information to create a new procedure.
- If the context does not directly answer the question, say:
"I don't have that information in my current onboarding knowledge base."
- Keep the answer short and practical for a Sales Agent.

Knowledge Base Context:
{context}

Sales Agent's Question:
{question}

Answer:"""



def ask_ollama(prompt, max_tokens=None):
    options = {"temperature": 0.2}
    if max_tokens:
        options["num_predict"] = max_tokens

    response = ollama.chat(
        model=OLLAMA_MODEL,
        messages=[
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": prompt},
        ],
        options=options,
        keep_alive="30m",
    )
    return response["message"]["content"]


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok"})


@app.route("/ask", methods=["POST"])
def ask():
    data = request.get_json(silent=True) or {}
    question = data.get("question", "").strip()
    step = data.get("step")

    max_tokens = data.get("max_tokens")
    max_tokens = min(max_tokens, 400) if max_tokens else None
    top_k = int(data.get("top_k", TOP_K))

    if not question:
        return jsonify({"error": "Missing 'question' in request body"}), 400

    chunks, sources = retrieve(question, top_k)
    prompt = build_prompt(question, chunks, step=step)
    answer = ask_ollama(prompt, max_tokens)

    return jsonify({
        "answer": answer,
        "sources": list(set(sources)),
    })


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=False)