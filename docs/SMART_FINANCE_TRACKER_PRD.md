# Smart Personal Finance Tracker — Product Requirements Document

> **Project Codename:** FinTrack
> **Stack:** Flutter 3.x (Riverpod) + Laravel 13 + PostgreSQL + Google Gemini AI
> **Target:** Portfolio-ready fullstack mobile app
> **PRD Version:** 1.0 — March 2026

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [System Architecture](#2-system-architecture)
3. [Database Schema](#3-database-schema)
4. [API Endpoints](#4-api-endpoints)
5. [Authentication — Laravel Sanctum](#5-authentication--laravel-sanctum)
6. [Feature Specifications](#6-feature-specifications)
7. [AI Features — Google Gemini Integration](#7-ai-features--google-gemini-integration)
8. [UI/UX Design System](#8-uiux-design-system)
9. [Flutter Folder Structure](#9-flutter-folder-structure)
10. [Laravel Folder Structure](#10-laravel-folder-structure)
11. [Implementation Roadmap](#11-implementation-roadmap)
12. [Claude Code CLI Prompts](#12-claude-code-cli-prompts)
13. [CLAUDE.md Project Context File](#13-claudemd-project-context-file)
14. [QUICKSTART.sh Cheatsheet](#14-quickstartsh-cheatsheet)

---

## 1. Project Overview

### 1.1 Description

**FinTrack** is a smart personal finance tracker that helps users manage their money through budgeting, spending habit monitoring, and AI-powered insights. The app combines traditional CRUD-based finance management with intelligent features powered by Google Gemini (`gemini-2.0-flash`).

### 1.2 Core Value Propositions

- **Transaction Management** — Full CRUD with multi-currency support and recurring transactions
- **Budget Control** — Category-based budgets with real-time alerts when approaching/exceeding limits
- **AI Auto-Categorization** — Gemini automatically categorizes transactions based on description/merchant name
- **AI Spending Insights** — Pattern-based savings suggestions derived from user spending history
- **AI Financial Chatbot** — Conversational financial advisor that answers questions about user's own data
- **Export & Reporting** — Generate PDF/CSV reports of transactions and spending summaries
- **Dark Mode** — Full theme support with system-aware toggle

### 1.3 Learning Objectives

- Laravel backend with Sanctum auth, job queues, scheduled commands, and API resources
- Flutter with Riverpod (code generation), go_router, fl_chart, and Clean Architecture
- PostgreSQL with migrations, indexes, and Eloquent relationships
- AI integration via Google Gemini API with SSE streaming for chatbot
- Multi-currency handling, recurring transaction scheduling, and PDF/CSV export

---

## 2. System Architecture

### 2.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────┐
│                  FLUTTER MOBILE APP                  │
│  ┌──────────┐  ┌──────────┐  ┌───────────────────┐  │
│  │ Riverpod │  │ go_router│  │ fl_chart / Syncfusion│ │
│  │  State   │  │Navigation│  │   Charts & PDF     │  │
│  └────┬─────┘  └──────────┘  └───────────────────┘  │
│       │                                              │
│  ┌────┴──────────────────────────────────────────┐   │
│  │            Data Layer (Repositories)           │   │
│  │  ┌─────────┐  ┌──────────┐  ┌──────────────┐  │   │
│  │  │  Dio    │  │  Hive    │  │ Secure       │  │   │
│  │  │ HTTP    │  │  Cache   │  │ Storage      │  │   │
│  │  └─────────┘  └──────────┘  └──────────────┘  │   │
│  └───────────────────────┬───────────────────────┘   │
└──────────────────────────┼───────────────────────────┘
                           │ HTTPS / REST API
┌──────────────────────────┼───────────────────────────┐
│               LARAVEL 13 BACKEND                      │
│  ┌───────────┐  ┌────────┴───────┐  ┌─────────────┐  │
│  │ Sanctum   │  │ API Controllers│  │ Form Request│  │
│  │ Auth      │  │ + Resources    │  │ Validation  │  │
│  └───────────┘  └────────────────┘  └─────────────┘  │
│  ┌───────────┐  ┌────────────────┐  ┌─────────────┐  │
│  │ Eloquent  │  │ Job Queues     │  │ Scheduled   │  │
│  │ ORM       │  │ (Notifications)│  │ Commands    │  │
│  └─────┬─────┘  └────────────────┘  └─────────────┘  │
│        │        ┌────────────────┐                     │
│        │        │ Gemini AI      │                     │
│        │        │ Service        │                     │
│        │        └────────────────┘                     │
└────────┼──────────────────────────────────────────────┘
         │
┌────────┴──────────────────────────────────────────────┐
│                   POSTGRESQL                           │
│  users │ wallets │ categories │ transactions │ budgets  │
│  recurring_transactions │ currencies │ ai_chat_sessions │
└───────────────────────────────────────────────────────┘
```

### 2.2 Tech Stack Summary

| Layer          | Technology                           | Purpose                           |
| -------------- | ------------------------------------ | --------------------------------- |
| Mobile         | Flutter 3.x                          | Cross-platform UI                 |
| State          | Riverpod + code gen                  | Reactive state management         |
| Navigation     | go_router                            | Declarative routing               |
| Charts         | fl_chart                             | Dashboard visualizations          |
| Local Storage  | Hive                                 | Offline cache                     |
| Secure Storage | flutter_secure_storage               | Token storage                     |
| HTTP           | Dio + interceptors                   | API communication                 |
| Backend        | Laravel 13                           | REST API server                   |
| Auth           | Laravel Sanctum                      | Token-based authentication        |
| Database       | PostgreSQL 16                        | Relational data store             |
| ORM            | Eloquent                             | Database abstraction              |
| Queue          | Laravel Queue (database driver)      | Background jobs                   |
| AI             | Google Gemini API (gemini-2.0-flash) | Categorization, insights, chatbot |
| PDF Export     | laravel-dompdf (backend)             | Generate PDF reports              |
| CSV Export     | Laravel Excel / manual               | Generate CSV exports              |

---

## 3. Database Schema

### 3.1 Entity Relationship Diagram (Textual)

```
users 1──N wallets
users 1──N categories (custom)
users 1──N transactions
users 1──N budgets
users 1──N recurring_transactions
users 1──N ai_chat_sessions 1──N ai_chat_messages

wallets 1──N transactions
categories 1──N transactions
categories 1──N budgets
currencies 1──N wallets
currencies 1──N transactions
```

### 3.2 Table Definitions

#### `users`

```sql
CREATE TABLE users (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) UNIQUE NOT NULL,
    password        VARCHAR(255) NOT NULL,
    default_currency_code VARCHAR(3) DEFAULT 'IDR',
    avatar_url      TEXT,
    email_verified_at TIMESTAMPTZ,
    remember_token  VARCHAR(100),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
```

#### `currencies`

```sql
CREATE TABLE currencies (
    code            VARCHAR(3) PRIMARY KEY,  -- ISO 4217: IDR, USD, EUR
    name            VARCHAR(100) NOT NULL,
    symbol          VARCHAR(10) NOT NULL,
    decimal_places  SMALLINT DEFAULT 2,
    exchange_rate_to_usd DECIMAL(20,10),     -- Updated periodically
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
```

#### `wallets`

```sql
CREATE TABLE wallets (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name            VARCHAR(255) NOT NULL,        -- "BCA", "GoPay", "Cash"
    type            VARCHAR(50) NOT NULL,          -- 'bank', 'e_wallet', 'cash', 'credit_card'
    currency_code   VARCHAR(3) NOT NULL REFERENCES currencies(code),
    balance         DECIMAL(18,2) DEFAULT 0,
    icon            VARCHAR(50),
    color           VARCHAR(7),                    -- Hex color: #FF5733
    is_active       BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(user_id, name)
);
CREATE INDEX idx_wallets_user ON wallets(user_id);
```

#### `categories`

```sql
CREATE TABLE categories (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT REFERENCES users(id) ON DELETE CASCADE, -- NULL = system default
    name            VARCHAR(100) NOT NULL,
    type            VARCHAR(10) NOT NULL CHECK (type IN ('income', 'expense')),
    icon            VARCHAR(50) NOT NULL,
    color           VARCHAR(7) NOT NULL,
    is_system       BOOLEAN DEFAULT FALSE,
    parent_id       BIGINT REFERENCES categories(id) ON DELETE SET NULL,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_categories_user ON categories(user_id);
CREATE INDEX idx_categories_type ON categories(type);
```

**System Default Categories (seeded):**

| Type    | Categories                                                                                                                                 |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Expense | Food & Drink, Transportation, Shopping, Bills & Utilities, Entertainment, Health, Education, Groceries, Subscriptions, Transfer Out, Other |
| Income  | Salary, Freelance, Investment, Gift, Transfer In, Other                                                                                    |

#### `transactions`

```sql
CREATE TABLE transactions (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    wallet_id       BIGINT NOT NULL REFERENCES wallets(id) ON DELETE CASCADE,
    category_id     BIGINT REFERENCES categories(id) ON DELETE SET NULL,
    type            VARCHAR(10) NOT NULL CHECK (type IN ('income', 'expense', 'transfer')),
    amount          DECIMAL(18,2) NOT NULL,
    currency_code   VARCHAR(3) NOT NULL REFERENCES currencies(code),
    description     TEXT,
    merchant_name   VARCHAR(255),
    transaction_date TIMESTAMPTZ NOT NULL,
    is_recurring    BOOLEAN DEFAULT FALSE,
    recurring_id    BIGINT REFERENCES recurring_transactions(id) ON DELETE SET NULL,
    ai_categorized  BOOLEAN DEFAULT FALSE,       -- Was category auto-set by AI?
    ai_confidence   DECIMAL(3,2),                 -- AI confidence score 0.00-1.00
    notes           TEXT,
    attachment_url  TEXT,                          -- Receipt photo URL
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_transactions_user_date ON transactions(user_id, transaction_date DESC);
CREATE INDEX idx_transactions_wallet ON transactions(wallet_id);
CREATE INDEX idx_transactions_category ON transactions(category_id);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_transactions_recurring ON transactions(recurring_id);
```

#### `budgets`

```sql
CREATE TABLE budgets (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    category_id     BIGINT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    amount          DECIMAL(18,2) NOT NULL,
    currency_code   VARCHAR(3) NOT NULL REFERENCES currencies(code),
    period          VARCHAR(10) NOT NULL CHECK (period IN ('weekly', 'monthly', 'yearly')),
    alert_threshold DECIMAL(3,2) DEFAULT 0.80,    -- Alert at 80% by default
    start_date      DATE NOT NULL,
    end_date        DATE,
    is_active       BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(user_id, category_id, period)
);
CREATE INDEX idx_budgets_user ON budgets(user_id);
```

#### `recurring_transactions`

```sql
CREATE TABLE recurring_transactions (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    wallet_id       BIGINT NOT NULL REFERENCES wallets(id) ON DELETE CASCADE,
    category_id     BIGINT REFERENCES categories(id) ON DELETE SET NULL,
    type            VARCHAR(10) NOT NULL CHECK (type IN ('income', 'expense')),
    amount          DECIMAL(18,2) NOT NULL,
    currency_code   VARCHAR(3) NOT NULL REFERENCES currencies(code),
    description     TEXT NOT NULL,
    merchant_name   VARCHAR(255),
    frequency       VARCHAR(20) NOT NULL CHECK (frequency IN ('daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'yearly')),
    next_due_date   DATE NOT NULL,
    last_processed  DATE,
    is_active       BOOLEAN DEFAULT TRUE,
    auto_create     BOOLEAN DEFAULT TRUE,         -- Auto-create transaction on due date
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_recurring_user ON recurring_transactions(user_id);
CREATE INDEX idx_recurring_next_due ON recurring_transactions(next_due_date) WHERE is_active = TRUE;
```

#### `ai_chat_sessions`

```sql
CREATE TABLE ai_chat_sessions (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title           VARCHAR(255),
    created_at      TIMESTAMPTZ DEFAULT NOW(),
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_chat_sessions_user ON ai_chat_sessions(user_id);
```

#### `ai_chat_messages`

```sql
CREATE TABLE ai_chat_messages (
    id              BIGSERIAL PRIMARY KEY,
    session_id      BIGINT NOT NULL REFERENCES ai_chat_sessions(id) ON DELETE CASCADE,
    role            VARCHAR(10) NOT NULL CHECK (role IN ('user', 'model')),
    content         TEXT NOT NULL,
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX idx_chat_messages_session ON ai_chat_messages(session_id);
```

---

## 4. API Endpoints

### 4.1 Authentication

| Method | Endpoint                | Description                    |
| ------ | ----------------------- | ------------------------------ |
| POST   | `/api/v1/auth/register` | Register new user              |
| POST   | `/api/v1/auth/login`    | Login, returns Sanctum token   |
| POST   | `/api/v1/auth/logout`   | Revoke current token           |
| GET    | `/api/v1/auth/user`     | Get authenticated user profile |
| PUT    | `/api/v1/auth/user`     | Update profile                 |
| PUT    | `/api/v1/auth/password` | Change password                |

### 4.2 Wallets

| Method | Endpoint                            | Description         |
| ------ | ----------------------------------- | ------------------- |
| GET    | `/api/v1/wallets`                   | List all wallets    |
| POST   | `/api/v1/wallets`                   | Create wallet       |
| GET    | `/api/v1/wallets/{id}`              | Get wallet detail   |
| PUT    | `/api/v1/wallets/{id}`              | Update wallet       |
| DELETE | `/api/v1/wallets/{id}`              | Delete wallet       |
| GET    | `/api/v1/wallets/{id}/transactions` | Wallet transactions |

### 4.3 Categories

| Method | Endpoint                  | Description                       |
| ------ | ------------------------- | --------------------------------- |
| GET    | `/api/v1/categories`      | List categories (system + custom) |
| POST   | `/api/v1/categories`      | Create custom category            |
| PUT    | `/api/v1/categories/{id}` | Update custom category            |
| DELETE | `/api/v1/categories/{id}` | Delete custom category            |

### 4.4 Transactions

| Method | Endpoint                       | Description                      |
| ------ | ------------------------------ | -------------------------------- |
| GET    | `/api/v1/transactions`         | List with filters & pagination   |
| POST   | `/api/v1/transactions`         | Create transaction               |
| GET    | `/api/v1/transactions/{id}`    | Get detail                       |
| PUT    | `/api/v1/transactions/{id}`    | Update transaction               |
| DELETE | `/api/v1/transactions/{id}`    | Delete transaction               |
| GET    | `/api/v1/transactions/summary` | Spending summary (by date range) |

**Query Parameters for `GET /transactions`:**

- `wallet_id` — Filter by wallet
- `category_id` — Filter by category
- `type` — `income`, `expense`, `transfer`
- `start_date`, `end_date` — Date range filter
- `search` — Search description/merchant
- `sort_by` — `transaction_date`, `amount` (default: `transaction_date`)
- `sort_order` — `asc`, `desc` (default: `desc`)
- `per_page` — Pagination size (default: 20, max: 100)

### 4.5 Budgets

| Method | Endpoint                   | Description                              |
| ------ | -------------------------- | ---------------------------------------- |
| GET    | `/api/v1/budgets`          | List all budgets with current spending   |
| POST   | `/api/v1/budgets`          | Create budget                            |
| GET    | `/api/v1/budgets/{id}`     | Get budget detail with spending progress |
| PUT    | `/api/v1/budgets/{id}`     | Update budget                            |
| DELETE | `/api/v1/budgets/{id}`     | Delete budget                            |
| GET    | `/api/v1/budgets/overview` | All budgets with % used this period      |

### 4.6 Recurring Transactions

| Method | Endpoint                         | Description                  |
| ------ | -------------------------------- | ---------------------------- |
| GET    | `/api/v1/recurring`              | List recurring transactions  |
| POST   | `/api/v1/recurring`              | Create recurring transaction |
| GET    | `/api/v1/recurring/{id}`         | Get detail                   |
| PUT    | `/api/v1/recurring/{id}`         | Update                       |
| DELETE | `/api/v1/recurring/{id}`         | Delete                       |
| POST   | `/api/v1/recurring/{id}/skip`    | Skip next occurrence         |
| POST   | `/api/v1/recurring/{id}/process` | Manually process now         |

### 4.7 AI Services

| Method | Endpoint                        | Description                             |
| ------ | ------------------------------- | --------------------------------------- |
| POST   | `/api/v1/ai/categorize`         | Auto-categorize transaction             |
| GET    | `/api/v1/ai/insights`           | Get spending insights/suggestions       |
| POST   | `/api/v1/ai/chat`               | Send message to AI advisor (SSE stream) |
| GET    | `/api/v1/ai/chat/sessions`      | List chat sessions                      |
| GET    | `/api/v1/ai/chat/sessions/{id}` | Get chat history                        |
| DELETE | `/api/v1/ai/chat/sessions/{id}` | Delete session                          |

### 4.8 Export & Reports

| Method | Endpoint                          | Description                 |
| ------ | --------------------------------- | --------------------------- |
| GET    | `/api/v1/export/transactions/csv` | Export transactions as CSV  |
| GET    | `/api/v1/export/transactions/pdf` | Export transactions as PDF  |
| GET    | `/api/v1/export/report/pdf`       | Monthly spending report PDF |

### 4.9 Currencies

| Method | Endpoint                   | Description               |
| ------ | -------------------------- | ------------------------- |
| GET    | `/api/v1/currencies`       | List supported currencies |
| GET    | `/api/v1/currencies/rates` | Get exchange rates        |

### 4.10 Dashboard / Analytics

| Method | Endpoint            | Description               |
| ------ | ------------------- | ------------------------- |
| GET    | `/api/v1/dashboard` | Aggregated dashboard data |

**Dashboard Response includes:**

- Total balance across all wallets (converted to default currency)
- Income vs expense this month
- Top spending categories this month
- Budget alerts (categories nearing limit)
- Recent transactions (last 5)
- Spending trend (last 6 months)

---

## 5. Authentication — Laravel Sanctum

### 5.1 Flow

```
┌──────────┐    POST /auth/login     ┌───────────┐
│  Flutter  │ ──────────────────────> │  Laravel   │
│   App     │ <────────────────────── │  Sanctum   │
│           │   { token, user }       │           │
│           │                         │           │
│  Dio      │    Authorization:       │  Middleware│
│  Intercept│    Bearer {token}       │  auth:     │
│  or       │ ──────────────────────> │  sanctum   │
└──────────┘                         └───────────┘
```

### 5.2 Implementation Notes

- Token stored in `flutter_secure_storage` on the mobile side
- Dio interceptor attaches `Authorization: Bearer {token}` to every request
- Laravel middleware `auth:sanctum` protects all `/api/v1/*` routes (except auth)
- Token abilities/scopes not used (simple personal app)
- On 401 response → clear stored token → redirect to login screen
- Password hashing via `bcrypt` (Laravel default)

### 5.3 Registration Request Validation

```php
[
    'name'     => 'required|string|max:255',
    'email'    => 'required|email|unique:users,email',
    'password' => 'required|string|min:8|confirmed',
    'default_currency_code' => 'sometimes|string|size:3|exists:currencies,code',
]
```

### 5.4 Login Response Format

```json
{
    "data": {
        "user": {
            "id": 1,
            "name": "Keina",
            "email": "keina@example.com",
            "default_currency_code": "IDR",
            "avatar_url": null
        },
        "token": "1|abc123...plainTextToken",
        "token_type": "Bearer"
    }
}
```

---

## 6. Feature Specifications

### 6.1 Transaction Management

**Create Transaction Flow:**

1. User taps "+" FAB on home/transaction screen
2. Selects type: Income / Expense / Transfer
3. Fills amount (numeric keyboard with currency symbol)
4. Selects wallet (from user's wallets)
5. Enters description / merchant name
6. Selects or auto-assigns category (AI button available)
7. Picks date (defaults to today)
8. Optional: add notes, attach receipt photo
9. Submit → POST to API → wallet balance updated → local cache refreshed

**Auto-categorization flow:**

- When user enters a description/merchant and taps the "AI Categorize" button
- App sends `POST /ai/categorize` with `{ description, merchant_name, amount, type }`
- Backend calls Gemini with user's category list → returns suggested category + confidence
- If confidence >= 0.85, auto-select. If < 0.85, show suggestion with "Accept / Change" option

**Transfer between wallets:**

- Type = `transfer` creates two linked transactions: expense from source, income to destination
- Both use a special "Transfer" category
- Wallet balances updated atomically

### 6.2 Budget Management

**Budget Creation:**

- User picks an expense category, sets limit amount, and period (weekly/monthly/yearly)
- Alert threshold configurable (default 80%)

**Budget Monitoring:**

- Dashboard shows progress bars for each active budget
- Color coding: green (< 60%), yellow (60-80%), orange (80-alert_threshold%), red (> 100%)
- When a new transaction pushes budget past alert_threshold → trigger notification job
- Budget overview shows: budget name, limit, spent, remaining, percentage, days left in period

**Budget Alert Notification (Laravel Queue Job):**

```
BudgetAlertJob dispatched when:
  - New transaction created/updated
  - Transaction category matches an active budget
  - Current period spending >= budget.amount * budget.alert_threshold
  - Alert not already sent for this period
```

### 6.3 Recurring Transactions

**Scheduled Processing (Laravel Console Kernel):**

- `ProcessRecurringTransactions` command runs daily via `schedule:run`
- Finds all active recurring where `next_due_date <= today` and `auto_create = true`
- Creates actual transaction, updates wallet balance, advances `next_due_date`
- Dispatches notification job to inform user

**Frequency calculations:**
| Frequency | Next date logic |
|-----------|----------------|
| daily | +1 day |
| weekly | +7 days |
| biweekly | +14 days |
| monthly | same day next month (Carbon::addMonth) |
| quarterly | +3 months |
| yearly | +1 year |

### 6.4 Multi-Currency Support

- Each wallet has a fixed currency
- Transactions inherit wallet's currency by default
- Dashboard totals converted to user's `default_currency_code`
- Exchange rates stored in `currencies` table, updated via scheduled Laravel command (daily) using a free API (e.g., exchangerate-api.com or frankfurter.app)
- Conversion formula: `amount_in_usd = amount / exchange_rate_to_usd`, then `amount_in_target = amount_in_usd * target_rate`

### 6.5 Export & Reports

**CSV Export:**

- Generates CSV with columns: Date, Type, Category, Description, Amount, Currency, Wallet, Notes
- Filterable by date range, wallet, category
- Served as file download response

**PDF Export (via laravel-dompdf):**

- Transaction list PDF: formatted table with filters applied
- Monthly report PDF: summary with category breakdown, income vs expense chart (rendered as HTML table, converted to PDF), top merchants, comparison with previous month

### 6.6 Dark Mode

- Flutter: `ThemeMode.system`, `ThemeMode.light`, `ThemeMode.dark`
- Theme preference saved in Hive local storage
- Riverpod `themeProvider` reads/writes preference
- All colors defined in design system using `ColorScheme.fromSeed()` with light/dark variants

---

## 7. AI Features — Google Gemini Integration

### 7.1 Backend AI Service Architecture

```php
// app/Services/GeminiService.php
class GeminiService
{
    private string $apiKey;
    private string $model = 'gemini-2.0-flash';
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function categorize(array $transactionData, Collection $categories): array
    public function getInsights(Collection $transactions, Collection $budgets): string
    public function chat(string $message, array $history, array $financialContext): StreamedResponse
}
```

### 7.2 Auto-Categorization

**Prompt Template:**

```
You are a financial transaction categorizer. Given a transaction description
and merchant name, classify it into one of the provided categories.

Transaction:
- Description: "{description}"
- Merchant: "{merchant_name}"
- Amount: {amount} {currency}
- Type: {type}

Available categories:
{categories_list}

Respond in JSON format only:
{
    "category_id": <id>,
    "category_name": "<name>",
    "confidence": <0.0-1.0>
}
```

**Processing:**

- Called on transaction creation if no category provided, or on-demand via AI button
- Result cached per merchant_name (Hive on Flutter, Cache on Laravel) to reduce API calls
- If same merchant seen before with high confidence → skip API call, use cached category

### 7.3 Spending Insights

**Trigger:** User opens Insights tab, or auto-generated weekly via scheduled command

**Context sent to Gemini:**

- Last 30/90 days of transactions (aggregated by category)
- Current budget utilization
- Income vs expense ratio
- Recurring transaction totals
- Comparison with previous period

**Prompt Template:**

```
You are a personal financial advisor. Analyze the user's spending data and provide
actionable savings suggestions. Be specific with numbers and percentages.

User's Financial Data (Last {period} days):
- Total Income: {total_income} {currency}
- Total Expenses: {total_expenses} {currency}
- Savings Rate: {savings_rate}%

Spending by Category:
{category_breakdown}

Budget Status:
{budget_status}

Recurring Expenses:
{recurring_list}

Previous Period Comparison:
{comparison}

Provide 3-5 specific, actionable insights in this JSON format:
{
    "insights": [
        {
            "title": "Short title",
            "description": "Detailed explanation with specific numbers",
            "potential_savings": <amount>,
            "priority": "high|medium|low",
            "category": "related category name"
        }
    ],
    "overall_health_score": <1-100>,
    "summary": "One paragraph overall assessment"
}
```

### 7.4 Financial Chatbot (SSE Streaming)

**Architecture:**

- Flutter sends message via `POST /ai/chat`
- Laravel streams response using SSE (Server-Sent Events)
- Dio on Flutter uses `responseType: ResponseType.stream` to receive chunks
- Chat history maintained in `ai_chat_sessions` / `ai_chat_messages` tables

**System Prompt:**

```
You are FinTrack AI, a friendly and knowledgeable personal financial advisor.
You have access to the user's financial data summarized below.
Always reference specific numbers from their data when giving advice.
Be encouraging but honest. Use the user's currency ({currency}) for amounts.
If asked about something outside personal finance, politely redirect.

User's Financial Summary:
- Monthly income: {monthly_income}
- Monthly expenses: {monthly_expenses}
- Active budgets: {budgets_summary}
- Top spending categories: {top_categories}
- Wallet balances: {wallet_balances}
- Recent transactions: {recent_transactions}
```

**SSE Response Format:**

```
data: {"type": "chunk", "content": "Based on your spending..."}
data: {"type": "chunk", "content": " I can see that..."}
data: {"type": "done", "session_id": 15}
```

**Flutter SSE handling:**

```dart
// In ChatNotifier (Riverpod)
final response = await dio.post(
    '/ai/chat',
    data: {'message': message, 'session_id': sessionId},
    options: Options(responseType: ResponseType.stream),
);
final stream = response.data.stream;
// Parse SSE lines, update state character by character
```

---

## 8. UI/UX Design System

### 8.1 Color Palette

**Primary (Teal/Emerald for finance trust feel):**

```dart
static const Color primarySeed = Color(0xFF0D9488); // Teal-600

// Generated via ColorScheme.fromSeed()
// Light:  primary=#0D9488, surface=#F8FFFE, onSurface=#1A1C1B
// Dark:   primary=#4FD8CB, surface=#1A1C1B, onSurface=#E1E3E1
```

**Semantic Colors:**

```dart
static const Color incomeGreen  = Color(0xFF22C55E); // Green-500
static const Color expenseRed   = Color(0xFFEF4444); // Red-500
static const Color warningAmber = Color(0xFFF59E0B); // Amber-500
static const Color budgetBlue   = Color(0xFF3B82F6); // Blue-500
```

**Category Colors (12 distinct):**

```dart
static const List<Color> categoryColors = [
    Color(0xFFEF4444), // Red
    Color(0xFFF97316), // Orange
    Color(0xFFF59E0B), // Amber
    Color(0xFF22C55E), // Green
    Color(0xFF14B8A6), // Teal
    Color(0xFF3B82F6), // Blue
    Color(0xFF6366F1), // Indigo
    Color(0xFF8B5CF6), // Violet
    Color(0xFFEC4899), // Pink
    Color(0xFF78716C), // Stone
    Color(0xFF06B6D4), // Cyan
    Color(0xFF84CC16), // Lime
];
```

### 8.2 Typography

```dart
// Using Google Fonts - Inter for UI, JetBrains Mono for amounts
static TextTheme textTheme = TextTheme(
    displayLarge:  GoogleFonts.inter(fontSize: 32, fontWeight: FontWeight.w700),
    headlineLarge: GoogleFonts.inter(fontSize: 24, fontWeight: FontWeight.w600),
    headlineMedium:GoogleFonts.inter(fontSize: 20, fontWeight: FontWeight.w600),
    titleLarge:    GoogleFonts.inter(fontSize: 18, fontWeight: FontWeight.w600),
    titleMedium:   GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.w500),
    bodyLarge:     GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.w400),
    bodyMedium:    GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.w400),
    bodySmall:     GoogleFonts.inter(fontSize: 12, fontWeight: FontWeight.w400),
    labelLarge:    GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.w500),
);

// Money amounts use monospace for alignment
static TextStyle moneyStyle = GoogleFonts.jetBrainsMono(
    fontSize: 20, fontWeight: FontWeight.w600,
);
```

### 8.3 Screen Map

```
Auth Flow:
  ├── SplashScreen (check token)
  ├── LoginScreen
  └── RegisterScreen

Main Flow (BottomNavigationBar — 4 tabs):
  ├── Tab 1: HomeScreen (Dashboard)
  │     ├── Total Balance Card
  │     ├── Income/Expense Summary (this month)
  │     ├── Budget Alerts Section
  │     ├── Spending by Category (PieChart)
  │     ├── Monthly Trend (LineChart)
  │     └── Recent Transactions (last 5)
  │
  ├── Tab 2: TransactionsScreen
  │     ├── Filter Bar (date, type, wallet, category, search)
  │     ├── Grouped by Date (StickyHeader)
  │     └── Transaction List Items
  │         └── TransactionDetailScreen
  │
  ├── Tab 3: BudgetsScreen
  │     ├── Budget Overview Cards (progress bars)
  │     └── BudgetDetailScreen
  │         └── Category transactions for this period
  │
  └── Tab 4: MoreScreen
        ├── AI Insights Section
        ├── AI Chat (Financial Advisor)
        ├── Wallets Management
        ├── Categories Management
        ├── Recurring Transactions
        ├── Export (PDF/CSV)
        ├── Currency Settings
        ├── Theme Toggle (Light/Dark/System)
        └── Profile & Logout

Floating Action Button (global):
  └── Add Transaction (Bottom Sheet or Full Screen)
```

### 8.4 Key UI Components

**Transaction List Item:**

```
┌────────────────────────────────────────────┐
│ 🍔  Food & Drink           - Rp 45,000   │
│     Mie Gacoan • BCA                      │
│     Today, 12:30 PM              🤖 AI    │
└────────────────────────────────────────────┘
```

- Category icon + color dot on left
- Category name + amount (red for expense, green for income) on top row
- Description + wallet name on second row
- Date/time on third row, AI badge if auto-categorized

**Budget Progress Card:**

```
┌────────────────────────────────────────────┐
│ 🍔  Food & Drink                          │
│ ████████████████░░░░  Rp 800K / Rp 1M    │
│ 80% used • 5 days left                    │
│ ⚠️ Approaching limit!                      │
└────────────────────────────────────────────┘
```

**Dashboard Balance Card:**

```
┌────────────────────────────────────────────┐
│         Total Balance                      │
│       Rp 15,420,000                        │
│                                            │
│   ↑ Rp 8,500,000    ↓ Rp 3,200,000       │
│     Income            Expense              │
│                                            │
│   Savings Rate: 62%                        │
└────────────────────────────────────────────┘
```

### 8.5 Charts (fl_chart)

1. **Pie Chart** — Expense breakdown by category (donut style, with center total)
2. **Line Chart** — Monthly income vs expense trend (last 6 months)
3. **Bar Chart** — Daily spending this week/month
4. **Progress Bars** — Budget utilization per category

---

## 9. Flutter Folder Structure

```
lib/
├── main.dart
├── app.dart                          # MaterialApp with theme + router
│
├── core/
│   ├── constants/
│   │   ├── app_constants.dart        # API base URL, timeouts, etc.
│   │   ├── app_colors.dart           # Color palette
│   │   └── app_typography.dart       # Text styles
│   ├── theme/
│   │   ├── app_theme.dart            # Light & dark ThemeData
│   │   └── theme_provider.dart       # Riverpod theme notifier
│   ├── network/
│   │   ├── dio_client.dart           # Dio instance + interceptors
│   │   ├── api_endpoints.dart        # Endpoint constants
│   │   ├── api_response.dart         # Generic response wrapper
│   │   └── api_exceptions.dart       # Custom exception classes
│   ├── router/
│   │   └── app_router.dart           # go_router configuration
│   ├── utils/
│   │   ├── currency_formatter.dart   # Format amounts with currency
│   │   ├── date_formatter.dart       # Date display helpers
│   │   ├── validators.dart           # Form validation
│   │   └── extensions.dart           # Dart extensions
│   └── widgets/
│       ├── app_loading.dart
│       ├── app_error.dart
│       ├── empty_state.dart
│       └── confirm_dialog.dart
│
├── data/
│   ├── models/
│   │   ├── user_model.dart
│   │   ├── wallet_model.dart
│   │   ├── category_model.dart
│   │   ├── transaction_model.dart
│   │   ├── budget_model.dart
│   │   ├── recurring_transaction_model.dart
│   │   ├── currency_model.dart
│   │   ├── chat_session_model.dart
│   │   ├── chat_message_model.dart
│   │   ├── ai_insight_model.dart
│   │   └── dashboard_model.dart
│   ├── repositories/
│   │   ├── auth_repository.dart
│   │   ├── wallet_repository.dart
│   │   ├── category_repository.dart
│   │   ├── transaction_repository.dart
│   │   ├── budget_repository.dart
│   │   ├── recurring_repository.dart
│   │   ├── ai_repository.dart
│   │   ├── export_repository.dart
│   │   └── currency_repository.dart
│   └── local/
│       ├── hive_service.dart         # Hive init + box management
│       ├── secure_storage_service.dart
│       └── cache_keys.dart
│
├── presentation/
│   ├── auth/
│   │   ├── providers/
│   │   │   └── auth_provider.dart
│   │   └── screens/
│   │       ├── splash_screen.dart
│   │       ├── login_screen.dart
│   │       └── register_screen.dart
│   ├── home/
│   │   ├── providers/
│   │   │   └── dashboard_provider.dart
│   │   ├── screens/
│   │   │   └── home_screen.dart
│   │   └── widgets/
│   │       ├── balance_card.dart
│   │       ├── income_expense_summary.dart
│   │       ├── spending_pie_chart.dart
│   │       ├── monthly_trend_chart.dart
│   │       ├── budget_alerts_section.dart
│   │       └── recent_transactions.dart
│   ├── transactions/
│   │   ├── providers/
│   │   │   └── transaction_provider.dart
│   │   ├── screens/
│   │   │   ├── transactions_screen.dart
│   │   │   ├── transaction_detail_screen.dart
│   │   │   └── add_transaction_screen.dart
│   │   └── widgets/
│   │       ├── transaction_list_item.dart
│   │       ├── transaction_filter_bar.dart
│   │       └── category_selector.dart
│   ├── budgets/
│   │   ├── providers/
│   │   │   └── budget_provider.dart
│   │   ├── screens/
│   │   │   ├── budgets_screen.dart
│   │   │   ├── budget_detail_screen.dart
│   │   │   └── add_budget_screen.dart
│   │   └── widgets/
│   │       └── budget_progress_card.dart
│   ├── more/
│   │   ├── screens/
│   │   │   └── more_screen.dart
│   │   ├── wallets/
│   │   │   ├── providers/
│   │   │   │   └── wallet_provider.dart
│   │   │   ├── screens/
│   │   │   │   ├── wallets_screen.dart
│   │   │   │   └── add_wallet_screen.dart
│   │   │   └── widgets/
│   │   │       └── wallet_card.dart
│   │   ├── categories/
│   │   │   ├── providers/
│   │   │   │   └── category_provider.dart
│   │   │   └── screens/
│   │   │       └── categories_screen.dart
│   │   ├── recurring/
│   │   │   ├── providers/
│   │   │   │   └── recurring_provider.dart
│   │   │   └── screens/
│   │   │       ├── recurring_screen.dart
│   │   │       └── add_recurring_screen.dart
│   │   ├── ai_insights/
│   │   │   ├── providers/
│   │   │   │   └── insights_provider.dart
│   │   │   └── screens/
│   │   │       └── insights_screen.dart
│   │   ├── ai_chat/
│   │   │   ├── providers/
│   │   │   │   └── chat_provider.dart
│   │   │   └── screens/
│   │   │       └── chat_screen.dart
│   │   └── export/
│   │       ├── providers/
│   │       │   └── export_provider.dart
│   │       └── screens/
│   │           └── export_screen.dart
│   └── shared/
│       └── main_shell.dart           # Scaffold with BottomNav + FAB
│
└── providers/
    └── providers.dart                # Global provider overrides
```

---

## 10. Laravel Folder Structure

```
fintrack-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/V1/
│   │   │       ├── AuthController.php
│   │   │       ├── WalletController.php
│   │   │       ├── CategoryController.php
│   │   │       ├── TransactionController.php
│   │   │       ├── BudgetController.php
│   │   │       ├── RecurringTransactionController.php
│   │   │       ├── AiController.php
│   │   │       ├── ExportController.php
│   │   │       ├── CurrencyController.php
│   │   │       └── DashboardController.php
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   │   ├── RegisterRequest.php
│   │   │   │   └── LoginRequest.php
│   │   │   ├── StoreTransactionRequest.php
│   │   │   ├── UpdateTransactionRequest.php
│   │   │   ├── StoreBudgetRequest.php
│   │   │   ├── StoreWalletRequest.php
│   │   │   ├── StoreRecurringRequest.php
│   │   │   └── AiChatRequest.php
│   │   ├── Resources/
│   │   │   ├── UserResource.php
│   │   │   ├── WalletResource.php
│   │   │   ├── CategoryResource.php
│   │   │   ├── TransactionResource.php
│   │   │   ├── BudgetResource.php
│   │   │   ├── RecurringTransactionResource.php
│   │   │   ├── ChatSessionResource.php
│   │   │   ├── CurrencyResource.php
│   │   │   └── DashboardResource.php
│   │   └── Middleware/
│   │       └── ForceJsonResponse.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Wallet.php
│   │   ├── Category.php
│   │   ├── Transaction.php
│   │   ├── Budget.php
│   │   ├── RecurringTransaction.php
│   │   ├── Currency.php
│   │   ├── AiChatSession.php
│   │   └── AiChatMessage.php
│   ├── Services/
│   │   ├── GeminiService.php
│   │   ├── TransactionService.php
│   │   ├── BudgetService.php
│   │   ├── CurrencyService.php
│   │   └── ExportService.php
│   ├── Jobs/
│   │   ├── BudgetAlertJob.php
│   │   ├── ProcessRecurringTransactionsJob.php
│   │   └── UpdateExchangeRatesJob.php
│   ├── Console/
│   │   └── Commands/
│   │       ├── ProcessRecurringTransactions.php
│   │       └── UpdateExchangeRates.php
│   ├── Observers/
│   │   └── TransactionObserver.php   # Update wallet balance, check budgets
│   └── Enums/
│       ├── TransactionType.php
│       ├── WalletType.php
│       ├── BudgetPeriod.php
│       └── RecurringFrequency.php
├── database/
│   ├── migrations/
│   │   ├── 0001_create_currencies_table.php
│   │   ├── 0002_create_users_table.php (modify default)
│   │   ├── 0003_create_wallets_table.php
│   │   ├── 0004_create_categories_table.php
│   │   ├── 0005_create_transactions_table.php
│   │   ├── 0006_create_budgets_table.php
│   │   ├── 0007_create_recurring_transactions_table.php
│   │   ├── 0008_create_ai_chat_sessions_table.php
│   │   └── 0009_create_ai_chat_messages_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── CurrencySeeder.php
│       └── CategorySeeder.php
├── routes/
│   └── api.php                       # All API v1 routes
├── config/
│   └── services.php                  # Gemini API key config
└── .env                              # GEMINI_API_KEY, DB config
```

---

## 11. Implementation Roadmap

### Phase 1: Foundation (Week 1)

**Backend:**

- Laravel project init with PostgreSQL config
- Sanctum setup + auth routes
- Database migrations (all tables)
- Seeders (currencies, default categories)
- User model + auth controllers
- ForceJsonResponse middleware
- API Resource classes (User, Currency, Category)

**Flutter:**

- Flutter project init + dependencies
- Core folder structure (Clean Architecture)
- Theme system (light/dark) + Riverpod theme provider
- Dio client with auth interceptor
- go_router setup with auth redirect
- Hive + flutter_secure_storage init
- Auth screens (Splash, Login, Register)
- Auth provider (Riverpod)
- Main shell with BottomNav placeholder

### Phase 2: Core CRUD (Week 2)

**Backend:**

- Wallet CRUD controller + request validation + resource
- Category CRUD controller (system vs custom)
- Transaction CRUD with filters, pagination, search
- TransactionObserver (wallet balance update)
- TransactionService (transfer logic)
- Dashboard aggregation endpoint

**Flutter:**

- Wallet management (list, add, edit, delete)
- Category management screens
- Transaction list with filters + search
- Add/edit transaction form
- Transaction detail screen
- Wallet & transaction providers (Riverpod)
- Currency formatter utility

### Phase 3: Budgets & Recurring (Week 3)

**Backend:**

- Budget CRUD + overview endpoint with spending calculation
- BudgetService (period calculation, spending aggregation)
- BudgetAlertJob (queue job)
- Recurring transaction CRUD
- ProcessRecurringTransactions command + scheduler
- Skip/process-now endpoints

**Flutter:**

- Budget screens (list with progress, detail, add/edit)
- Budget progress card widget
- Recurring transaction screens (list, add, edit)
- Budget & recurring providers
- Home screen dashboard with real data
- Charts (PieChart, LineChart, BarChart via fl_chart)

### Phase 4: AI Integration (Week 4)

**Backend:**

- GeminiService class (categorize, insights, chat)
- Auto-categorization endpoint
- Spending insights endpoint (with financial context builder)
- Chat endpoint with SSE streaming
- Chat session/message management
- Rate limiting on AI endpoints

**Flutter:**

- AI categorize button on add-transaction screen
- Insights screen with AI-generated suggestions
- Chat screen with SSE streaming (message bubbles, typing indicator)
- Chat session management
- AI providers (categorize, insights, chat)

### Phase 5: Multi-Currency, Export & Polish (Week 5)

**Backend:**

- CurrencyService + exchange rate update command
- ExportService (CSV generation, PDF via dompdf)
- Export endpoints
- API rate limiting + final validation review

**Flutter:**

- Multi-currency display + conversion in dashboard
- Currency settings screen
- Export screen (choose format, date range, filters)
- File download + share handling
- Dark mode toggle in settings
- Loading states, error states, empty states
- Pull-to-refresh on list screens
- Smooth page transitions + micro-animations
- Final QA + edge case handling

---

## 12. Claude Code CLI Prompts

### Phase 1: Foundation

#### Prompt 1.1 — Laravel Project Init + Database Setup

```
Initialize a new Laravel 13 project called "fintrack-api".

1. Configure PostgreSQL in .env:
   - DB_CONNECTION=pgsql
   - DB_HOST=127.0.0.1
   - DB_PORT=5432
   - DB_DATABASE=fintrack
   - DB_USERNAME=postgres
   - DB_PASSWORD=postgres

2. Install Laravel Sanctum (it comes pre-installed in Laravel 13, just publish config):
   - php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

3. Add HasApiTokens trait to User model.

4. Create ALL database migrations in this exact order:
   a. create_currencies_table: code (VARCHAR 3 PK), name (VARCHAR 100), symbol (VARCHAR 10), decimal_places (SMALLINT default 2), exchange_rate_to_usd (DECIMAL 20,10), updated_at
   b. Modify users table migration: add default_currency_code (VARCHAR 3, default 'IDR', FK to currencies.code), avatar_url (TEXT nullable)
   c. create_wallets_table: id (bigIncrements), user_id (FK CASCADE), name (VARCHAR 255), type (VARCHAR 50: bank/e_wallet/cash/credit_card), currency_code (FK currencies), balance (DECIMAL 18,2 default 0), icon (VARCHAR 50), color (VARCHAR 7), is_active (BOOLEAN default true), timestamps. Add UNIQUE(user_id, name), INDEX on user_id
   d. create_categories_table: id (bigIncrements), user_id (FK CASCADE nullable — NULL = system), name (VARCHAR 100), type (VARCHAR 10: income/expense CHECK), icon (VARCHAR 50), color (VARCHAR 7), is_system (BOOLEAN default false), parent_id (self-ref FK SET NULL), timestamps. INDEX on user_id, type
   e. create_transactions_table: id (bigIncrements), user_id (FK CASCADE), wallet_id (FK CASCADE), category_id (FK SET NULL), type (VARCHAR 10: income/expense/transfer CHECK), amount (DECIMAL 18,2), currency_code (FK currencies), description (TEXT), merchant_name (VARCHAR 255), transaction_date (TIMESTAMPTZ), is_recurring (BOOLEAN default false), recurring_id (FK SET NULL), ai_categorized (BOOLEAN default false), ai_confidence (DECIMAL 3,2), notes (TEXT), attachment_url (TEXT), timestamps. INDEX on (user_id, transaction_date DESC), wallet_id, category_id, type, recurring_id
   f. create_budgets_table: id (bigIncrements), user_id (FK CASCADE), category_id (FK CASCADE), amount (DECIMAL 18,2), currency_code (FK currencies), period (VARCHAR 10: weekly/monthly/yearly CHECK), alert_threshold (DECIMAL 3,2 default 0.80), start_date (DATE), end_date (DATE nullable), is_active (BOOLEAN default true), timestamps. UNIQUE(user_id, category_id, period), INDEX on user_id
   g. create_recurring_transactions_table: id (bigIncrements), user_id (FK CASCADE), wallet_id (FK CASCADE), category_id (FK SET NULL), type (VARCHAR 10: income/expense CHECK), amount (DECIMAL 18,2), currency_code (FK currencies), description (TEXT), merchant_name (VARCHAR 255), frequency (VARCHAR 20: daily/weekly/biweekly/monthly/quarterly/yearly CHECK), next_due_date (DATE), last_processed (DATE nullable), is_active (BOOLEAN default true), auto_create (BOOLEAN default true), timestamps. INDEX on user_id, partial INDEX on next_due_date WHERE is_active=true
   h. create_ai_chat_sessions_table: id (bigIncrements), user_id (FK CASCADE), title (VARCHAR 255 nullable), timestamps. INDEX on user_id
   i. create_ai_chat_messages_table: id (bigIncrements), session_id (FK CASCADE), role (VARCHAR 10: user/model CHECK), content (TEXT), created_at. INDEX on session_id

5. Create seeders:
   a. CurrencySeeder: Seed at least these currencies with approximate exchange_rate_to_usd:
      - IDR (Rp, 0 decimal places, rate ~15500)
      - USD ($, 2 decimal places, rate 1.0)
      - EUR (€, 2 decimal places, rate ~0.92)
      - GBP (£, 2 decimal places, rate ~0.79)
      - JPY (¥, 0 decimal places, rate ~150)
      - SGD (S$, 2 decimal places, rate ~1.34)
      - MYR (RM, 2 decimal places, rate ~4.47)
   b. CategorySeeder: Seed system default categories (is_system=true, user_id=NULL):
      Expense: Food & Drink (🍔, #EF4444), Transportation (🚗, #F97316), Shopping (🛍️, #F59E0B), Bills & Utilities (📄, #3B82F6), Entertainment (🎬, #6366F1), Health (💊, #22C55E), Education (📚, #8B5CF6), Groceries (🛒, #14B8A6), Subscriptions (📱, #EC4899), Transfer Out (↗️, #78716C), Other Expense (📦, #06B6D4)
      Income: Salary (💰, #22C55E), Freelance (💻, #3B82F6), Investment (📈, #F59E0B), Gift (🎁, #EC4899), Transfer In (↙️, #78716C), Other Income (💵, #14B8A6)

6. Create Enums (app/Enums/):
   - TransactionType: INCOME, EXPENSE, TRANSFER
   - WalletType: BANK, E_WALLET, CASH, CREDIT_CARD
   - BudgetPeriod: WEEKLY, MONTHLY, YEARLY
   - RecurringFrequency: DAILY, WEEKLY, BIWEEKLY, MONTHLY, QUARTERLY, YEARLY

7. Create ForceJsonResponse middleware in app/Http/Middleware/ that sets Accept: application/json on all requests. Register it in bootstrap/app.php for the 'api' middleware group.

8. Run migrations + seeders and verify tables exist.
```

#### Prompt 1.2 — Laravel Auth + User API

```
In the fintrack-api Laravel project, implement authentication with Sanctum:

1. Create Form Requests (app/Http/Requests/Auth/):
   - RegisterRequest: name (required|string|max:255), email (required|email|unique:users), password (required|string|min:8|confirmed), default_currency_code (sometimes|string|size:3|exists:currencies,code)
   - LoginRequest: email (required|email), password (required|string)

2. Create API Resources (app/Http/Resources/):
   - UserResource: id, name, email, default_currency_code, avatar_url, created_at

3. Create AuthController (app/Http/Controllers/Api/V1/):
   - register(): Create user, create Sanctum token, return UserResource + token
   - login(): Validate credentials, create token, return UserResource + token
   - logout(): Revoke current token (via $request->user()->currentAccessToken()->delete())
   - user(): Return authenticated user (UserResource)
   - updateProfile(): Update name, default_currency_code, avatar_url
   - updatePassword(): Validate current_password, update to new password

4. Define routes in routes/api.php:
   - Route::prefix('v1')->group(function() {
       Route::prefix('auth')->group(function() {
         Route::post('/register', [AuthController::class, 'register']);
         Route::post('/login', [AuthController::class, 'login']);
         Route::middleware('auth:sanctum')->group(function() {
           Route::post('/logout', [AuthController::class, 'logout']);
           Route::get('/user', [AuthController::class, 'user']);
           Route::put('/user', [AuthController::class, 'updateProfile']);
           Route::put('/password', [AuthController::class, 'updatePassword']);
         });
       });
     });

5. Token response format:
   {
     "data": {
       "user": { ...UserResource },
       "token": "plainTextToken",
       "token_type": "Bearer"
     }
   }

6. Error response format (consistent across all endpoints):
   {
     "message": "Error description",
     "errors": { "field": ["validation message"] }  // only for validation errors
   }

7. Create a base ApiController with helper methods:
   - successResponse($data, $message = 'Success', $code = 200)
   - errorResponse($message, $code = 400)
   - Use these consistently across all controllers.
```

#### Prompt 1.3 — Flutter Project Init + Core Setup

```
Create a new Flutter project called "fintrack" and set up the foundation:

1. Run: flutter create fintrack --org com.fintrack
2. Add these dependencies to pubspec.yaml:
   dependencies:
     flutter_riverpod: ^2.5.1
     riverpod_annotation: ^2.3.5
     go_router: ^14.2.0
     dio: ^5.4.0
     flutter_secure_storage: ^9.2.2
     hive: ^2.2.3
     hive_flutter: ^1.1.0
     fl_chart: ^0.68.0
     google_fonts: ^6.2.1
     intl: ^0.19.0
     freezed_annotation: ^2.4.1
     json_annotation: ^4.9.0
     shimmer: ^3.0.0
     gap: ^3.0.1
     flutter_svg: ^2.0.10+1
     path_provider: ^2.1.3
     share_plus: ^9.0.0
     open_file: ^3.5.3
     cached_network_image: ^3.3.1
   dev_dependencies:
     flutter_test: sdk: flutter
     flutter_lints: ^4.0.0
     build_runner: ^2.4.9
     riverpod_generator: ^2.4.0
     freezed: ^2.5.2
     json_serializable: ^6.7.1

3. Create the complete folder structure as defined in Section 9 of the PRD.
   Create all folders and placeholder files with TODO comments.

4. Implement core/constants/app_constants.dart:
   - static const String apiBaseUrl = 'http://10.0.2.2:8000/api/v1'; // Android emulator
   - static const Duration connectTimeout = Duration(seconds: 30);
   - static const Duration receiveTimeout = Duration(seconds: 30);
   - static const int paginationLimit = 20;

5. Implement core/constants/app_colors.dart:
   - primarySeed = Color(0xFF0D9488)
   - incomeGreen = Color(0xFF22C55E)
   - expenseRed = Color(0xFFEF4444)
   - warningAmber = Color(0xFFF59E0B)
   - budgetBlue = Color(0xFF3B82F6)
   - categoryColors list (12 colors as defined in PRD Section 8.1)

6. Implement core/constants/app_typography.dart:
   - Using GoogleFonts.inter for UI text
   - Using GoogleFonts.jetBrainsMono for money amounts
   - Define all TextStyle constants matching PRD Section 8.2

7. Implement core/theme/app_theme.dart:
   - lightTheme: ThemeData using ColorScheme.fromSeed(seedColor: AppColors.primarySeed, brightness: Brightness.light)
   - darkTheme: ThemeData using ColorScheme.fromSeed(seedColor: AppColors.primarySeed, brightness: Brightness.dark)
   - Customize: AppBarTheme (centered title, elevation 0), CardTheme (rounded 16), InputDecorationTheme (OutlineInputBorder rounded 12), ElevatedButtonTheme (rounded 12, min height 48)

8. Implement core/theme/theme_provider.dart:
   - ThemeNotifier extends StateNotifier<ThemeMode>
   - Reads/writes to Hive box 'settings' key 'theme_mode'
   - Methods: setTheme(ThemeMode), toggleTheme()

9. Implement data/local/hive_service.dart:
   - initHive() — call in main.dart
   - Open boxes: 'settings', 'cache'
   - Methods: getValue, setValue, clearAll

10. Implement data/local/secure_storage_service.dart:
    - saveToken(String token)
    - getToken() → String?
    - deleteToken()
    - saveUser(String userJson)
    - getUser() → String?
    - deleteAll()

11. Implement core/network/dio_client.dart:
    - Create Dio instance with baseUrl, timeouts
    - AuthInterceptor: onRequest → attach Bearer token from SecureStorage
    - ErrorInterceptor: onError → handle 401 (clear token, redirect to login), format error messages
    - LogInterceptor for debug mode

12. Implement main.dart:
    - WidgetsFlutterBinding.ensureInitialized()
    - await HiveService.initHive()
    - runApp(ProviderScope(child: FinTrackApp()))

13. Implement app.dart (FinTrackApp):
    - ConsumerWidget that reads themeProvider
    - MaterialApp.router with goRouter, light/dark theme, title "FinTrack"

14. Implement core/router/app_router.dart:
    - GoRouter with redirect logic: if no token → /login, if token → /home
    - Routes: /splash, /login, /register, /home (ShellRoute with BottomNav tabs)
    - ShellRoute children: /home, /transactions, /budgets, /more
    - Sub-routes: /transactions/add, /transactions/:id, /budgets/add, /budgets/:id, etc.

15. Implement presentation/shared/main_shell.dart:
    - Scaffold with BottomNavigationBar (4 tabs: Home, Transactions, Budgets, More)
    - FloatingActionButton (centered, docked) for Add Transaction
    - Icons: home_rounded, receipt_long_rounded, account_balance_wallet_rounded, more_horiz_rounded

16. Create placeholder screens for all routes that just show the screen name centered.
```

#### Prompt 1.4 — Flutter Auth Flow

```
In the fintrack Flutter project, implement the complete authentication flow:

1. Create data/models/user_model.dart:
   - Use @freezed annotation
   - Fields: int id, String name, String email, String defaultCurrencyCode, String? avatarUrl, DateTime? createdAt
   - Include fromJson/toJson

2. Create data/repositories/auth_repository.dart:
   - AuthRepository class that takes DioClient
   - register({name, email, password, passwordConfirmation, defaultCurrencyCode}) → returns {user, token}
   - login({email, password}) → returns {user, token}
   - logout() → POST /auth/logout
   - getUser() → GET /auth/user
   - updateProfile({name, defaultCurrencyCode}) → PUT /auth/user
   - updatePassword({currentPassword, newPassword, newPasswordConfirmation}) → PUT /auth/password

3. Create presentation/auth/providers/auth_provider.dart:
   - AuthState: initial, loading, authenticated(UserModel), unauthenticated, error(String)
   - AuthNotifier extends StateNotifier<AuthState>:
     - Dependencies: AuthRepository, SecureStorageService
     - checkAuth(): Read token from secure storage, if exists call getUser(), set state
     - login(email, password): Call repo, save token + user, set authenticated
     - register(...): Call repo, save token + user, set authenticated
     - logout(): Call repo, clear storage, set unauthenticated
     - updateProfile/updatePassword
   - Provide as StateNotifierProvider

4. Implement presentation/auth/screens/splash_screen.dart:
   - Shows app logo (Text "FinTrack" with primarySeed color, large font) centered
   - On initState, call authNotifier.checkAuth()
   - Listen to auth state changes → navigate to /login or /home

5. Implement presentation/auth/screens/login_screen.dart:
   - SafeArea with SingleChildScrollView
   - App logo at top
   - Email TextField with email keyboard type + validation
   - Password TextField with obscure toggle (eye icon)
   - "Login" ElevatedButton (full width, height 48)
   - "Don't have an account? Register" TextButton at bottom
   - Loading state: disable button, show CircularProgressIndicator
   - Error state: show SnackBar with error message
   - Form validation before submit

6. Implement presentation/auth/screens/register_screen.dart:
   - Similar to login but with: name, email, password, confirm password fields
   - Currency dropdown (hardcoded for now: IDR, USD, EUR)
   - "Register" button
   - "Already have an account? Login" at bottom
   - Same loading/error handling as login

7. Update app_router.dart:
   - redirect: check auth state from provider
   - If unauthenticated and trying to access protected route → /login
   - If authenticated and on /login or /register → /home

8. All forms must use TextFormField with proper:
   - TextInputType (email, text, visiblePassword)
   - TextInputAction (next, done)
   - Validators (not empty, email format, min 8 chars for password, passwords match)
   - AutofillHints where applicable
```

### Phase 2: Core CRUD

#### Prompt 2.1 — Laravel Wallet + Category + Transaction CRUD

```
In fintrack-api, implement the core CRUD controllers:

1. Create Eloquent Models with relationships, fillable, casts:
   - Wallet: belongsTo User, belongsTo Currency, hasMany Transactions. Casts: balance → decimal:2, is_active → boolean
   - Category: belongsTo User (nullable), hasMany Transactions, hasMany Budgets, belongsTo parent Category, hasMany children Categories. Scope: scopeForUser($query, $userId) — returns system + user's custom. Casts: is_system → boolean
   - Transaction: belongsTo User, Wallet, Category, RecurringTransaction, Currency. Casts: amount → decimal:2, transaction_date → datetime, ai_categorized → boolean, ai_confidence → decimal:2
   - Currency: hasMany Wallets, hasMany Transactions

2. Create Form Requests:
   - StoreWalletRequest: name (required|string|max:255), type (required|in:bank,e_wallet,cash,credit_card), currency_code (required|size:3|exists:currencies,code), balance (sometimes|numeric|min:0), icon (sometimes|string|max:50), color (sometimes|string|max:7)
   - StoreTransactionRequest: wallet_id (required|exists:wallets,id — must belong to auth user), category_id (sometimes|exists:categories,id), type (required|in:income,expense,transfer), amount (required|numeric|min:0.01), description (sometimes|string|max:500), merchant_name (sometimes|string|max:255), transaction_date (required|date), notes (sometimes|string|max:1000). For transfer type: also require destination_wallet_id
   - UpdateTransactionRequest: same fields but all 'sometimes'
   - StoreBudgetRequest: category_id (required|exists:categories,id), amount (required|numeric|min:0.01), period (required|in:weekly,monthly,yearly), alert_threshold (sometimes|numeric|between:0.5,1.0), start_date (required|date)

3. Create API Resources:
   - WalletResource: id, name, type, currency (code + symbol), balance, icon, color, is_active, created_at
   - CategoryResource: id, name, type, icon, color, is_system, parent_id, created_at
   - TransactionResource: id, type, amount, currency, description, merchant_name, transaction_date, wallet (WalletResource), category (CategoryResource), ai_categorized, ai_confidence, notes, is_recurring, created_at

4. Create WalletController:
   - index: List user's wallets ordered by name, include currency
   - store: Create wallet for auth user
   - show: Get wallet (authorize ownership)
   - update: Update wallet (authorize ownership, cannot change currency_code)
   - destroy: Soft check — if has transactions, warn or block. Otherwise delete.

5. Create CategoryController:
   - index: List system categories + user's custom categories, filterable by type (income/expense)
   - store: Create custom category (user_id = auth user, is_system = false)
   - update: Only user's custom categories (not system)
   - destroy: Only user's custom categories. If has transactions, reassign to "Other" category.

6. Create TransactionController:
   - index: Paginated list with filters (wallet_id, category_id, type, start_date, end_date, search on description/merchant_name). Eager load wallet, category. Sort by transaction_date desc default.
   - store: Create transaction. Use DB::transaction for atomicity. Update wallet balance (income: +amount, expense: -amount). For transfer: create two transactions (expense from source, income to destination with "Transfer" category).
   - show: Get with relationships (authorize ownership)
   - update: Reverse old balance impact, apply new. Update transaction fields.
   - destroy: Reverse balance impact. Delete transaction.
   - summary: Return income/expense totals for date range, grouped by category

7. Create TransactionService (app/Services/):
   - createTransaction(array $data, User $user): handles regular + transfer logic
   - updateTransaction(Transaction $tx, array $data): handles balance recalculation
   - deleteTransaction(Transaction $tx): handles balance reversal
   - getSummary(User $user, ?Carbon $startDate, ?Carbon $endDate): aggregated data

8. Create TransactionObserver:
   - After creating/updating/deleting a transaction, dispatch BudgetAlertJob if the transaction's category has an active budget

9. Register all routes under Route::middleware('auth:sanctum')->prefix('v1') group.
```

#### Prompt 2.2 — Flutter Wallet + Category Screens

```
In the fintrack Flutter project, implement wallet and category management:

1. Create data/models/wallet_model.dart (@freezed):
   - int id, String name, String type, String currencyCode, String currencySymbol, double balance, String? icon, String? color, bool isActive, DateTime? createdAt

2. Create data/models/category_model.dart (@freezed):
   - int id, String name, String type, String icon, String color, bool isSystem, int? parentId, DateTime? createdAt

3. Create data/repositories/wallet_repository.dart:
   - getWallets() → List<WalletModel>
   - createWallet({name, type, currencyCode, balance, icon, color}) → WalletModel
   - updateWallet(id, {name, type, balance, icon, color}) → WalletModel
   - deleteWallet(id) → void

4. Create data/repositories/category_repository.dart:
   - getCategories({String? type}) → List<CategoryModel>
   - createCategory({name, type, icon, color}) → CategoryModel
   - updateCategory(id, {name, icon, color}) → CategoryModel
   - deleteCategory(id) → void

5. Create presentation/more/wallets/providers/wallet_provider.dart:
   - WalletListState: loading, loaded(List<WalletModel>), error(String)
   - WalletListNotifier: fetchWallets, addWallet, editWallet, removeWallet

6. Create presentation/more/categories/providers/category_provider.dart:
   - CategoryListState: loading, loaded(List<CategoryModel>), error(String)
   - CategoryListNotifier: fetchCategories, addCategory, editCategory, removeCategory

7. Implement wallets_screen.dart:
   - AppBar title "Wallets" with Add button (icon)
   - ListView of wallet cards showing: icon, name, type badge, balance with currency symbol, color indicator
   - Swipe to delete with confirmation dialog
   - Pull to refresh
   - Empty state when no wallets

8. Implement add_wallet_screen.dart:
   - Form fields: name (TextField), type (DropdownButtonFormField: Bank, E-Wallet, Cash, Credit Card), currency (dropdown), initial balance (numeric), icon picker (grid of common icons), color picker (grid of preset colors)
   - Save button in AppBar
   - Validation: name required, type required, currency required

9. Implement categories_screen.dart:
   - SegmentedButton to toggle income/expense
   - List showing: icon + color dot, name, "System" badge for system categories
   - System categories: show but disable edit/delete
   - Custom categories: swipe to delete, tap to edit
   - FAB to add custom category

10. Implement core/utils/currency_formatter.dart:
    - formatAmount(double amount, String currencyCode, String symbol, {int? decimalPlaces})
    - Returns formatted string like "Rp 15.420.000" or "$1,234.56"
    - Use intl NumberFormat with locale based on currency

11. Run build_runner to generate freezed + json_serializable files:
    dart run build_runner build --delete-conflicting-outputs
```

#### Prompt 2.3 — Flutter Transaction Screens

```
In the fintrack Flutter project, implement transaction management:

1. Create data/models/transaction_model.dart (@freezed):
   - int id, String type, double amount, String currencyCode, String? description, String? merchantName, DateTime transactionDate, WalletModel? wallet, CategoryModel? category, bool aiCategorized, double? aiConfidence, String? notes, String? attachmentUrl, bool isRecurring, DateTime? createdAt

2. Create data/repositories/transaction_repository.dart:
   - getTransactions({walletId, categoryId, type, startDate, endDate, search, sortBy, sortOrder, page, perPage}) → paginated response with List<TransactionModel> + meta
   - createTransaction({walletId, categoryId, type, amount, description, merchantName, transactionDate, notes, destinationWalletId}) → TransactionModel
   - getTransaction(id) → TransactionModel
   - updateTransaction(id, {...}) → TransactionModel
   - deleteTransaction(id) → void
   - getSummary({startDate, endDate}) → Map with income/expense totals + category breakdown

3. Create presentation/transactions/providers/transaction_provider.dart:
   - TransactionListNotifier: manages paginated list with infinite scroll
     - State: {transactions: List, isLoading, isLoadingMore, hasMore, filters: TransactionFilters}
     - fetchTransactions(reset: false): fetch page, append to list
     - applyFilters(TransactionFilters): reset and refetch
     - deleteTransaction(id): remove from list + call API
   - TransactionFilters (@freezed): walletId, categoryId, type, startDate, endDate, search

4. Implement transactions_screen.dart:
   - SliverAppBar with search bar (expandable)
   - Filter chips row below AppBar: Date Range, Type (All/Income/Expense), Wallet, Category
   - Tapping a filter chip opens BottomSheet with options
   - Transaction list grouped by date (use SliverStickyHeader pattern):
     - Date header: "Today", "Yesterday", "Mon, 24 Mar 2026", etc.
     - TransactionListItem tiles under each date
   - Infinite scroll: load more when near bottom
   - Pull to refresh
   - Empty state with illustration when no transactions
   - Shimmer loading placeholders while fetching

5. Implement transaction_list_item.dart:
   - Leading: Category icon in colored circle
   - Title: Category name
   - Subtitle: description/merchant_name • wallet name
   - Trailing: formatted amount (red for expense, green for income) + AI badge icon if ai_categorized
   - Trailing bottom: formatted date/time
   - onTap → navigate to detail

6. Implement transaction_detail_screen.dart:
   - Full details: amount (large, colored), type badge, category with icon, wallet, date/time, description, merchant, notes
   - If ai_categorized: show "Categorized by AI (85% confidence)" chip
   - AppBar actions: Edit (pencil icon), Delete (trash icon with confirmation)

7. Implement add_transaction_screen.dart:
   - SegmentedButton at top: Income / Expense / Transfer
   - Amount input: large centered text with currency symbol, numeric keyboard
   - Wallet selector: horizontal scrollable chips or dropdown
   - Category selector: grid bottom sheet with category icons (filtered by income/expense type)
   - Date picker: defaults to today, tap to change
   - Description TextField
   - Merchant name TextField
   - Notes TextField (multiline, expandable)
   - If Transfer: show source wallet + destination wallet selectors
   - "AI Categorize" button next to category selector (magic wand icon) — placeholder for Phase 4
   - Save button: validate required fields, show loading, navigate back on success, show error snackbar on failure

8. Implement transaction_filter_bar.dart:
   - Row of FilterChip widgets
   - Date range: shows "This Month" by default, tap to open DateRangePicker
   - Type: All / Income / Expense
   - Wallet: shows wallet name when selected
   - Category: shows category name when selected
   - Active filters show filled chip style, inactive show outlined
   - "Clear filters" button when any filter active
```

### Phase 3: Budgets & Recurring

#### Prompt 3.1 — Laravel Budget + Recurring + Dashboard

```
In fintrack-api, implement budgets, recurring transactions, and dashboard:

1. Create BudgetController:
   - index: List user's active budgets with current period spending calculated. Each budget includes: budget data + spent_amount (SUM of transactions in category for current period) + remaining + percentage_used
   - store: Create budget. Validate no duplicate (user_id + category_id + period)
   - show: Budget detail with transactions in current period
   - update: Update amount, alert_threshold, period
   - destroy: Delete budget
   - overview: All budgets with spending progress for dashboard

2. Create BudgetService:
   - getCurrentPeriodDates(Budget $budget): returns [start, end] Carbon dates based on period + start_date
   - calculateSpending(Budget $budget): SUM transactions where category_id matches, transaction_date within period, type = 'expense'
   - checkBudgetAlert(Budget $budget): if spending >= budget * alert_threshold → return true
   - getBudgetResource includes: id, category (CategoryResource), amount, currency, period, spent, remaining, percentage, alert_threshold, is_over_budget, start_date, period_start, period_end

3. Create BudgetResource:
   - Include computed fields: spent, remaining, percentage_used, period_start, period_end, is_over_budget, days_remaining

4. Create BudgetAlertJob (app/Jobs/):
   - Accepts Budget model
   - Checks if spending crossed alert_threshold
   - For now, just logs the alert (can integrate push notifications later)
   - Prevent duplicate alerts: use cache key per budget per period

5. Create RecurringTransactionController:
   - Full CRUD
   - skip(id): advance next_due_date to next occurrence without creating transaction
   - processNow(id): manually trigger processing of one occurrence

6. Create RecurringTransactionResource:
   - Include: id, wallet (WalletResource), category (CategoryResource), type, amount, currency, description, merchant_name, frequency, next_due_date, last_processed, is_active, auto_create

7. Create ProcessRecurringTransactions console command:
   - Find all active recurring where next_due_date <= today and auto_create = true
   - For each: create Transaction, update wallet balance, advance next_due_date, update last_processed
   - Log count processed
   - Register in schedule: $schedule->command('recurring:process')->daily();

8. Create DashboardController:
   - dashboard(): Returns aggregated data:
     a. total_balance: SUM of all active wallet balances (converted to user's default_currency)
     b. month_summary: { income: total, expense: total, savings_rate: % } for current month
     c. category_breakdown: top 6 expense categories this month with amounts + percentages
     d. budget_alerts: budgets where percentage_used >= alert_threshold (use BudgetService)
     e. recent_transactions: last 5 transactions (TransactionResource)
     f. monthly_trend: last 6 months income vs expense totals
     g. wallet_balances: all wallets with name, balance, currency

9. Register routes:
   - Resource routes for budgets, recurring
   - POST recurring/{id}/skip, POST recurring/{id}/process
   - GET dashboard
```

#### Prompt 3.2 — Flutter Budget + Recurring + Dashboard Screens

```
In the fintrack Flutter project, implement budgets, recurring transactions, and dashboard:

1. Create data/models/budget_model.dart (@freezed):
   - int id, CategoryModel category, double amount, String currencyCode, String period, double alertThreshold, double spent, double remaining, double percentageUsed, bool isOverBudget, String periodStart, String periodEnd, int daysRemaining, DateTime startDate

2. Create data/models/recurring_transaction_model.dart (@freezed):
   - int id, WalletModel wallet, CategoryModel? category, String type, double amount, String currencyCode, String description, String? merchantName, String frequency, DateTime nextDueDate, DateTime? lastProcessed, bool isActive, bool autoCreate

3. Create data/models/dashboard_model.dart (@freezed):
   - double totalBalance, String defaultCurrency, MonthSummary monthSummary, List<CategoryBreakdown> categoryBreakdown, List<BudgetModel> budgetAlerts, List<TransactionModel> recentTransactions, List<MonthlyTrend> monthlyTrend, List<WalletModel> walletBalances
   - MonthSummary: double income, double expense, double savingsRate
   - CategoryBreakdown: String name, String icon, String color, double amount, double percentage
   - MonthlyTrend: String month, double income, double expense

4. Create repositories: budget_repository.dart, recurring_repository.dart
   - Budget: getBudgets, getBudgetOverview, createBudget, updateBudget, deleteBudget
   - Recurring: getRecurring, createRecurring, updateRecurring, deleteRecurring, skipNext, processNow

5. Create providers: budget_provider.dart, recurring_provider.dart, dashboard_provider.dart

6. Implement home_screen.dart (Dashboard):
   - RefreshIndicator wrapping CustomScrollView
   - SliverToBoxAdapter sections:
     a. BalanceCard: large centered total balance, income/expense summary below with colored arrows, savings rate percentage
     b. Budget Alerts Section: horizontal scrollable cards for budgets near/over limit. Each shows category icon, name, progress bar (colored by status), "Rp 800K / Rp 1M" text. Tap → navigate to budget detail
     c. Spending by Category: Donut PieChart (fl_chart) showing top 6 expense categories. Center of donut shows total. Legend below with color dots + category names + amounts
     d. Monthly Trend: LineChart (fl_chart) with 6 months. Two lines: income (green) and expense (red). X-axis: month names. Y-axis: amounts. Touch tooltips showing exact values
     e. Recent Transactions: last 5 TransactionListItem widgets. "See All" button → navigates to transactions tab

7. Implement budget widgets:
   - budget_progress_card.dart: Category icon + name, LinearProgressIndicator (colored green/yellow/orange/red based on percentage), "spent / limit" text, "X% used • Y days left" subtitle, warning icon if near limit
   - budgets_screen.dart: ListView of BudgetProgressCards, FAB to add budget
   - budget_detail_screen.dart: Large progress circle at top, category info, period dates, list of transactions in this category for current period
   - add_budget_screen.dart: Category selector (only expense categories), amount field, period dropdown (Weekly/Monthly/Yearly), alert threshold slider (50%-100%, default 80%), start date picker

8. Implement recurring screens:
   - recurring_screen.dart: ListView grouped by active/inactive. Each item shows: category icon, description, formatted amount, frequency badge, next due date. Swipe actions: Skip, Process Now, Delete
   - add_recurring_screen.dart: Similar to add_transaction but with frequency dropdown and no date picker (replaced by start date)

9. Chart implementation details for fl_chart:
   - PieChart: Use PieChartSectionData with color from category, radius 80, showTitle false. Center widget with total amount. Touch: expand touched section radius to 90
   - LineChart: Use FlSpot for data points. LineBarsData with gradient fill below line. Show dots on data points. BottomTitles with month abbreviations. LeftTitles with formatted amounts (e.g., "1M", "500K")
   - Customize chart text styles to use AppTypography

10. Run build_runner after creating all models.
```

### Phase 4: AI Integration

#### Prompt 4.1 — Laravel AI Services (Gemini)

```
In fintrack-api, implement AI features using Google Gemini API:

1. Add GEMINI_API_KEY to .env and config/services.php:
   'gemini' => [
       'api_key' => env('GEMINI_API_KEY'),
       'model' => 'gemini-2.0-flash',
       'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
   ]

2. Create GeminiService (app/Services/GeminiService.php):

   Properties: $apiKey, $model, $baseUrl (from config)

   a. categorize(array $transactionData, Collection $categories): array
      - Build prompt with transaction description/merchant/amount/type + available categories list
      - Call Gemini API: POST {baseUrl}/models/{model}:generateContent?key={apiKey}
      - Request body: { contents: [{ parts: [{ text: prompt }] }], generationConfig: { responseMimeType: "application/json" } }
      - Parse JSON response → return ['category_id' => int, 'category_name' => string, 'confidence' => float]
      - Cache result by merchant_name (Cache::put("categorize:{merchant}", result, 86400))
      - On error: return ['category_id' => null, 'confidence' => 0]

   b. getInsights(User $user): array
      - Gather financial context:
        • Last 30 days transactions aggregated by category
        • Current budget utilization (via BudgetService)
        • Income vs expense ratio
        • Recurring transaction totals
        • Comparison with previous 30 days
      - Build detailed prompt asking for 3-5 actionable insights in JSON format
      - Call Gemini API
      - Parse and return insights array with: title, description, potential_savings, priority, category
      - Cache per user for 6 hours: Cache::put("insights:{userId}", result, 21600)

   c. chat(string $message, array $history, array $financialContext): StreamedResponse
      - Build system instruction with user's financial summary (monthly income/expense, budgets, top categories, wallet balances, recent transactions)
      - Build contents array from chat history + new message
      - Call Gemini streaming API: POST {baseUrl}/models/{model}:streamGenerateContent?key={apiKey}&alt=sse
      - Return Laravel StreamedResponse that forwards SSE chunks to client
      - Each chunk format: data: {"type": "chunk", "content": "text piece"}
      - Final chunk: data: {"type": "done", "session_id": id}

3. Create AiController (app/Http/Controllers/Api/V1/):

   a. categorize(Request $request):
      - Validate: description (required|string), merchant_name (sometimes|string), amount (required|numeric), type (required|in:income,expense)
      - Check cache first for merchant_name
      - Call GeminiService::categorize
      - Return JSON response with category suggestion + confidence

   b. insights(Request $request):
      - Check cache for user
      - Call GeminiService::getInsights
      - Return insights array

   c. chat(AiChatRequest $request):
      - Validate: message (required|string|max:2000), session_id (sometimes|exists:ai_chat_sessions,id)
      - If no session_id: create new AiChatSession
      - Save user message to ai_chat_messages
      - Load chat history from session
      - Build financial context for system prompt
      - Call GeminiService::chat → return SSE stream
      - After stream complete: save model response to ai_chat_messages

   d. chatSessions(): List user's chat sessions (latest first)
   e. chatHistory(int $sessionId): Get messages for a session
   f. deleteChatSession(int $sessionId): Delete session + messages

4. Create AiChatRequest:
   - message: required|string|max:2000
   - session_id: sometimes|nullable|integer|exists:ai_chat_sessions,id

5. Rate limit AI endpoints: 10 requests per minute per user (use Laravel RateLimiter in RouteServiceProvider)

6. Register routes:
   - POST ai/categorize
   - GET ai/insights
   - POST ai/chat (returns SSE stream)
   - GET ai/chat/sessions
   - GET ai/chat/sessions/{id}
   - DELETE ai/chat/sessions/{id}
```

#### Prompt 4.2 — Flutter AI Features

````
In the fintrack Flutter project, implement AI features:

1. Create data/models/ai_insight_model.dart (@freezed):
   - String title, String description, double? potentialSavings, String priority, String? category

2. Create data/models/chat_session_model.dart (@freezed):
   - int id, String? title, DateTime createdAt

3. Create data/models/chat_message_model.dart (@freezed):
   - int id, String role, String content, DateTime createdAt

4. Create data/repositories/ai_repository.dart:
   - categorizeTransaction({description, merchantName, amount, type}) → {categoryId, categoryName, confidence}
   - getInsights() → {insights: List<AiInsightModel>, healthScore: int, summary: String}
   - sendChatMessage({message, sessionId}) → Stream<String> (SSE stream)
   - getChatSessions() → List<ChatSessionModel>
   - getChatHistory(sessionId) → List<ChatMessageModel>
   - deleteChatSession(sessionId)

   For SSE streaming in sendChatMessage:
   - Use dio.post with Options(responseType: ResponseType.stream)
   - Return a Stream that parses SSE lines:
     ```dart
     final response = await _dio.post('/ai/chat',
       data: {'message': message, 'session_id': sessionId},
       options: Options(responseType: ResponseType.stream),
     );
     final stream = (response.data as ResponseBody).stream;
     yield* stream
       .transform(utf8.decoder)
       .transform(const LineSplitter())
       .where((line) => line.startsWith('data: '))
       .map((line) => line.substring(6))
       .where((data) => data.isNotEmpty)
       .map((data) => jsonDecode(data));
     ```

5. Update add_transaction_screen.dart:
   - Add "AI Categorize" IconButton (magic wand / auto_awesome icon) next to category selector
   - When tapped: show loading indicator on the button, call aiRepository.categorizeTransaction
   - If confidence >= 0.85: auto-select category, show green checkmark + "AI: {categoryName} (95%)" chip
   - If confidence < 0.85: show suggestion as a selectable chip with "Suggested: {categoryName} ({confidence}%)" — user can accept or choose manually
   - If error: show small error text, let user select manually

6. Create presentation/more/ai_insights/providers/insights_provider.dart:
   - InsightsState: loading, loaded(insights, healthScore, summary), error(String)
   - fetchInsights()

7. Implement insights_screen.dart:
   - At top: Health Score gauge (circular, 0-100, colored green/yellow/red)
   - Summary text paragraph below gauge
   - "Your Insights" section: ListView of insight cards
   - Each InsightCard:
     - Priority badge (HIGH=red, MEDIUM=amber, LOW=green) at top-right
     - Title (bold)
     - Description text
     - If potentialSavings != null: "Potential savings: Rp 150,000/month" chip
     - Related category name at bottom
   - Pull to refresh (clears cache and refetches)
   - Loading: shimmer placeholders

8. Create presentation/more/ai_chat/providers/chat_provider.dart:
   - ChatState: {messages: List<ChatMessage>, isLoading, isStreaming, currentStreamText, sessions: List<ChatSessionModel>, activeSessionId}
   - ChatMessage (local model, not freezed): String role ('user'|'model'), String content, bool isStreaming
   - ChatNotifier:
     - loadSessions(): fetch chat sessions list
     - loadHistory(sessionId): load messages for session
     - sendMessage(String text):
       1. Add user message to state.messages
       2. Add empty model message with isStreaming=true
       3. Call aiRepository.sendChatMessage → listen to stream
       4. For each chunk: append content to current model message, update state
       5. On done: mark isStreaming=false, save session_id
     - deleteSession(id): remove from list

9. Implement chat_screen.dart:
   - AppBar: "AI Financial Advisor" title, sessions list button (history icon) → opens drawer/bottom sheet with past sessions
   - Chat messages list (ListView.builder, reversed):
     - User messages: right-aligned, primary color background, white text, rounded corners (top-left, top-right, bottom-left)
     - Model messages: left-aligned, surface color background, rounded corners (top-left, top-right, bottom-right)
     - Streaming message: show typing indicator (three dots animation) then text as it arrives
   - Bottom input area:
     - TextField with "Ask about your finances..." placeholder
     - Send button (arrow_upward icon in circle), disabled when empty or streaming
     - Max 2000 characters
   - Suggested prompts (show when no messages):
     - "How can I save more this month?"
     - "Analyze my spending habits"
     - "What subscriptions should I review?"
     - "Am I on track with my budgets?"
   - Each suggested prompt is a tappable chip that sends the message

10. Run build_runner after creating all new models.
````

### Phase 5: Multi-Currency, Export & Polish

#### Prompt 5.1 — Laravel Export + Currency Update

```
In fintrack-api, implement export and currency features:

1. Create CurrencyService (app/Services/CurrencyService.php):
   - updateExchangeRates(): Fetch from https://api.frankfurter.app/latest?from=USD
   - Parse response and update currencies table exchange_rate_to_usd
   - Convert: convertAmount(float $amount, string $fromCurrency, string $toCurrency): uses stored rates

2. Create UpdateExchangeRates console command:
   - Calls CurrencyService::updateExchangeRates()
   - Schedule: $schedule->command('currency:update-rates')->daily();

3. Create CurrencyController:
   - index: List all currencies
   - rates: Return exchange rates (optionally from/to specific currency)

4. Create ExportService (app/Services/ExportService.php):
   - exportTransactionsCsv(User $user, array $filters):
     - Query transactions with filters (date range, wallet, category, type)
     - Generate CSV string with columns: Date, Type, Category, Description, Amount, Currency, Wallet, Merchant, Notes
     - Return StreamedResponse with CSV headers

   - exportTransactionsPdf(User $user, array $filters):
     - Same query as CSV
     - Render Blade view 'exports.transactions' with data
     - Use barryvdh/laravel-dompdf to generate PDF
     - Return download response

   - exportMonthlyReportPdf(User $user, int $year, int $month):
     - Aggregate data for the month: total income, total expense, net, category breakdown, top 5 merchants, daily spending, comparison with previous month
     - Render Blade view 'exports.monthly-report' with styled HTML tables
     - Generate PDF and return

5. Create ExportController:
   - transactionsCsv(Request $request): Validate date filters → call ExportService
   - transactionsPdf(Request $request): Same
   - monthlyReportPdf(Request $request): Validate year/month → call ExportService

6. Create Blade views for PDF:
   - resources/views/exports/transactions.blade.php: styled table of transactions
   - resources/views/exports/monthly-report.blade.php: formatted report with summary boxes, category table, merchant table, month comparison

7. Install barryvdh/laravel-dompdf: composer require barryvdh/laravel-dompdf

8. Register routes:
   - GET export/transactions/csv (with query params: start_date, end_date, wallet_id, category_id, type)
   - GET export/transactions/pdf (same filters)
   - GET export/report/pdf (query params: year, month)
   - GET currencies, GET currencies/rates
```

#### Prompt 5.2 — Flutter Export + Polish + Final Integration

```
In the fintrack Flutter project, implement export, multi-currency display, and final polish:

1. Create data/repositories/export_repository.dart:
   - exportTransactionsCsv({startDate, endDate, walletId, categoryId, type}) → downloads file
   - exportTransactionsPdf({same filters}) → downloads file
   - exportMonthlyReportPdf({year, month}) → downloads file
   - Use dio.download() to save to app's documents directory
   - After download: use share_plus to share or open_file to open

2. Create data/repositories/currency_repository.dart:
   - getCurrencies() → List<CurrencyModel>
   - getRates() → Map<String, double>

3. Create data/models/currency_model.dart (@freezed):
   - String code, String name, String symbol, int decimalPlaces, double? exchangeRateToUsd

4. Implement export_screen.dart:
   - "Export Data" title
   - Export type selector: Transactions CSV, Transactions PDF, Monthly Report PDF
   - Date range picker for transactions export
   - Filter options: wallet, category, type (reuse filter components)
   - For monthly report: month/year picker
   - "Export" button → show loading → download file → show success snackbar with "Open" and "Share" actions
   - Error handling with retry option

5. Multi-currency display improvements:
   - Dashboard total balance: convert all wallet balances to user's default currency
   - Show original currency amounts in parentheses where different
   - Currency formatter handles IDR (no decimals, dot separator), USD/EUR (2 decimals, comma separator)
   - Settings screen: change default currency → calls updateProfile API

6. Dark mode implementation:
   - In more_screen.dart: add theme toggle section
   - Three options: Light, Dark, System (use SegmentedButton)
   - Changing theme instantly updates via Riverpod themeProvider
   - All screens use Theme.of(context) colors — no hardcoded colors

7. Polish and UX improvements:

   a. Loading states: Add shimmer loading placeholders (using shimmer package) for:
      - Transaction list items (3 placeholder rows)
      - Dashboard cards
      - Budget progress cards
      - Chat messages

   b. Error states: Create reusable AppErrorWidget with:
      - Error illustration/icon
      - Error message
      - "Try Again" button

   c. Empty states: Create reusable EmptyStateWidget with:
      - Illustration (use Flutter icons or simple SVG)
      - Title: "No transactions yet", "No budgets set", etc.
      - Subtitle with helpful instruction
      - Optional CTA button: "Add your first transaction"

   d. Pull-to-refresh: Add RefreshIndicator to all list screens

   e. Confirm dialogs: Reusable ConfirmDialog for delete actions with:
      - Warning icon, title, description, Cancel + Delete buttons
      - Delete button uses expenseRed color

   f. Smooth transitions:
      - Hero animation on balance card (splash → home)
      - SlideTransition for screen navigation via go_router CustomTransitionPage
      - AnimatedSwitcher for theme changes

   g. Form UX:
      - Auto-focus first field on form screens
      - Show/hide password toggle with animated eye icon
      - Numeric keyboard for amount fields
      - Close keyboard on tap outside

   h. Snackbar feedback for all actions:
      - Success: green background, checkmark icon
      - Error: red background, error icon
      - Use ScaffoldMessenger for consistency

8. Implement more_screen.dart:
   - User profile section at top (avatar, name, email)
   - Sections with ListTile navigation:
     - AI Insights → /more/insights
     - AI Financial Advisor → /more/chat
     - Wallets → /more/wallets
     - Categories → /more/categories
     - Recurring Transactions → /more/recurring
     - Export Data → /more/export
     - Currency Settings → change default currency
     - Theme → Light/Dark/System toggle
     - About → app version info
     - Logout → confirm dialog → clear auth

9. Final integration checks:
   - Verify all API calls have proper error handling
   - Verify auth token refresh/expiry handling
   - Test all navigation flows
   - Verify Riverpod providers are properly scoped and disposed
   - Ensure all list screens handle empty/loading/error states

10. Run build_runner one final time.
```

---

## 13. CLAUDE.md Project Context File

Place this file in the Flutter project root (`fintrack/CLAUDE.md`):

````markdown
# CLAUDE.md — FinTrack Project Context

## Project

FinTrack is a Smart Personal Finance Tracker — Flutter mobile app with Laravel backend.

## Architecture

- **Flutter**: Clean Architecture (data/presentation/core layers)
- **State Management**: Riverpod with code generation (@riverpod annotation)
- **Navigation**: go_router with ShellRoute for bottom nav
- **Backend**: Laravel 13 REST API
- **Auth**: Laravel Sanctum (token-based)
- **Database**: PostgreSQL with Eloquent ORM
- **AI**: Google Gemini API (gemini-2.0-flash) for categorization, insights, chatbot

## Key Patterns

- All models use @freezed with fromJson/toJson
- Repositories handle API calls via Dio, return typed models
- Providers (Riverpod StateNotifier) manage UI state per feature
- All amounts stored as DECIMAL(18,2) in DB, double in Dart
- Currency formatting via CurrencyFormatter utility (locale-aware)
- SSE streaming for AI chatbot (Dio + ResponseType.stream)

## Folder Convention

```
lib/core/         → constants, theme, network, router, utils, shared widgets
lib/data/         → models (@freezed), repositories, local storage
lib/presentation/ → auth|home|transactions|budgets|more → providers|screens|widgets
```

## API Base URL

- Android Emulator: http://10.0.2.2:8000/api/v1
- iOS Simulator: http://localhost:8000/api/v1

## Common Commands

```bash
# Generate freezed + json_serializable + riverpod code
dart run build_runner build --delete-conflicting-outputs

# Run app
flutter run

# Laravel
php artisan serve
php artisan migrate:fresh --seed
php artisan queue:work
php artisan schedule:run
```

## Dependencies (Flutter)

flutter_riverpod, riverpod_annotation, go_router, dio, flutter_secure_storage,
hive, hive_flutter, fl_chart, google_fonts, intl, freezed_annotation,
json_annotation, shimmer, gap, share_plus, open_file, cached_network_image

## Dependencies (Laravel)

laravel/sanctum, barryvdh/laravel-dompdf

## Key Design Tokens

- Primary: #0D9488 (Teal)
- Income: #22C55E (Green)
- Expense: #EF4444 (Red)
- Warning: #F59E0B (Amber)
- Font: Inter (UI), JetBrains Mono (amounts)
- Border radius: 12 (inputs), 16 (cards)
- Min button height: 48
````

---

## 14. QUICKSTART.sh Cheatsheet

```bash
#!/bin/bash
# FinTrack — Quick Setup Cheatsheet

echo "=== BACKEND SETUP ==="
echo "1. cd fintrack-api"
echo "2. composer install"
echo "3. cp .env.example .env && php artisan key:generate"
echo "4. Edit .env: DB_CONNECTION=pgsql, DB_DATABASE=fintrack, GEMINI_API_KEY=your_key"
echo "5. createdb fintrack  (PostgreSQL)"
echo "6. php artisan migrate:fresh --seed"
echo "7. php artisan serve"
echo ""
echo "=== FLUTTER SETUP ==="
echo "8. cd fintrack"
echo "9. flutter pub get"
echo "10. dart run build_runner build --delete-conflicting-outputs"
echo "11. flutter run"
echo ""
echo "=== BACKGROUND JOBS ==="
echo "12. php artisan queue:work          # Process budget alerts"
echo "13. php artisan schedule:run        # Process recurring transactions + update rates"
echo "14. php artisan recurring:process   # Manual: process due recurring transactions"
echo "15. php artisan currency:update-rates  # Manual: update exchange rates"
echo ""
echo "=== TESTING API ==="
echo "Register: curl -X POST http://localhost:8000/api/v1/auth/register -H 'Content-Type: application/json' -d '{\"name\":\"Test\",\"email\":\"test@test.com\",\"password\":\"password123\",\"password_confirmation\":\"password123\"}'"
echo ""
echo "Login: curl -X POST http://localhost:8000/api/v1/auth/login -H 'Content-Type: application/json' -d '{\"email\":\"test@test.com\",\"password\":\"password123\"}'"
echo ""
echo "=== KEY URLS ==="
echo "API: http://localhost:8000/api/v1"
echo "Android Emulator: http://10.0.2.2:8000/api/v1"
```

---

_End of PRD — FinTrack Smart Personal Finance Tracker v1.0_
