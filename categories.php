<?php
/** Shared category registry; existing poem labels are imported without changing poems. */
function category_name(string $name): string {
    $name = trim($name);
    if (!preg_match('/^.{1,100}$/us', $name) || preg_match('/[\x00-\x1f\x7f]/', $name)) {
        throw new InvalidArgumentException('Use a category name between 1 and 100 characters, without line breaks.');
    }
    return $name;
}

function categories_init(mysqli $conn): void {
    $conn->set_charset('utf8mb4');
    $conn->query("CREATE TABLE IF NOT EXISTS poem_categories (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        UNIQUE KEY category_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $conn->query("INSERT IGNORE INTO poem_categories (name)
        SELECT DISTINCT TRIM(category) FROM poems
        WHERE category IS NOT NULL AND TRIM(category) <> ''");
}

function category_register(mysqli $conn, string $name): string {
    $name = category_name($name);
    categories_init($conn);
    $stmt = $conn->prepare('INSERT IGNORE INTO poem_categories (name) VALUES (?)');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare('SELECT name FROM poem_categories WHERE name = ?');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $canonical = $stmt->get_result()->fetch_assoc()['name'];
    $stmt->close();
    return $canonical;
}
