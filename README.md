# Think Room — Poetry Website

A personal poetry and reflection gallery by **Freddy Carias**, live at [freddycarias.com](https://freddycarias.com).

Think Room is a place to read, like, and comment on original poems. It includes a full admin panel for managing works, a public-facing gallery, and a contact form.

---

## Features

- Public poetry gallery with category filtering
- Like and comment system per poem
- Admin dashboard to create, edit, and delete poems
- Secure admin login
- Contact form with email delivery
- SEO-optimized pages with Open Graph and JSON-LD schema
- Responsive design via Bootstrap 5

---

## Tech Stack

- **Frontend:** HTML, CSS, Bootstrap 5, JavaScript
- **Backend:** PHP
- **Database:** MySQL (MySQLi)
- **Hosting:** Shared hosting with SSI (`.shtml` pages)

---

## Project Structure

```
/
├── index.shtml           # Landing / splash page
├── home.shtml            # Main poetry gallery
├── all_poems.php         # Public poems listing
├── get_poems.php         # API — fetch poems
├── get_poem_by_id.php    # API — single poem
├── get_likes_comments.php# API — likes & comments data
├── update_likes.php      # API — handle likes
├── save_comment.php      # API — save a comment
├── save_work.php         # Admin — save new poem
├── edit_poem.php         # Admin — edit poem
├── delete_poem.php       # Admin — delete poem
├── dashboard.php         # Admin dashboard
├── login.php             # User login
├── admin_login.php       # Admin login
├── logout.php            # Session logout
├── send_contact.php      # Contact form handler
├── navbar.html           # Shared navbar partial
├── footer.html           # Shared footer partial
├── styles.css            # Global styles
├── config.example.php    # DB config template (copy → config.php)
├── admin_config.example.php  # Admin creds template (copy → admin_config.php)
└── manifest.json         # PWA manifest
```

---

## Database Setup

Run the following SQL to set up the required tables:

```sql
CREATE TABLE `poems` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `body` LONGTEXT NULL,
  `category` VARCHAR(100) NULL,
  `likes` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `poem_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NULL,
  `email` VARCHAR(150) NULL,
  `comment` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_comments_poem_id`
    FOREIGN KEY (`poem_id`) REFERENCES `poems`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Local Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/fcarias38/poetry-website.git
   ```

2. Copy the config templates and fill in your credentials:
   ```bash
   cp config.example.php config.php
   cp admin_config.example.php admin_config.php
   ```

3. Import the database schema above into your MySQL instance.

4. Serve with a local PHP server (e.g., XAMPP, Laragon) or deploy to shared hosting.

---

## Security Notes

- `config.php` and `admin_config.php` are excluded from version control via `.gitignore` — never commit live credentials.
- Admin routes should be protected behind the login session check in `admin_login.php`.

---

## License

&copy; 2025 Freddy Carias. All rights reserved.  
Built by [TechbyFreddy](https://techbyfreddy.com).
