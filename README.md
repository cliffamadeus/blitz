# Blitz

A PHP-based web application featuring user authentication, role-based access control, and an admin dashboard.

## Overview

Blitz is a lightweight PHP application built around a modular structure with dedicated directories for authentication, configuration, database migrations, and business logic. It includes user management capabilities with security safeguards such as password verification and last-role protection on user deletion.

## Project Structure

```
blitz/
├── app/          # Application logic (e.g., user management)
├── auth/         # Authentication handling
├── config/       # Configuration files
├── database/     # SQL migrations and schema
├── functions/    # Reusable helper functions
├── tests/        # Test files
└── index.php     # Application entry point
```

### Directory Details

| Directory | Purpose |
|-----------|---------|
| `app/` | Core application features, including admin user management (`admin/users.php`) |
| `auth/` | Login, signout, and authentication redirects |
| `config/` | Application configuration settings |
| `database/` | Database migrations (e.g., `003_transactions.sql`, `004_password_resets.sql`) |
| `functions/` | Shared utility functions |
| `tests/` | Test coverage for application features |

## Features

- **User Authentication** — Login page with redirect handling and sign-out support
- **User Management** — Admin interface for managing users (`admin/users.php`)
- **Role-Based Access Control** — Role assignments and validation
- **Secure User Deletion** — Requires password confirmation and prevents removal of the last remaining role
- **Password Resets** — Dedicated schema support via `004_password_resets.sql`
- **Transaction Support** — Migration `003_transactions.sql` for transactional data
- **Database Migrations** — Versioned SQL schema changes

## Requirements

- PHP (version compatible with the codebase)
- A supported database (MySQL/MariaDB or similar — see migration files)
- A web server (Apache, Nginx, or PHP's built-in server)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/cliffamadeus/blitz.git
   cd blitz
   ```

2. **Configure the application**
   - Update the files in `config/` with your database credentials and environment settings.

3. **Run database migrations**
   - Apply the SQL files in `database/` in order (e.g., `003_transactions.sql`, `004_password_resets.sql`) to set up the schema.

4. **Start the application**
   ```bash
   php -S localhost:8000
   ```
   Then navigate to `http://localhost:8000` in your browser.

## Usage

- Access the login page to authenticate.
- Administrators can manage users via the admin interface.
- Sign out when finished to end your session.

## Recent Changes

- **Oct 10, 2026** — Hardened user deletion with password verification and last-role checks
- **Oct 2, 2026** — Added transaction migration (`003_transactions.sql`), password reset schema (`004_password_resets.sql`), and initial user management for `admin/users.php`
- **Sep 9, 2026** — Added login page, redirects, and sign-out

## Testing

Tests are located in the `tests/` directory. Run them using your preferred PHP testing tool (e.g., PHPUnit) as configured in the repository.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -m 'Add your feature'`)
4. Push to the branch (`git push origin feature/your-feature`)
5. Open a Pull Request

## License

No license specified. Please contact the repository owner for usage terms.

## Author

**cliffamadeus** — [GitHub Profile](https://github.com/cliffamadeus)

---

> **Note:** This README was generated based on the repository structure and commit history. Some details (such as exact PHP version, database engine, and test framework) may need verification against the source code.
