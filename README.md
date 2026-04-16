# KNCode - API Job Portal

Freelance platform API built with Laravel 13, connecting companies with freelancers.

## Features

- **User Roles**: `admin`, `company`, `freelance`
- **Work Management**: Post works, specify budgets & skills
- **Skill System**: Technical skill tagging for works and freelancers
- **Authentication**: Laravel Sanctum & Fortify
- **Application System**: Freelancers apply, companies view CVs

## Getting Started

### Prerequisites
PHP 8.3+, Composer, Node.js & NPM, SQLite

### Installation
```bash
git clone <repository-url> && cd kncode
composer run setup
php artisan db:seed
```

### Development Server
```bash
composer run dev
```
Available at `http://localhost:8000`.

## API Endpoints

### Authentication
| Method | Endpoint | Auth | Description |
|--------|----------|-----|-------------|
| POST | `/api/register` | No | Register a new user |
| POST | `/api/login` | No | Authenticate a user |
| POST | `/api/logout` | Yes | Revoke current token |
| GET | `/api/me` | Yes | Get authenticated user |

### Works (Public)
| Method | Endpoint | Auth | Description |
|--------|----------|-----|-------------|
| GET | `/api/works` | No | List open works |

**Query params**: `search`, `type` (`fixed`/`hourly`), `experience_level` (`entry`/`intermediate`/`expert`), `budget_min`, `budget_max`, `skills[]`, `sort_by` (`created_at`/`title`/`budget_min`/`budget_max`), `sort_order` (`asc`/`desc`), `per_page` (1–100), `page`

### Works (Company)
| Method | Endpoint | Auth | Description |
|--------|----------|-----|-------------|
| GET | `/api/company/works` | Company | List own works (any status) |
| POST | `/api/works` | Company | Create work (defaults to `draft`) |
| PUT | `/api/works/{work_id}` | Company | Update work (must own) |

**Company works query params**: Same as public works + `status` (`draft`/`open`/`in_progress`/`closed`/`cancelled`)

**POST /api/works body**: `title`* (string, max 255), `description` (text), `budget_min` (numeric), `budget_max` (numeric, ≥ budget_min), `type`* (`fixed`/`hourly`), `experience_level`* (`entry`/`intermediate`/`expert`), `deadline_date` (date, after today), `skills` (array of IDs)

**PUT /api/works/{work_id} body**: All fields optional + `status` (`draft`/`open`/`closed`/`cancelled`). `company_id` cannot be changed.

### Work Applications (Company)
| Method | Endpoint | Auth | Description |
|--------|----------|-----|-------------|
| GET | `/api/works/{work_id}/applications` | Company | List applications (includes freelancer CV) |
| GET | `/api/works/{work_id}/applications/{application_id}` | Company | View single application with freelancer CV |

**Query params**: `status` (`pending`/`accepted`/`rejected`/`withdrawn`), `sort_by` (`created_at`/`proposed_rate`), `sort_order` (`asc`/`desc`), `per_page` (1–100), `page`

> Only the work owner can view applications. Response includes freelancer profile with skills (including proficiency level) and portfolios.

### Work Applications (Freelancer)
| Method | Endpoint | Auth | Description |
|--------|----------|-----|-------------|
| POST | `/api/works/{work_id}/applications` | Freelance | Apply to an open work |

**Body**: `cover_letter` (text), `proposed_rate` (numeric, ≥ 0)

> Freelancers can only apply to `open` works, one application per work. Defaults to `pending` status.

### Skills
| Method | Endpoint | Auth | Description |
|--------|----------|-----|-------------|
| GET | `/api/skills` | No | List technical skills |

## Work Statuses
| Status | Description |
|--------|-------------|
| `draft` | Initial status |
| `open` | Visible to freelancers, accepting applications |
| `in_progress` | Work assigned, being completed |
| `closed` | Work completed |
| `cancelled` | Cancelled by company |

## Testing
```bash
composer test
```

## Tech Stack
Laravel 13 | Inertia.js + React | Sanctum & Fortify | Pest PHP | Laravel Pint
