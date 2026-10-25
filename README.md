# StepWise

StepWise helps sales agents onboard customers for clients such as Safaricom (POS merchants) and Airtel (line registration). Each type of job is a **gig** with its own list of steps. The agent works through the steps on their phone; at each step the system shows short AI tips, suggested questions, and a chat with an AI assistant. A manager assigns gigs to agents, follows every onboarding, gets alerts when an agent looks stuck, and sees which steps confuse agents most.

The app is built as an installable web app (PWA) that feels like a native phone app (bottom tabs, full-screen onboarding, works offline), because agents work in shops and on the street where the connection is often poor. Steps can be completed offline and are sent when the connection returns.

## How it works

```
Phone / browser
      |
      v
Laravel app (backend/)  --- MariaDB (users, agents, gigs, steps, sessions, progress, alerts, chat logs)
      |
      v
Flask service (RAG/)
      |
      +--> ChromaDB: finds the document chunks closest to the question (only for this gig)
      +--> Ollama (llama3.2:3b): writes the answer from those chunks
```

1. Onboarding documents (`RAG/sample_docs/<gig-slug>/*.md`) are split into chunks, turned into embeddings, and stored in ChromaDB (`RAG/ingest.py`). Each chunk is tagged with its gig.
2. A question is embedded, the closest chunks of that gig (plus general documents) are retrieved, and the local model answers using only those chunks.
3. Laravel stores the workflow, the agent's progress, the alerts, and every question and answer.

The AI is advisory only: it never validates or blocks a step, and photos, ID numbers and activation codes are never sent to it.

## Features

**Agent** (tabs: Home, My Onboardings, Profile)
- Sign in with Google (or a demo password on a local install).
- Home: summary, "continue where you left off", and the gigs the manager has assigned. With no gig assigned, a friendly message says so.
- One step per screen. Each step has its own input (photo, confirmation, text or choice) and is validated on the server. Steps must be completed in order.
- Short AI tips per step (generated once and cached), three suggested questions per step, and a chat with the AI.
- Works offline: steps are cached, finished steps are saved on the device (IndexedDB) and synced in order when the connection returns. An offline banner and a "N steps waiting to sync" chip show what is pending.
- A completion card at the end of a gig: gig, date, number of steps, time taken.
- Profile: Google photo, name, email, role, editable region, own numbers (gigs completed, steps saved, in progress), sync status, install hint, log out.

**Manager** (tabs: Overview, Agents, Alerts, Profile)
- Overview of all onboardings with a gig filter: status, progress against that gig's own steps, last activity.
- Agents: add an agent (name, phone, region and the Google email they sign in with), switch gigs on or off for each agent, see their progress per gig, and mark an agent inactive (they can no longer log in; their history is kept).
- Alerts for agents who may need help (see below), with a "mark resolved" action.
- Step difficulty: per gig, a bar per step for AI questions asked, alerts raised and typical time spent, with the hardest step highlighted.
- Detail page per onboarding: each step, what was submitted (photos and codes are never shown), and the questions the agent asked the AI.

## Alert rules

Defined in `backend/app/Services/AlertRules.php`:

| Rule | Condition | Default |
|------|-----------|---------|
| Repeated help | The agent asks the AI about the same step several times | 3 questions |
| Stalled onboarding | An unfinished onboarding has no activity | 30 minutes |

The values are constants at the top of the file. The stalled rule runs every 5 minutes through Laravel's scheduler (`routes/console.php`), and also whenever a manager opens Overview or Alerts.

## Tech stack

- Backend: Laravel 13 (PHP 8.3), Blade, Tailwind CSS (CDN), Laravel Socialite (Google login)
- Database: MySQL or MariaDB (InnoDB)
- AI service: Python, Flask, ChromaDB, sentence-transformers (`all-MiniLM-L6-v2`), Ollama with `llama3.2:3b`
- App feel and offline: service worker, web app manifest, IndexedDB

## Project structure

```
RAG-stepwise/
├── RAG/                          Python AI service
│   ├── ingest.py                 builds the vector store from the onboarding documents
│   ├── app.py                    Flask API used by Laravel
│   ├── debug_retrieve.py         shows which chunks a question retrieves
│   ├── requirements.txt
│   └── sample_docs/<gig-slug>/   onboarding documents per gig (markdown)
└── backend/                      Laravel application
    ├── app/
    │   ├── Http/Controllers/     Auth, AgentDashboard, StepProgress, Chat, ManagerDashboard, Profile
    │   ├── Http/Middleware/      EnsureRole (agent | manager)
    │   ├── Models/               User, Agent, Gig, Step, OnboardingSession, StepProgress, Alert, ChatLog
    │   └── Services/             AlertRules, SessionStatus
    ├── database/
    │   ├── migrations/
    │   └── seeders/              StepSeeder (gigs and steps), DemoUserSeeder (logins), DemoSeeder (demo data)
    ├── resources/views/
    │   ├── chat.blade.php        the full-screen onboarding wizard
    │   ├── partials/tabbar       bottom tab bar (tabs depend on the role)
    │   ├── agent/, manager/      the tab screens
    │   └── profile.blade.php
    ├── routes/web.php            every page and endpoint (behind login, role and CSRF)
    └── public/                   manifest.json, sw.js, js/sync-status.js, icons
```

## Setup

You need PHP 8.3, Composer, MySQL or MariaDB, Python 3.10 or newer, and [Ollama](https://ollama.com).

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
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

The Google keys come from a Google Cloud OAuth client. Only email addresses that already exist in `users` can sign in.

Then:

```
php artisan migrate
php artisan db:seed --class=StepSeeder        (gigs and their steps)
php artisan db:seed --class=DemoUserSeeder    (login accounts; password logins only on local)
php artisan db:seed --class=DemoSeeder        (optional: 3 demo agents with onboardings, alerts and chats)
```

All three seeders are safe to run again. `DemoSeeder` only rebuilds its own three demo agents, with dates relative to now.

**3. AI service**

```
cd RAG
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
ollama pull llama3.2:3b
python ingest.py            (or: python ingest.py --local)
```

`ingest.py` downloads the onboarding documents (or uses `sample_docs/` with `--local`), splits them into chunks, and builds the ChromaDB store. Run it again whenever the documents change, then restart `app.py` and run `php artisan cache:clear`.

## Running

Start these three, each in its own terminal:

```
ollama serve                      (if Ollama is not already running)
python app.py                     (in RAG/, listens on port 5000)
php artisan serve                 (in backend/, listens on port 8000)
php artisan schedule:work         (in backend/, checks for stalled onboardings every 5 minutes)
```

On a server, use the usual cron line for `php artisan schedule:run` instead of `schedule:work`.

Open `http://localhost:8000`. After login, agents land on `/agent` and managers on `/manager`. On a local install the demo accounts are `manager@stepwise.test`, `agent<id>@stepwise.test`, and (after `DemoSeeder`) `grace@`, `kevin@` and `faith@stepwise.test`, all with password `password`.

To open the app on a phone, run `php artisan serve --host=0.0.0.0` and use the laptop's IP address on the same network. Installing the app and offline mode need HTTPS, so they work on the phone only through an HTTPS tunnel or a deployed server. On the laptop, `localhost` counts as secure. On an iPhone, install with Share, then Add to Home Screen.

After changing anything in the front end, bump `CACHE_NAME` in `public/sw.js` and hard refresh.

Before a demo, run `php artisan db:seed --class=DemoSeeder` again so the demo onboardings are dated from now.

## Tests

```
cd backend
php artisan test
```

The feature tests in `backend/tests/Feature` run on a temporary in-memory SQLite database (see `phpunit.xml`), so they never touch the real data, and the AI service is faked. They check that every page needs the right login and role, that agents only reach their own onboardings, gig assignment (including on the server), steps saved in order and completion, that saving works when the AI is down, the AI question rules (gig, length, rate limit, repeated-help alert), the step difficulty view, adding and deactivating agents, and Google login.

## Endpoints

**Laravel (`backend/routes/web.php`)**. Every route except login needs a logged-in user; the role column shows who may use it.

| Method | Path | Role | Purpose |
|--------|------|------|---------|
| GET | `/` | guest | login page |
| GET | `/auth/google`, `/auth/google/callback` | guest | Google sign-in |
| POST | `/login` | guest | password login (local only, 5 tries a minute) |
| POST | `/logout` | any | log out |
| GET | `/profile` | any | Profile tab |
| POST | `/profile/region` | agent | change own region |
| GET | `/agent`, `/agent/onboardings` | agent | Home and My Onboardings tabs |
| POST | `/agent/sessions` | agent | start an onboarding of an **assigned** gig |
| GET | `/onboarding/{sessionId}` | agent | the wizard (own onboardings only) |
| GET | `/wizard/sessions/{id}/steps` | agent | steps of the session's gig with progress |
| POST | `/wizard/sessions/{id}/steps/{stepId}/submit` | agent | submit a step (validated, in order) |
| GET | `/wizard/steps/{stepId}/guidance` | agent | AI tips for a step (cached) |
| POST | `/wizard/ask` | agent | ask the AI (max 500 characters, 10 a minute), saved in the chat log |
| GET | `/manager`, `/manager/sessions/{id}` | manager | Overview (`?gig=<slug>` filter) and onboarding detail |
| GET | `/manager/agents`, `/manager/agents/{id}` | manager | Agents tab and one agent's gigs |
| GET, POST | `/manager/agents/create`, `/manager/agents` | manager | add an agent and their login |
| POST | `/manager/agents/{id}/status` | manager | mark an agent `active` or `inactive` |
| POST | `/manager/agents/{id}/gigs/{gigId}` | manager | assign (`assign=1`) or unassign (`assign=0`) a gig |
| GET | `/manager/alerts` | manager | Alerts tab (`?gig=<slug>` filter) |
| POST | `/manager/alerts/{id}/resolve` | manager | mark an alert resolved |
| GET | `/manager/insights` | manager | step difficulty (`?gig=<slug>`) |

An agent asking for another agent's onboarding gets a 404; the wrong role, or an inactive agent, gets a 403.

**Flask (`RAG/app.py`)**: `GET /health`, `POST /ask` (takes `question` and `gig`).

## Database

| Table | Holds |
|-------|-------|
| `users` | logins: name, email, Google `avatar`, `role` (agent or manager), `agent_id` |
| `agents` | the field agents: name, phone, region, status |
| `gigs` | types of job: slug, name, client, description |
| `steps` | each gig's steps: order, instructions, `input_type`, `input_config` |
| `agent_gig` | which gigs each agent may start: `assigned_by`, `assigned_at` |
| `onboarding_sessions` | one onboarding: agent, gig, status, start and end |
| `step_progress` | one saved step of an onboarding and what was submitted |
| `alerts`, `chat_logs` | alerts raised and questions asked, per onboarding and step |

Photos are stored in `storage/app/private/onboarding/{session}` and only their path is kept in `step_progress.submission_data`.

## Troubleshooting

- **Migration fails with "Specified key was too long".** The database is using MyISAM. Run `SET GLOBAL default_storage_engine=InnoDB;` and migrate again. On WAMP this setting resets when MySQL restarts, unless `default-storage-engine=InnoDB` is in `my.ini`.
- **Answers take 30 seconds on the first request.** Ollama is loading the model. `app.py` warms it up on start, and keeps it loaded for 30 minutes.
- **Tips look out of date after changing the documents or steps.** Run `python ingest.py`, restart `app.py`, then `php artisan cache:clear`.
- **The phone still shows an old screen.** The service worker cached it. Bump `CACHE_NAME` in `public/sw.js`, then reload (or unregister the service worker in the browser's developer tools).

## Limitations

- The AI runs on a small local model on a CPU, so it is slow and its answers depend entirely on the quality of the onboarding documents. It cannot see photos and does not validate anything; validation is done by rules in the backend.
- The alerts table has no type column, so the rule that raised an alert is recognised from the start of its reason text.
- The stalled rule needs `php artisan schedule:work` (or a cron job) to run on its own; without it, it still runs when a manager opens Overview or Alerts.
- Step times in the step difficulty view are measured between saves. Steps saved offline are timed when they sync.
- Onboardings do not record who the customer is.
- In offline mode, AI chat and new tips need a connection.
- There is one team: every manager sees every agent. Agents can be added and deactivated in the app, but not edited or deleted (use tinker for that).
