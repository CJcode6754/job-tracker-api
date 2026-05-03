# HireSight API

> REST API backend for HireSight — a job application tracking platform built for serious job seekers.

HireSight API powers the entire backend of the HireSight platform. It handles authentication, application data, interview tracking, dashboard analytics, and AI integrations via Google Gemini. Built with Laravel 12, secured with Sanctum HttpOnly cookie auth, and deployed on Render with Aiven MySQL.

**Frontend:** [hire-sight-track.vercel.app](https://hire-sight-track.vercel.app)
**API Base URL:** `https://job-tracker-api-1-c066.onrender.com/api`

---

## What Does This API Do?

- Authenticates users with secure HttpOnly cookie-based tokens (no localStorage exposure)
- Stores and manages job applications with full metadata: company, role, status, priority, salary, location, deadlines, and notes
- Tracks interview rounds per application with type, date, interviewer, and self-rating
- Manages contacts associated with each application
- Provides dashboard statistics and pipeline analytics
- Integrates with Google Gemini 2.5 Flash for AI chat, cover letter generation, pipeline insights, and job description tagging
- Enforces per-user data isolation via policy-based authorization
- Rate limits auth, application, and AI endpoints separately

---

## Tech Stack

| Category | Technology |
|----------|-----------|
| Framework | Laravel 12 (PHP 8.2+) |
| Authentication | Laravel Sanctum (HttpOnly cookies) |
| Database | MySQL 8 via Aiven |
| ORM | Eloquent |
| AI | Google Gemini 2.5 Flash |
| ID Obfuscation | Hashids (vinkla/hashids) |
| Containerization | Docker (richarvey/nginx-php-fpm) |
| Deployment | Render |

---

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+

---

## Local Setup

```bash
# Install dependencies
composer install

# Copy and configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Seed demo data (creates demo@example.com with 100 sample applications)
php artisan db:seed

# Start development server
php artisan serve
```

API runs at `http://localhost:8000`

---

## Environment Variables

```env
APP_NAME="HireSight"
APP_ENV=production
APP_KEY=                          # php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-api-domain.com

FRONTEND_URL=https://your-frontend-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host.com
DB_PORT=3306
DB_DATABASE=job_tracker
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Session & Cookie Security
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Sanctum — comma-separated frontend domains allowed for cookie auth
SANCTUM_STATEFUL_DOMAINS=localhost:5173,your-frontend-domain.com

# Google Gemini AI
GEMINI_API_KEY=                   # https://console.cloud.google.com
GEMINI_MODEL=gemini-2.5-flash
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models
```

---

## API Reference

All endpoints are prefixed with `/api`. Protected routes require the `auth_token` HttpOnly cookie.

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/register` | No | Register a new user |
| POST | `/login` | No | Login — sets HttpOnly auth cookie |
| POST | `/logout` | Yes | Logout — clears auth cookie |
| POST | `/refresh` | Yes | Refresh token — issues new cookie |
| GET | `/me` | Optional | Get authenticated user or null |
| GET | `/health` | No | Health check for uptime monitoring |

**Register body:**
```json
{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

Password requirements: 12+ characters, uppercase, lowercase, number, special character.

---

### Applications

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/applications` | List applications (paginated, filterable) |
| POST | `/applications` | Create a new application |
| GET | `/applications/{id}` | Get application with contacts and interview rounds |
| PUT | `/applications/{id}` | Update application |
| DELETE | `/applications/{id}` | Delete application |

**GET /applications query params:**

| Param | Type | Description |
|-------|------|-------------|
| `search` | string | Filter by company or role name |
| `status` | string | Filter by status (see statuses below) |
| `priority` | string | Filter by priority: `low`, `medium`, `high` |
| `per_page` | integer | Results per page (1–200, default 20) |
| `show_rejected` | boolean | Include rejected applications |
| `show_archived` | boolean | Include archived applications |

**Application statuses:** `wishlist`, `applied`, `phone_screen`, `interview`, `offer`, `rejected`, `archived`

**Application body:**
```json
{
  "company": "Stripe",
  "role": "Senior Backend Engineer",
  "job_url": "https://stripe.com/jobs/123",
  "status": "applied",
  "priority": "high",
  "location": "Remote",
  "work_type": "remote",
  "employment_type": "full_time",
  "applied_date": "2026-05-01",
  "deadline": "2026-05-15",
  "salary_min": 120000,
  "salary_max": 160000,
  "salary_currency": "USD",
  "notes": "Referral from John. Strong culture fit."
}
```

---

### Interview Rounds

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/applications/{id}/interview-rounds` | List rounds for an application |
| POST | `/applications/{id}/interview-rounds` | Add a new round |
| GET | `/applications/{id}/interview-rounds/{round}` | Get a round |
| PUT | `/applications/{id}/interview-rounds/{round}` | Update a round |
| DELETE | `/applications/{id}/interview-rounds/{round}` | Delete a round |

**Round body:**
```json
{
  "type": "technical",
  "date": "2026-05-10",
  "interviewer_name": "Alice Chen",
  "notes": "Focused on system design and distributed systems.",
  "self_rating": 4
}
```

Round types: `technical`, `hr`, `system_design`, `take_home`

---

### Dashboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/dashboard/stats` | Pipeline statistics and analytics |

**Response:**
```json
{
  "total": 45,
  "active": 32,
  "offers": 2,
  "by_status": [
    { "status": "applied", "count": 18 },
    { "status": "interview", "count": 6 }
  ],
  "by_week": [
    { "week": "2026-18", "count": 8 }
  ],
  "interviews": {
    "total_rounds": 14,
    "avg_rating": 3.8,
    "by_type": { "technical": 8, "hr": 4, "system_design": 2 },
    "active_count": 6
  }
}
```

---

### AI Endpoints

All AI endpoints are rate-limited and require authentication.

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/ai/chat` | Chat assistant with full pipeline context |
| POST | `/ai/cover-letter` | Generate a tailored cover letter |
| POST | `/ai/insights` | Get 5 AI-generated pipeline insights |
| POST | `/ai/tag-job` | Auto-tag a job description |

**POST /ai/chat body:**
```json
{
  "message": "Which companies haven't responded in over 2 weeks?",
  "history": [
    { "role": "user", "text": "How many applications do I have?" },
    { "role": "model", "text": "You have 45 applications in total." }
  ]
}
```

**POST /ai/cover-letter body:**
```json
{
  "company": "Stripe",
  "role": "Senior Backend Engineer",
  "job_description": "We are looking for...",
  "notes": "Referral from John",
  "user_background": "5 years Laravel, built payment systems"
}
```

**POST /ai/tag-job body:**
```json
{
  "job_description": "We are looking for a Senior React Developer..."
}
```

**POST /ai/tag-job response:**
```json
{
  "tags": {
    "role_title": "Senior React Developer",
    "company": "Acme Corp",
    "location": "Remote",
    "seniority": "senior",
    "employment_type": "full-time",
    "remote_policy": "remote",
    "tech_stack": ["React", "TypeScript", "GraphQL"],
    "key_requirements": ["5+ years React", "TypeScript", "REST APIs"],
    "salary_range": "$120k–$160k",
    "company_size_hint": "startup",
    "estimated_priority": "high",
    "priority_reason": "Strong tech match and remote-first culture."
  }
}
```

---

## Rate Limiting

| Group | Limit | Applies To |
|-------|-------|-----------|
| `auth` | Strict | `/register`, `/login` |
| `app` | Standard | All `/applications` routes |
| `ai` | Strict | All `/ai/*` routes |

---

## Security

- Tokens stored in HttpOnly cookies — not accessible to JavaScript
- All application routes protected by `auth:sanctum` middleware
- Policy-based authorization — users can only access their own data
- Status and priority filters validated against explicit allowlists
- AI inputs sanitized against prompt injection patterns
- Gemini API key sent via `x-goog-api-key` header — never in URL query params
- Password requirements enforced at registration (12+ chars, complexity rules)
- `SESSION_ENCRYPT=true` and `SESSION_SECURE_COOKIE=true` in production

---

## Deployment (Render)

The app runs in Docker using `richarvey/nginx-php-fpm`. On every deploy, `scripts/00-laravel-deploy.sh` runs automatically:

```bash
composer install --no-dev
php artisan config:cache
php artisan route:cache
php artisan migrate --force
php artisan db:seed --class=RequiredDataSeeder --force
```

`RequiredDataSeeder` is idempotent — it only creates the demo user and seeds applications if they don't already exist. Safe to run on every deploy.

### Demo Account
Created automatically on first deploy:
- **Email:** demo@example.com
- **Password:** password
- **Data:** 100 sample applications with interview rounds

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php     # Login, logout, refresh, me
│   │   │   └── RegisterController.php  # Registration
│   │   ├── ApplicationController.php   # Application CRUD
│   │   ├── InterviewRoundController.php
│   │   ├── DashboardController.php     # Pipeline stats
│   │   └── AiController.php            # Gemini AI endpoints
│   ├── Middleware/
│   └── Requests/
│       ├── LoginRequest.php
│       ├── RegisterRequest.php
│       ├── StoreApplicationRequest.php
│       └── StoreInterviewRoundRequest.php
├── Models/
│   ├── User.php
│   ├── Application.php       # Has Hashids ID obfuscation
│   ├── InterviewRound.php
│   └── Contact.php
├── Policies/
│   └── ApplicationPolicy.php # view, update, delete authorization
└── Services/
    └── GeminiService.php     # Gemini chat, generate, generateJson
database/
├── migrations/
├── factories/
│   ├── ApplicationFactory.php
│   ├── InterviewRoundFactory.php
│   └── UserFactory.php
└── seeders/
    ├── DatabaseSeeder.php        # Local dev — creates demo user + 100 apps
    ├── ApplicationSeeder.php     # Generates 100 realistic applications
    └── RequiredDataSeeder.php    # Production deploy — idempotent
scripts/
└── 00-laravel-deploy.sh          # Auto-runs on Render deploy
```

---

## Built By

**Ceejay Ibabiosa** — Built HireSight to end the chaos of manual job tracking and give job seekers an enterprise-grade dashboard with full pipeline visibility and AI-powered insights.
