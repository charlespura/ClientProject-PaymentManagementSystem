# Client Project & Payment Management System

A PHP and MySQL dashboard for managing client transactions, project progress, payment status, revenue metrics, and soft-deleted records. The interface is styled with Tailwind CSS and designed to run locally through XAMPP.

## Overview

This project provides a lightweight admin dashboard for:

- Creating client transaction records
- Viewing full transaction details
- Updating project and payment information
- Soft deleting, restoring, and permanently deleting records
- Tracking dashboard metrics such as paid revenue, outstanding revenue, completion rate, and average ticket size
- Reviewing recent revenue trends and payment method breakdowns

## Tech Stack

- PHP
- MySQL / MariaDB
- Tailwind CSS v4
- XAMPP
- npm for frontend build tooling

## Features

- Clean admin dashboard in [`admin.php`](/Applications/XAMPP/xamppfiles/htdocs/business/admin.php)
- Shared database connection in [`connection.php`](/Applications/XAMPP/xamppfiles/htdocs/business/connection.php)
- Analytics helpers in [`analytics.php`](/Applications/XAMPP/xamppfiles/htdocs/business/analytics.php)
- Record detail view in [`view.php`](/Applications/XAMPP/xamppfiles/htdocs/business/view.php)
- Record editing flow in [`update.php`](/Applications/XAMPP/xamppfiles/htdocs/business/update.php) and [`update_handler.php`](/Applications/XAMPP/xamppfiles/htdocs/business/update_handler.php)
- Soft delete, restore, and purge logic in [`delete.php`](/Applications/XAMPP/xamppfiles/htdocs/business/delete.php)
- Tailwind source file in [`tailwind.input.css`](/Applications/XAMPP/xamppfiles/htdocs/business/tailwind.input.css)
- Compiled stylesheet output in [`tailwind.css`](/Applications/XAMPP/xamppfiles/htdocs/business/tailwind.css)

## Requirements

- PHP 8.x recommended
- MySQL or MariaDB
- XAMPP installed and running
- Node.js and npm

## Local Setup

1. Place the project inside your XAMPP `htdocs` directory.
2. Start Apache and MySQL from XAMPP.
3. Create a database named `business`.
4. Import the database file from [`database/business.sql`](/Applications/XAMPP/xamppfiles/htdocs/business/database/business.sql).
5. Install frontend dependencies:

```bash
npm install
```

6. Build the stylesheet:

```bash
npm run build:css
```

7. Open the app in your browser:

```text
http://localhost/business/admin.php
```

## Database Configuration

The default database connection is defined in [`connection.php`](/Applications/XAMPP/xamppfiles/htdocs/business/connection.php):

```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "business";
```

Update those values if your local MySQL setup is different.

## Database Import

Use the included SQL file to get the project running locally:

```sql
SOURCE database/business.sql;
```

Notes:

- The app expects a table named `ClientTransactions`.
- The included schema file is [`database/business.sql`](/Applications/XAMPP/xamppfiles/htdocs/business/database/business.sql).
- If `deleted_at` is missing, the project attempts to add it automatically through [`analytics.php`](/Applications/XAMPP/xamppfiles/htdocs/business/analytics.php).

## Available npm Scripts

```bash
npm run build:css
```

Builds and minifies `tailwind.css` from `tailwind.input.css`.

```bash
npm run watch:css
```

Watches PHP files and rebuilds `tailwind.css` during development.

## Project Structure

```text
business/
├── database/
│   └── business.sql
├── admin.php
├── analytics.php
├── connection.php
├── delete.php
├── update.php
├── update_handler.php
├── view.php
├── tailwind.input.css
├── tailwind.css
├── package.json
└── logo.png
```

## Workflow Summary

- Add new client transaction records from the dashboard
- Mark records as paid or not paid
- Store payment method and completion date for paid work
- Review metrics and revenue summaries from the analytics layer
- Move records to trash instead of deleting them immediately
- Restore or permanently remove trashed records when needed

## Development Notes

- The project currently uses inline PHP with procedural helpers, which keeps it simple for local deployment.
- Tailwind scans `./*.php` as defined in [`tailwind.input.css`](/Applications/XAMPP/xamppfiles/htdocs/business/tailwind.input.css).
- `node_modules` should not be committed.
- `tailwind.css` is generated output. Keep it committed only if you want the project to run without requiring a local CSS build step.

## Recommended Next Improvements

- Move database credentials into an environment file
- Add SQL migration files instead of relying on runtime schema changes
- Add server-side validation for all fields before insert and update
- Add CSRF protection for form submissions
- Add authentication for admin access
- Add export support for reports

## License

No license has been defined yet. Add one before distributing the project publicly.
