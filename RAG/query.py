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

SYSTEM_PROMPT = """
You are StepWise, an AI onboarding assistant helping Sales Agents onboard merchants.
Answer the Sales Agent's question using ONLY the context provided below.
Use the available context to give clear, practical, and actionable guidance for the merchant onboarding process.
If the provided context does not directly answer the question, say:
"I don't have that information in my current onboarding knowledge base."
Do not infer, assume, or create additional procedures from general knowledge.
Do not combine unrelated information from different documents to answer the question.
Only state a procedure if it is explicitly supported by the provided context.
Keep answers concise and easy for a Sales Agent to follow.
Do not override the StepWise onboarding workflow or its validation rules.
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

    return f"""You are StepWise, an AI onboarding assistant for Sales Agents.

    You must answer the question using ONLY the information explicitly stated in the Context below.

    STRICT RULES:
    - Do not use general knowledge.
    - Do not guess or infer missing information.
    - Do not create procedures that are not explicitly stated in the Context.
    - Do not assume that a situation has a solution if the Context does not provide one.
    - Do not combine unrelated information to create a new procedure.
    - If the Context does not explicitly answer the question, respond exactly:
    "I don't have that information in my current onboarding knowledge base."
    - Only mention troubleshooting actions if they are explicitly stated in the Context.
    - Keep the answer short and practical for a Sales Agent.

    Context:
    {context}

    Question:
    {question}
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