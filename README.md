# Barangay E-Services Portal

A web-based portal that lets residents of **Barangay San Roque** request barangay documents online, book appointments, track their requests in real time, and get AI-powered guidance on requirements, while giving barangay staff a single dashboard to process everything.

---

## Features

### For Residents
- **Request documents online**: apply for barangay documents without lining up at the barangay hall.
- **Book appointments**: schedule a pickup or visit, including walk-in slot availability.
- **Real-time tracking**: follow a request from submission to release using a tracking number.
- **AI assistance**: ask questions about document requirements, fees, and the application process, and get step-by-step guidance.

### For Barangay Staff & Admins
- **Dashboard**: at-a-glance view of total requests, pending approvals, documents released today, and today's appointments.
- **Clearance Requests**: review, approve, process, reject, or release incoming requests, with search and filters by status and document type.
- **Appointments**: view and manage resident appointments by date and status.
- **Document Tracking**: look up any request by tracking number and view its full status timeline.
- **Residents registry**: manage registered residents (name, email, purok, sex/age) and view each resident's request history.
- **Reports & analytics**: request volume, status breakdown, document-type breakdown, residents per purok, and fees collected.
- **Staff & Admins**: manage system accounts, roles, and account status.
- **Settings**: configure portal preferences.

---

## Supported Documents

| Document | Description |
|---|---|
| Barangay Clearance | General-purpose clearance issued to residents |
| Certificate of Residency | Proof that a person resides in the barangay |
| Certificate of Indigency | Certification for residents who qualify for assistance |
| Business Permit Endorsement | Barangay endorsement required for business permits |

Each request records the **purpose** (e.g., travel requirement), the **fee**, and its **payment status**.

---

## Request Lifecycle

Every request has a tracking number in the format `BRGY-YYYY-XXXXXX` (e.g., `BRGY-2026-633119`) and moves through these statuses:

```
Pending → Processing → Ready for Pickup → Released
              ↘ Rejected
              ↘ Cancelled
```

| Status | Meaning |
|---|---|
| **Pending** | Submitted and awaiting staff review |
| **Processing** | Approved and being prepared |
| **Ready for Pickup** | Document is ready to be claimed |
| **Released** | Document has been handed to the resident |
| **Rejected** | Request was denied by staff |
| **Cancelled** | Request was cancelled |

The tracking page shows a timestamped timeline of each step.

---

## User Roles

| Role | Access |
|---|---|
| **Resident** | Submit requests, book appointments, track documents, use the AI assistant |
| **Staff** | Process requests, manage appointments, view residents |
| **Admin** | Full access, including reports, staff accounts, and settings |

---

## Screens

| Screen | Purpose |
|---|---|
| Dashboard | Overview stats, recent requests, today's appointments |
| Clearance Requests | Searchable, filterable list of all document requests |
| Appointments | Date-based appointment list with status filter |
| Doc Tracking | Track a request by tracking number |
| Residents | Registry of residents with request counts |
| Reports | Analytics and statistics |
| Staff & Admins | Account management |
| Settings | Portal configuration |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | [Laravel](https://laravel.com) (PHP), REST API |
| Frontend | [Vue.js](https://vuejs.org) (Vite) |
| Database | MySQL / PostgreSQL |
| Auth | Laravel Sanctum (token / session-based) |
| AI Assistant | LLM provider API, called from the Laravel backend |

---

## Getting Started

### Prerequisites
- PHP 8.2+ and [Composer](https://getcomposer.org)
- Node.js 18+ and npm
- MySQL or PostgreSQL
- An API key for your AI provider (for the AI assistant)

### Backend Setup (Laravel)

```bash
# Clone the repository
git clone https://github.com/<your-org>/barangay-eservices.git
cd barangay-eservices/backend

# Install PHP dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations (and seeders)
php artisan migrate --seed

# Start the API server
php artisan serve
```

### Frontend Setup (Vue)

```bash
cd ../frontend

# Install dependencies
npm install

# Configure environment
cp .env.example .env

# Start the dev server
npm run dev
```

### Environment Variables

**Backend (`backend/.env`)**

```env
APP_NAME="Barangay E-Services"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=barangay_eservices
DB_USERNAME=
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173

AI_API_KEY=
```

**Frontend (`frontend/.env`)**

```env
VITE_API_BASE_URL=http://localhost:8000/api
```

### Production Build

```bash
# Frontend
cd frontend && npm run build

# Backend optimizations
cd ../backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Useful Commands

```bash
php artisan migrate:fresh --seed   # Reset database with seed data
php artisan test                   # Run backend tests
npm run lint                       # Lint frontend code
```

> **Security note:** Change the default administrator credentials immediately after first login, and never commit real resident data, `.env` files, or API keys to the repository.

---

## Project Structure

```
barangay-eservices/
├── backend/                  # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/ # Requests, Appointments, Residents, Reports, ...
│   │   ├── Models/           # Resident, DocumentRequest, Appointment, User
│   │   └── Services/         # Tracking numbers, AI assistant integration
│   ├── database/             # Migrations, seeders, factories
│   ├── routes/api.php
│   └── .env.example
├── frontend/                 # Vue app
│   ├── src/
│   │   ├── views/            # Dashboard, Requests, Appointments, Tracking, ...
│   │   ├── components/       # Reusable UI components
│   │   ├── router/
│   │   ├── stores/           # State management (Pinia)
│   │   └── services/         # API client
│   └── .env.example
└── README.md
```

---

## AI Assistant

The built-in assistant helps residents by:
- Explaining the **requirements** for each document type
- Clarifying **fees** and **processing steps**
- Guiding them through **booking an appointment**
- Answering common barangay-service questions

The assistant provides guidance only. Final approval of any document rests with barangay officials.

---

## Data Privacy

This system handles personal information of residents. Deployments should comply with the **Data Privacy Act of 2012 (RA 10173)**: collect only what is needed, restrict access by role, and secure stored data.

---

## Roadmap

- [ ] SMS / email notifications on status changes
- [ ] Online payment integration
- [ ] Downloadable, QR-verified digital documents
- [ ] Additional document types
- [ ] Multi-language support (English / Filipino / Cebuano)

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m "Add your feature"`
4. Push the branch: `git push origin feature/your-feature`
5. Open a Pull Request

---

## License

Specify your license here (e.g., MIT).

---

## Contact

**Barangay San Roque**
E-Services Portal Support: `admin@barangay.gov.ph`
