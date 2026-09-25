# StepWise - AI-Powered Onboarding Navigator

StepWise is a mobile-first Progressive Web Application designed to guide independent sales agents through structured onboarding workflows in real time. Built for the SalesMesh distributed sales platform, the system enforces sequential step completion, generates contextually grounded AI guidance at each step using Retrieval-Augmented Generation, and provides managers with live oversight through a real-time dashboard and alert system.

---

## Project Structure
---

## System Overview

StepWise consists of three integrated components:

- **Agent PWA** - A mobile-first web application that presents onboarding steps one at a time, validates each submission before unlocking the next step, and displays RAG-generated guidance at every stage.
- **Laravel Backend** - A RESTful API handling step sequencing, submission validation, session management, alert generation, and communication with the RAG service.
- **Manager Dashboard** - A web-based interface providing live visibility into all active onboarding sessions with colour-coded status indicators and a real-time alert feed.

---

## AI Guidance - RAG Pipeline

The AI guidance layer uses Retrieval-Augmented Generation to deliver contextually accurate onboarding assistance to agents in the field.

**How it works:**
1. Agent reaches an onboarding step
2. Laravel invokes the RAG service via `predict.py`
3. The agent's input is embedded using `sentence-transformers`
4. ChromaDB performs a semantic search and returns the most relevant knowledge base chunks
5. An augmented prompt is passed to a locally deployed LLM via Ollama
6. The generated guidance response is returned to Laravel and displayed on the agent's screen

**Current status:** RAG pipeline operational with synthetic knowledge base. Real onboarding documentation to be integrated in Sprint 2.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Mobile Frontend | HTML, CSS, JavaScript (PWA) |
| Backend API | Laravel (PHP) |
| Database | MySQL |
| AI - Embeddings | sentence-transformers |
| AI - Vector Store | ChromaDB |
| AI - Orchestration | LangChain |
| AI - LLM | Ollama (local deployment) |
| Version Control | Git / GitHub |

---

## Getting Started

### RAG Service Setup

```bash
# Navigate to the RAG service directory
cd rag-service

# Create a virtual environment
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Ingest knowledge base documents into ChromaDB
python ingest.py

# Test the RAG pipeline
python query.py
```

### Backend Setup (coming in Sprint 2)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

---

## Development Sprints

| Sprint | Focus | Status |
|---|---|---|
| Sprint 1 | Development environment setup, RAG pipeline, knowledge base | 🟡 In Progress |
| Sprint 2 | Laravel backend, database migrations, RAG integration |🟡 In Progress|
| Sprint 3 | PWA frontend - agent interface | ⬜ Pending |
| Sprint 4 | Manager dashboard | ⬜ Pending |
| Sprint 5 | Alert engine, offline support | ⬜ Pending |
| Sprint 6 | Admin interface, user management | ⬜ Pending |
| Sprint 7 | Integration testing, refinement | ⬜ Pending |
| Sprint 8 | Final testing, documentation, deployment | ⬜ Pending |

---

## Project Context

This project is developed as part of the ICS4 final year project at Strathmore University, Kenya, in collaboration with SalesMesh through the 51Webmasters Kenya programme.

---

## Author

Rehana Deborah - BSc. Informatics and Computer Science, Strathmore University