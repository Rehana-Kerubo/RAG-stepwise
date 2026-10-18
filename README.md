# StepWise

StepWise helps sales agents onboard small merchants onto a POS and digital payments system. The agent works through a fixed list of steps (identity documents, device setup, Till or Paybill request, first transaction, staff accounts). At each step the system shows short AI tips, and the agent can ask questions in a chat. A manager can see every onboarding, and gets alerts when an agent looks stuck.

The app is built as an installable web app (PWA) for phones, because agents work in shops and on the street where the connection is often poor. Steps can be completed offline and are sent when the connection returns.

## How it works

```
Phone / browser
      |
      v
Laravel app (backend/)  --- MySQL (agents, sessions, steps, progress, alerts, chat logs)
      |
      v
Flask service (RAG/)
      |
      +--> ChromaDB: finds the onboarding document chunks closest to the question
      +--> Ollama (llama3.2:3b): writes the answer from those chunks
```

1. Onboarding documents are split into chunks, turned into embeddings, and stored in ChromaDB (`RAG/ingest.py`).
2. A question (or a step's title and instructions) is embedded, the closest chunks are retrieved, and the local model answers using only those chunks.
3. Laravel stores the workflow, the agent's progress, the alerts, and every question and answer.

## Features

**Agent**
- Pick an agent, start a new onboarding, or continue an existing one.
- 13 steps, one per screen. Each step has its own input (photo, confirmation, code, or choice) and is validated on the server. Steps must be completed in order.
- Short AI tips for the current step, generated once per step and cached.
- Chat with the AI about the current step.
- Works offline: steps are cached, completed steps are saved on the device and synced in order when the connection returns.

**Manager**
- Overview of all onboardings: status, progress, last activity.
- Alerts for agents who may need help (see below), with a "mark resolved" action.
- Detail page per onboarding: each step, what was submitted, and the questions the agent asked the AI.

## Alert rules

Defined in `backend/app/Services/AlertRules.php`:

| Rule | Condition | Default |
|------|-----------|---------|
| Repeated help | The agent asks the AI about the same step several times | 3 questions |
| Stalled onboarding | An unfinished onboarding has no activity | 30 minutes |

The values are constants at the top of the file.

## Tech stack

- Backend: Laravel (PHP 8.3), Blade, Tailwind CSS (CDN)
- Database: MySQL or MariaDB (InnoDB)
- AI service: Python, Flask, ChromaDB, sentence-transformers (`all-MiniLM-L6-v2`), Ollama with `llama3.2:3b`
- Offline: service worker, web app manifest, IndexedDB

## Project structure

```
RAG-stepwise/
├── RAG/                          Python AI service
│   ├── ingest.py                 builds the vector store from the onboarding documents
│   ├── app.py                    Flask API used by Laravel
│   ├── query.py                  command-line version for testing
│   ├── requirements.txt
│   └── sample_docs/              onboarding documents (markdown)
└── backend/                      Laravel application
    ├── app/
    │   ├── Http/Controllers/     Chat, StepProgress, AgentDashboard, ManagerDashboard
    │   ├── Models/               Agent, OnboardingSession, Step, StepProgress, Alert, ChatLog
    │   └── Services/             AlertRules, SessionStatus
    ├── database/
    │   ├── migrations/           the six tables
    │   └── seeders/              the 13 onboarding steps
    ├── resources/views/          layouts, agent, manager, and the onboarding screen (chat.blade.php)
    ├── routes/                   web.php and api.php
    └── public/                   manifest.json, sw.js, icons
```

## Setup

You need PHP 8.2 or newer, Composer, MySQL or MariaDB, Python 3.10 or newer, and [Ollama](https://ollama.com).

**1. Database.** Create an empty database. It must use InnoDB (see Troubleshooting).

**2. Laravel**

```
cd backend
composer install
copy .env.example .env
php artisan key:generate
```

Edit `.env`: set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_PORT`, and add

```
FLASK_API_URL=http://localhost:5000
```

Then:

```
php artisan migrate
php artisan db:seed --class=StepSeeder
```

Add at least one agent, for example in `php artisan tinker`:

```php
App\Models\Agent::create(['name' => 'Amina Wanjiku', 'phone' => '0711000001', 'region' => 'Nairobi West', 'status' => 'active']);
```

**3. AI service**

```
cd RAG
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
ollama pull llama3.2:3b
python ingest.py
```

`ingest.py` downloads the onboarding documents, splits them into chunks, and builds the ChromaDB store. Run it again whenever the documents change, then restart `app.py`.

## Running

Start these three, each in its own terminal:

```
ollama serve                      (if Ollama is not already running)
python app.py                     (in RAG/, listens on port 5000)
php artisan serve                 (in backend/, listens on port 8000)
```

| Page | Address |
|------|---------|
| Choose an agent | `http://localhost:8000/agent` |
| Manager dashboard | `http://localhost:8000/manager` |

To open the app on a phone, run `php artisan serve --host=0.0.0.0` and use the laptop's IP address on the same network. Installing the app and offline mode need HTTPS, so they work on the phone only through an HTTPS tunnel or a deployed server. On the laptop, `localhost` counts as secure.

## Endpoints

**Laravel (`backend/routes`)**

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/agent`, `/agent/{id}` | choose an agent, agent dashboard |
| POST | `/agent/{id}/sessions` | start a new onboarding |
| GET | `/onboarding/{sessionId}` | the onboarding screen |
| GET | `/manager`, `/manager/sessions/{id}` | manager overview and detail |
| POST | `/manager/alerts/{id}/resolve` | mark an alert resolved |
| GET | `/api/sessions/{id}/steps` | steps with this session's progress |
| POST | `/api/sessions/{id}/steps/{stepId}/submit` | submit a step (validated, in order) |
| GET | `/api/steps/{stepId}/guidance` | AI tips for a step (cached) |
| POST | `/api/ask` | ask the AI a question, saved in the chat log |

**Flask (`RAG/app.py`)**: `GET /health`, `POST /ask`.

## Database

Six tables: `agents`, `onboarding_sessions`, `steps`, `step_progress`, `alerts`, `chat_logs`. Photos submitted in steps 1 to 3 are stored in `storage/app/private/onboarding/{session}` and only their path is kept in `step_progress.submission_data`.

## Troubleshooting

- **Migration fails with "Specified key was too long".** The database is using MyISAM. Run `SET GLOBAL default_storage_engine=InnoDB;` and migrate again. On WAMP this setting resets when MySQL restarts.
- **Answers take 30 seconds on the first request.** Ollama is loading the model. `app.py` warms it up on start, and keeps it loaded for 30 minutes.
- **Tips look out of date after changing the documents.** Run `python ingest.py`, restart `app.py`, then `php artisan cache:clear`.

## Limitations

- There is no login. The user is chosen from a list, which is fine for a demo but not for real use.
- The AI runs on a small local model on a CPU, so it is slow and its tips depend entirely on the quality of the onboarding documents. It cannot see photos and does not validate anything; validation is done by rules in the backend.
- The alerts table has no type column, so the rule that raised an alert is recognised from the start of its reason text.
- The stalled rule is checked when the manager dashboard is opened, not on a schedule.
- Onboardings do not record who the merchant is.
- In offline mode, AI chat and new tips need a connection.

