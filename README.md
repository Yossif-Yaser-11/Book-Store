# Arabic Book Store Admin

The admin area is available at `/books/admin/login.php`.

Before using it, update the database constants in `admin/includes/db.php` if the existing MySQL database does not use the default XAMPP values:

- Database: `books`
- User: `root`
- Password: empty

Passwords in the `users.password` column must be created with PHP `password_hash()`.
