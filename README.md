# Kelma 🗣️
### Agentic AI-Driven Speech Therapy Platform for Children

> ESLSCA University · School of Computing & Digital Technologies · Graduation Project 2026-2027

![Status](https://img.shields.io/badge/status-in%20development-orange)
![Frontend](https://img.shields.io/badge/frontend-HTML%20%7C%20CSS%20%7C%20JS-blue)
![Backend](https://img.shields.io/badge/backend-PHP-777BB4)
![Agent](https://img.shields.io/badge/agent-CrewAI%20(Python)-green)

---

## 📖 About

**Kelma** is a web-based, agentic AI-driven speech and language therapy platform for young children (preschool to early primary school age). It delivers personalized, adaptive practice through interactive games, while giving parents and therapists the tools to monitor and guide progress.

Unlike apps that only score pronunciation, Kelma uses an **Agentic AI Therapy Agent** that reasons about each child's needs and selects, adapts, and sequences activities on its own. A licensed therapist stays in the loop and can review, adjust, or approve the agent's recommendations. Kelma **supports** therapy; it does not replace therapists.

**Target areas:** articulation difficulties · phonological disorders · vocabulary delay

---

## ✨ Key Features

| Feature | Description |
|---|---|
| 🤖 **Therapy Agent** | CrewAI agent that observes progress, picks the game and difficulty, and adapts the therapy path |
| 🎙️ **Speech Analysis Engine** | Pronunciation scoring via a pronunciation-assessment API with custom struggle-point detection |
| 🎮 **Three games** | *Say It & Collect It*, *Word Hunt*, *Speech Rocket* |
| 🔁 **Shared Stimulus Engine** | One reusable pipeline: present → capture → analyze → reward → report |
| ⭐ **Decoupled rewards** | Motivational score (stars, coins) kept separate from the diagnostic score used by the agent |
| 👨‍👩‍👧 **Parent dashboard** | Simple progress summaries and at-home practice suggestions |
| 🧑‍⚕️ **Therapist dashboard** | Detailed reports, AI recommendations, and plan approval |
| 🔒 **Security & privacy** | Role-based access (child / parent / therapist), encryption, data minimization |
| 📊 **Agent observability** | Langfuse / Langtrace traces of tool calls, latency, and token cost |

### The Agent's Tools
`select_activity` · `select_words` · `on_failure` · `log_progress`

**3-tier failure fallback:** retry → simplified cue → easier substitute word.

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript, Lottie animations |
| Backend API | PHP |
| Database | PHPMyAdmin
| Agent Service | Python, CrewAI (exposed over HTTP, e.g. FastAPI) |
| Speech Analysis | Azure AI Speech Pronunciation Assessment |
| Observability | Langfuse or Langtrace |
| Architecture Visuals | Langflow, draw.io |
| Design | Figma, Canva |
| Security Testing | OWASP Top 10, OWASP ZAP |
| Project Management | Trello, GitHub |

---

## 🏗️ Architecture (High Level)

```
┌──────────────────────┐     HTTPS      ┌─────────────────────┐
│  Browser (HTML/CSS/  │ ─────────────▶ │   PHP Backend API   │
│  JS) – Games &       │ ◀───────────── │  Auth · RBAC · Game │
│  Dashboards          │                │  sessions · Reports │
└──────────────────────┘                └──────┬────────┬─────┘
                                               │        │
                                    HTTP       │        │  SQL
                                               ▼        ▼
                              ┌──────────────────┐  ┌──────────────┐
                              │ Agent Service    │  │  Database    │
                              │ (Python+CrewAI)  │◀▶│ Word Bank ·  │
                              │ + Langfuse       │  │ Progress ·   │
                              └────────┬─────────┘  │ Users · Plans│
                                       │            └──────────────┘
                                       ▼
                              ┌──────────────────┐
                              │ Speech Analysis  │
                              │ (Azure API)      │
                              └──────────────────┘
```

---

## 📁 Repository Structure

```
kelma/
├── README.md
├── .gitignore
├── .env.example              # Template for environment variables (never commit .env)
├── docs/                     # Milestone documents and diagrams
│   ├── proposal/
│   ├── srs/
│   ├── sdd/
│   └── diagrams/
├── web/                      # Frontend + PHP backend
│   ├── index.php
│   ├── assets/               # css/, js/, images/, audio/, lottie/
│   ├── games/                # say-it/, word-hunt/, speech-rocket/
│   ├── dashboards/           # parent/, therapist/
│   ├── api/                  # PHP endpoints
│   └── includes/             # config, db connection, auth, helpers
├── agent-service/            # Python + CrewAI
│   ├── app.py                # HTTP entry point
│   ├── agents/               # Therapy Agent definition
│   ├── tools/                # select_activity, select_words, on_failure, log_progress
│   └── requirements.txt
├── database/
│   ├── schema.sql
│   └── seed_wordbank.sql
├── poc/                      # Proof of concept (Milestone 1)
└── tests/
```

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.1+
- PHPMyAdmin (XAMPP / WAMP / Laragon works well)
- Python 3.10+
- Git
- An Azure AI Speech key and region *(for pronunciation assessment)*
- A Langfuse account or self-hosted instance *(optional, for tracing)*

### 1. Clone the repository
```bash
git clone https://github.com/<your-org-or-username>/kelma.git
cd kelma
```

### 2. Configure environment variables
```bash
cp .env.example .env
# then fill in your own values (database, Azure key, LLM key, Langfuse keys)
```

### 3. Set up the database
```bash
mysql -u root -p -e "CREATE DATABASE kelma CHARACTER SET utf8mb4;"
mysql -u root -p kelma < database/schema.sql
mysql -u root -p kelma < database/seed_wordbank.sql
```

### 4. Run the agent service
```bash
cd agent-service
python -m venv venv
source venv/bin/activate        # Windows: venv\Scripts\activate
pip install -r requirements.txt
uvicorn app:app --reload --port 8001
```

### 5. Run the web app
Place the project in your web server's root (e.g. `htdocs` in XAMPP), or use PHP's built-in server:
```bash
cd web
php -S localhost:8000
```
Open **http://localhost:8000** in your browser.

---

## 🔐 Security & Privacy Notes
- Secrets live in `.env` and are **never committed**.
- Passwords are stored with `password_hash()`.
- Access is restricted by role (child, parent, therapist).
- Only the minimum child data needed for therapy is stored.
- Use only test data and consented recordings during development and testing.

---

## 🗓️ Milestones (Fall 2026)

| Milestone | Week | Focus | Code Target |
|---|---|---|---|
| 1 | Week 5 | Project Proposal + Proof of Concept | 10% |
| 2 | Week 9 | Software Requirements Specification + Code Stubs | 25% |
| 3 | Week 12 | System Design Document + Core Code | 40% |

Spring 2027 covers Milestones 4-6.

---

## 🌿 Contribution Workflow
1. Create a branch from `develop`: `feature/<short-name>` (e.g. `feature/speech-rocket-ui`).
2. Commit small, clear changes (`feat:`, `fix:`, `docs:`, `chore:`).
3. Open a Pull Request into `develop` and request a review from a teammate.
4. After review, merge. `main` only receives stable, milestone-ready code.

---

## 👥 Team

| Name | Student ID | 
|---|---|---|
| [Kenzi Ahmos El Haw] | [242500002] | 
| [Mohamed Zouhir Kazan] | [242500176] | 
| [Alia Sakr] | [242500226] | 
| [Hania Abdelrahman Elsayed] | [242500629] | 
| [Maryam Ahmed Yehia Sherif] | [242500955] | 

**Supervisor:** [DR. Ammar Mohammed]
**Institution:** ESLSCA University, School of Computing & Digital Technologies

---

## ⚠️ Disclaimer
Kelma is an academic graduation project. It is intended to support, not replace, professional speech-language therapy, and it is not a medical device.

All rights reserved