<?php
/** Private draft storage is separate from every public poem query. */
function poem_drafts_init(mysqli $conn): void {
    $conn->set_charset('utf8mb4');
    $conn->query("CREATE TABLE IF NOT EXISTS poem_drafts (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        source_poem_id INT UNSIGNED NULL,
        title VARCHAR(255) NOT NULL DEFAULT '',
        category VARCHAR(100) NOT NULL DEFAULT '',
        body MEDIUMTEXT NOT NULL,
        meta_description TEXT NULL,
        meta_keywords TEXT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
