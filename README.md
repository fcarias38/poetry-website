# Think Room — Poetry Website

A personal poetry and reflection gallery by **Freddy Carias**, live at [freddycarias.com](https://freddycarias.com).

Think Room is a place to read, like, and comment on original poems. It includes a full admin panel for managing works, a public-facing gallery, and a contact form.

---

## Features

- Public poetry gallery with category filtering
- Like and comment system per poem
- Admin dashboard to create, edit, and delete poems
- Secure admin login
- Light admin workspace with category creation, renaming, and reassignment
- Private gallery previews before publishing and optional grammar suggestions
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

Category management creates its `poem_categories` table automatically on first use
and imports the labels already used by poems. The database account needs `CREATE`
permission for that first use. Open **Dashboard → Categories** to add topics, rename
them across all poems, or remove them after moving their poems to another category.
Empty categories remain available in the editor; public topics appear once a poem
uses them. Names are limited to 100 characters.

Categories use an editable table with poem counts. The checkmark saves a name;
the trash icon opens removal and reassignment options.

The theme switch saves separate browser preferences for the admin workspace and
the public website. Admin defaults to light, and the public site defaults to dark.
The preference applies across pages and open tabs for each side independently.

Apache error responses use the static `error.html` page, including `/502`. Deploy
that file together with `.htaccess`, `theme.css`, and `theme.js`. The fallback
does not require PHP or a database. Missing clean URLs only rewrite to `.shtml`
when the target exists, preventing repeated rewrites. Errors generated before
the request reaches this site's Apache configuration (for example, a hosting
proxy failure) require the host's own error-page configuration.

In **New Poem**, **Preview on site** opens the real gallery layout in a private tab
without saving a poem. The preview requires an admin session and a CSRF token,
uses POST rather than placing draft text in URLs, and sends no-cache/no-index
headers. **Save poem** still publishes immediately; preview is optional.

The editor enables browser spellcheck. **Check with LanguageTool** sends the poem
body to LanguageTool only when clicked, using its public proofreading API. It
needs an internet connection and is subject to service availability and free
request limits. The editor explains the text sharing and links to its privacy
policy. Suggestions never change a poem unless selected. Line breaks are preserved.

Verification: `node tests/admin-writing.test.cjs` checks writing interactions.
`node tests/theme.test.cjs` checks defaults, persistence, and separate preferences.
`node tests/http-routing.test.cjs` checks error handling with an isolated Apache
server (Windows/XAMPP default, overridable with `TEST_APACHE_ROOT`).
`node tests/categories.integration.cjs` runs category and private-preview checks
using disposable local PHP/MariaDB instances, with Windows/XAMPP defaults. Set
`TEST_PHP` and `TEST_MYSQL_BIN` to override their executable paths. The integration
test does not use the site's database credentials or data.

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

## Like, comment, and share notifications

The dashboard bell shows an unread count and the latest 40 notifications, with
unread activity first. It checks every 30 seconds while the dashboard is visible
and refreshes when you return to the tab. Notifications remain unread until you
choose **Mark all as read**. Comment notifications link to the moderation panel;
reading a notification does not approve the comment.

Open **Notifications** from the desktop or mobile dashboard menu for a dedicated
activity page. The page and bell share the same feed and read state, and the menu
shows an unread count. The dashboard remembers the last panel you opened.

Deploy `notifications.php`, `admin_notifications.php`, `admin-notifications.js`,
and the updated dashboard, admin CSS, session helper, like/comment/share handlers,
`home.shtml`, `poem.php`, and `.htaccess`
together. The database account needs `CREATE` permission on first use for the
`admin_notifications` and `notification_email_limits` tables. Only new activity
is recorded; historical likes and comments are not imported.

Email alerts use `$ADMIN_EMAIL` and `$MAIL_FROM` from the existing, private
`admin_config.php`. The host must support PHP `mail()` with an authorized sender.
Optional settings, also shown in `admin_config.example.php`:

```php
$NOTIFICATION_EMAIL_ENABLED = true;
$NOTIFICATION_EMAIL = $ADMIN_EMAIL; // Or a different recipient.
$NOTIFICATION_EMAIL_LIKES = true;
$NOTIFICATION_EMAIL_COMMENTS = true;
$NOTIFICATION_EMAIL_SHARES = true;
```

Comment emails send when a comment arrives for approval. Like emails are limited
to one per poem every 15 minutes; all likes appear in the dashboard. Email failure
is logged and does not prevent likes/comments or remove dashboard notifications.
Share emails send for each recorded share action. These record the site's share
tools or copied links; they cannot confirm a completed post on another service.
Cancelled native share dialogs and failed clipboard copies do not record a share.
Mail delivery depends on the host; there is no background retry queue.

Verification: `node tests/notifications.integration.cjs` uses disposable PHP and
MariaDB instances, a local SMTP capture server, and headless Chrome. It never
uses the production database or sends external email. Windows/XAMPP defaults can
be overridden with `TEST_PHP`, `TEST_MYSQL_BIN`, and `TEST_CHROME`.

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
