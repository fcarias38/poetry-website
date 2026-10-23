<?php
/**
 * admin_auth.php
 * Resolves the effective admin password hash for verification.
 *
 * Priority:
 *   1. $ADMIN_PASS_HASH from admin_config.php (set by the password-reset flow).
 *   2. A cached bcrypt hash of $ADMIN_PASSWORD, computed once and stored in
 *      admin_pass.hash so we don't re-run bcrypt on every request.
 *
 * Requires admin_config.php to have been included first.
 */

if (!function_exists('admin_password_hash')) {
    /**
     * @return string A bcrypt hash suitable for password_verify(), or '' if no
     *                credential source is configured.
     */
    function admin_password_hash(): string {
        global $ADMIN_PASS_HASH, $ADMIN_PASSWORD;

        // 1. Explicit hash set by the reset flow wins.
        if (!empty($ADMIN_PASS_HASH)) {
            return $ADMIN_PASS_HASH;
        }

        // 2. Fall back to a cached hash of the plaintext password.
        if (empty($ADMIN_PASSWORD)) {
            return '';
        }

        $cacheFile = __DIR__ . '/admin_pass.hash';

        if (is_file($cacheFile)) {
            $cached = trim((string)file_get_contents($cacheFile));
            // Reuse the cache only if it still matches the configured password.
            if ($cached !== '' && password_verify($ADMIN_PASSWORD, $cached)) {
                return $cached;
            }
        }

        // No valid cache — hash once and store it (best effort).
        $hash = password_hash($ADMIN_PASSWORD, PASSWORD_DEFAULT);
        @file_put_contents($cacheFile, $hash, LOCK_EX);
        return $hash;
    }
}
?>
