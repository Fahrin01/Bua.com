# BUA.COM

**Find. Verify. Hire.**

A full-stack PHP/MySQL web platform for finding and hiring verified maid/bua workers in Bangladesh, built around **Area Agents** who register and manage maid profiles on their behalf (maids themselves never log in or access the website).

---

## Technology

- **Frontend:** HTML5, CSS3, Vanilla JavaScript, Bootstrap 5, Font Awesome
- **Backend:** PHP 8+, MySQL, PDO with prepared statements
- **Environment:** XAMPP (Apache + MySQL + phpMyAdmin)

---

## Workflow

```
AREA AGENT → Collects maid info → Registers maid in system
    → ADMIN verifies → Maid becomes searchable
    → CUSTOMER searches & submits a hiring request
    → ADMIN receives request → contacts maid by phone
    → ADMIN updates status → CUSTOMER is notified
    → Hiring confirmed → After completion, customer can review
```

**Maids never use the website** — no login, no dashboard, no chat, no notifications. All contact with maids is handled manually, by phone, by the Admin team.

---

## Installation (XAMPP)

1. Copy the entire `bua.com` folder into your XAMPP `htdocs` directory, so the path is:
   ```
   C:\xampp\htdocs\bua.com\   (Windows)
   /Applications/XAMPP/htdocs/bua.com/   (Mac)
   /opt/lampp/htdocs/bua.com/   (Linux)
   ```
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Click **Import**, choose `database/bua_com.sql`, and click **Go**.
   - This creates the `bua_com` database, all 15 tables, and realistic demo data.
5. Check `config/database.php` — the default XAMPP credentials (`root` / no password) are already set. Edit if your setup differs.
6. Visit **`http://localhost/bua.com/`** in your browser.

That's it — no Composer, no build step, no external dependencies beyond what's loaded from CDN (Bootstrap, Font Awesome, Google Fonts).

---

## Demo Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@bua.com` | `Admin@123` |
| Agent | `agent.rafiq@bua.com` | `Agent@123` |
| Agent | `agent.salma@bua.com` | `Agent@123` |
| Customer | `tanvir@example.com` | `Customer@123` |
| Customer | `nusrat@example.com` | `Customer@123` |

(All demo customer/agent accounts share the same respective password — see `database/bua_com.sql` for the full list.)

You can also register a brand-new customer account from `register.php` — no OTP required.

---

## Folder Structure

```
bua.com/
├── index.php, login.php, register.php, logout.php
├── customer/     → 12 pages (search, request, favorites, reviews, complaints, profile...)
├── agent/        → 7 pages (add/edit maid, verification checks, notifications...)
├── admin/        → 16 pages (verification approval, hiring workflow, reports...)
├── config/       → database.php, auth.php, functions.php
├── includes/     → header, footer, navbar, sidebar, alerts
├── assets/       → css/style.css, js/script.js, images/, uploads/maids/
└── database/     → bua_com.sql
```

---

## Key Design Notes

- **PDO + prepared statements everywhere** — no raw string-concatenated SQL.
- **CSRF tokens** on every state-changing form (`csrfField()` / `requireCsrf()`).
- **Role-based access control** via `requireCustomer()`, `requireAgent()`, `requireAdmin()` in every protected page.
- **Passwords** hashed with `password_hash()` / verified with `password_verify()`.
- **File uploads** (maid photos) are MIME-checked, size-limited (3MB), renamed to random secure filenames, and validated with `getimagesize()` to block disguised executables.
- **Sensitive data** (NID number, private reference phone numbers, internal verification notes) is stored in `maid_verification` / `maid_references` and is **never rendered on any public/customer-facing page** — only the 🟢 Verified / 🟡 Pending Verification badge is shown publicly. Admin/agent panels show more, since they are authenticated, role-gated internal tools.
- **No OTP, no chat, no maid login/notifications** anywhere in the codebase, per the project's business rules.

## Testing Each Role

1. **Customer:** Register → search maids with filters → view a profile → request a maid → track status on "My Requests" → after admin marks it `COMPLETED`, leave a review.
2. **Agent:** Log in → "Add Maid" (upload a photo, fill in work details + reference) → see it listed as `PENDING` on "My Maids" → run the verification checklist.
3. **Admin:** Log in → "Verification" tab to approve the new maid → "Hiring Requests" to manage a customer's request end-to-end (contact maid, add notes, change status) → "Reports" for platform stats.

---

*This is an academic project. All demo data (names, phone numbers, reviews) is fictional.*
