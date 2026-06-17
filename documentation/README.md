# Student Management System — Complete Documentation

## 1. Project Overview

The Student Management System (SMS) is a web application built with **Core PHP**, **MySQLi**, **Bootstrap 5**, and plain **HTML/CSS/JavaScript**. It has two user roles:

- **Admin** — can add, edit, view, delete, and search students.
- **Student** — can log in and update only their own phone, address, and profile image.

All passwords are hashed with bcrypt. Sessions use token-based expiry (1 hour). Prepared statements prevent SQL injection. No external frameworks (no Laravel, no Composer) are used.

---

## 2. Folder Structure

```
/                           Project root
├── index.php               Redirect hub — sends user to correct dashboard or login
├── login.php               Dual-role login (Admin / Student tabs)
├── logout.php              Destroys session, redirects to login
│
├── includes/
│   ├── db.php              MySQLi connection; dies gracefully on failure
│   ├── functions.php       All helpers: token auth, image upload, flash messages, redirect
│   ├── admin_header.php    HTML head + sidebar navigation for admin pages
│   ├── admin_footer.php    Closing HTML + Bootstrap JS for admin pages
│   ├── student_header.php  HTML head + top navbar for student pages
│   └── student_footer.php  Closing HTML + Bootstrap JS for student pages
│
├── admin/
│   ├── dashboard.php       Admin home: stats + recent students
│   └── students/
│       ├── list.php        All students table with search
│       ├── add.php         Add new student form
│       ├── edit.php        Edit existing student
│       ├── view.php        Read-only student detail view
│       └── delete.php      Delete student + image handler
│
├── student/
│   ├── dashboard.php       Student home: profile summary
│   └── profile.php         Student self-update (phone, address, image only)
│
├── assets/
│   ├── css/style.css       All custom styles
│   ├── js/validation.js    All frontend form validation
│   └── uploads/            Uploaded profile images (gitignored in production)
│
└── documentation/
    └── README.md           This file
```

---

## 3. Database Structure

**Database name:** `student_portal`

### admins
| Column     | Type         | Notes              |
|------------|--------------|--------------------|
| id         | INT PK AI    | Auto increment     |
| username   | VARCHAR(100) | Unique             |
| password   | VARCHAR(255) | bcrypt hash        |
| created_at | DATETIME     | Default NOW()      |

### students
| Column        | Type                        | Notes                          |
|---------------|-----------------------------|--------------------------------|
| id            | INT PK AI                   | Auto increment                 |
| full_name     | VARCHAR(150)                |                                |
| email         | VARCHAR(150)                | Unique                         |
| password      | VARCHAR(255)                | bcrypt hash                    |
| phone         | VARCHAR(20)                 |                                |
| gender        | ENUM(Male, Female, Other)   |                                |
| course        | VARCHAR(100)                |                                |
| dob           | DATE                        | YYYY-MM-DD                     |
| profile_image | VARCHAR(255)                | Filename only, stored in uploads/ |
| address       | TEXT                        |                                |
| status        | ENUM(Active, Inactive)      | Default: Active                |
| created_at    | DATETIME                    | Default NOW()                  |

---

## 4. Authentication Flow

### Login process
1. User opens `login.php`. Two tabs: Admin and Student.
2. User fills credentials and clicks **Sign In**.
3. PHP validates fields server-side (empty check, email format).
4. PHP queries the relevant table using a prepared statement.
5. If found: `password_verify()` checks the bcrypt hash.
6. On success:
   - `session_regenerate_id(true)` prevents session fixation.
   - `$_SESSION['role']`, `$_SESSION['user_id']`, `$_SESSION['auth_token']`, `$_SESSION['token_time']` are set.
   - User is redirected to their dashboard.
7. On failure: error message shown inline, no redirect.

### Token validation (every protected page)
- `requireAdmin()` or `requireStudent()` is called at the top of every protected page.
- `isTokenValid()` checks:
  - `$_SESSION['auth_token']` exists.
  - `$_SESSION['token_time']` exists.
  - `time() - token_time <= 3600` (1 hour).
- If invalid: session is destroyed and user is sent to `login.php`.
- If valid: `refreshToken()` updates `token_time` (sliding expiry).

### Logout
- `logout.php` clears `$_SESSION`, removes the session cookie, calls `session_destroy()`, and redirects to `login.php`.

---

## 5. Admin Workflow

### Dashboard (`admin/dashboard.php`)
- **Page loads:** Queries `COUNT(*)` total, active, inactive from `students`. Queries 5 most recent students.
- **Stats cards:** Show totals.
- **Recent students table:** Clickable View button per row.
- **Add Student button:** Links to `add.php`.

### Student List (`admin/students/list.php`)
- **Page loads:** Fetches all students `ORDER BY created_at DESC`.
- **Search:** GET form submits `search` + `type` (name or email). PHP uses `LIKE '%...%'` prepared query.
- **Clear button:** Appears when search is active; links back to `list.php` with no params.
- **View button:** Links to `view.php?id=X`.
- **Edit button:** Links to `edit.php?id=X`.
- **Delete button:** Shows `window.confirm()` then links to `delete.php?id=X`.

### Add Student (`admin/students/add.php`)
**When Submit is clicked:**
1. Frontend JS validates all fields (no submit if errors).
2. PHP re-validates all fields server-side.
3. Checks email uniqueness with prepared statement.
4. Validates uploaded image: error code, file size, MIME type, extension.
5. Hashes password with `password_hash($pass, PASSWORD_BCRYPT)`.
6. Uploads image: renamed to `student_<timestamp>.jpg`, stored in `assets/uploads/`.
7. Inserts student record via prepared statement.
8. Sets flash success message.
9. Redirects to `list.php`.

**When validation fails:** Errors shown inline below each field. Entered values preserved.

### Edit Student (`admin/students/edit.php`)
- **Page loads:** Fetches student by `id` GET param. Pre-fills form.
- **Password field:** Optional — empty = keep current hash.
- **Image field:** Optional — empty = keep current file. New upload = old file deleted after successful DB update.
- **Save Changes:** Same validation flow as Add, skipping image if none uploaded.

### View Student (`admin/students/view.php`)
- Read-only display of all student fields.
- Sidebar buttons: Edit (→ `edit.php?id`), Delete (→ `delete.php?id` with confirm).

### Delete Student (`admin/students/delete.php`)
1. Loads student by `id` to get `profile_image` filename.
2. Runs `DELETE FROM students WHERE id = ?`.
3. Calls `deleteImage()` to unlink file from `assets/uploads/`.
4. Sets flash message and redirects to `list.php`.

---

## 6. Student Workflow

### Student Dashboard (`student/dashboard.php`)
- **Page loads:** Fetches own row using `$_SESSION['user_id']`. Displays welcome message, profile image, course details, summary.
- **Update Profile button:** Links to `profile.php`.

### Student Profile (`student/profile.php`)
- **What students CAN update:** phone, address, profile_image.
- **What is locked (read-only display):** email, course, status, full_name, dob, gender.
- **Page loads:** Loads student row, pre-fills editable fields.
- **Submit:** Validates phone + address; optional image validation; updates only those 3 columns.
- If new image uploaded: old image deleted.
- Success message shown on same page.

---

## 7. File Upload Workflow

1. Admin submits form with image file.
2. PHP checks `$_FILES['profile_image']['error'] === UPLOAD_ERR_OK`.
3. Checks `$_FILES['profile_image']['size'] <= 5242880` (5 MB).
4. Checks `mime_content_type()` is `image/jpeg` or `image/png`.
5. Checks file extension is `jpg`, `jpeg`, or `png`.
6. Generates filename: `student_` + `time()` + `.` + extension.
7. Moves file to `assets/uploads/` using `move_uploaded_file()`.
8. Only the **filename** is stored in the database column `profile_image`.
9. When replacing: old filename is deleted after new upload succeeds.
10. When deleting student: `deleteImage()` removes file using `unlink()`.

---

## 8. Validation Workflow

### Frontend (JS — `assets/js/validation.js`)
- Runs on `form.addEventListener('submit', ...)`.
- Calls `e.preventDefault()` if any error found.
- Shows error below field using Bootstrap `is-invalid` + `.invalid-feedback`.
- Clears all errors before re-checking on every submit.

### Backend (PHP — each form handler)
- Mirrors all frontend rules.
- Additional checks not possible in JS: duplicate email query, file MIME check.
- On failure: re-renders form with `$errors` array and `$old` array to restore values.
- No `alert()` anywhere. All errors are `$errors['field']` rendered as `<div class="invalid-feedback">`.

### Validation rules per field

| Field         | Required | Additional rules                                    |
|---------------|----------|-----------------------------------------------------|
| full_name     | Yes      | —                                                   |
| email         | Yes      | `FILTER_VALIDATE_EMAIL`, unique in DB               |
| password      | Add: Yes | min 8 chars; Edit/Profile: optional but min 8 if provided |
| phone         | Yes      | regex `^\+?[0-9]{7,15}$`                           |
| gender        | Yes      | One of: Male, Female, Other                         |
| course        | Yes      | —                                                   |
| dob           | Yes      | Valid date, not in future                           |
| address       | Yes      | —                                                   |
| status        | Yes      | One of: Active, Inactive                            |
| profile_image | Add: Yes | Edit/Profile: optional; jpg/jpeg/png; max 5 MB      |

---

## 9. Security Workflow

| Threat              | Mitigation                                                   |
|---------------------|--------------------------------------------------------------|
| SQL Injection        | All queries use MySQLi prepared statements with `bind_param` |
| Password theft       | `password_hash()` with `PASSWORD_BCRYPT` (cost factor 10)   |
| Session fixation     | `session_regenerate_id(true)` on successful login            |
| Unauthorized access  | `requireAdmin()` / `requireStudent()` on every protected page|
| Token hijacking      | Token + timestamp stored in session; expires in 1 hour       |
| XSS                  | All output uses `htmlspecialchars()` via `e()` helper        |
| File upload abuse    | MIME check + extension check + size limit + rename           |
| Direct URL access    | Auth check redirects unauthenticated users to login          |
| Role confusion       | `$_SESSION['role']` checked on every page; admin ≠ student   |

---

## 10. CRUD Workflow Summary

| Operation | Page                        | DB Query                                    |
|-----------|-----------------------------|---------------------------------------------|
| Create    | `admin/students/add.php`    | `INSERT INTO students (...) VALUES (...)`   |
| Read all  | `admin/students/list.php`   | `SELECT * FROM students ORDER BY ...`       |
| Read one  | `admin/students/view.php`   | `SELECT * FROM students WHERE id = ?`       |
| Update    | `admin/students/edit.php`   | `UPDATE students SET ... WHERE id = ?`      |
| Delete    | `admin/students/delete.php` | `DELETE FROM students WHERE id = ?`         |
| Self-update| `student/profile.php`      | `UPDATE students SET phone,address,profile_image WHERE id = ?` |
