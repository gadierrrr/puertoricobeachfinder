<?php
/**
 * Rate Limiter Class
 * Prevents abuse via sliding window rate limiting
 */

class RateLimiter {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->createTable();
    }

    private function createTable() {
        $this->db->exec('CREATE TABLE IF NOT EXISTS rate_limits (
            id TEXT PRIMARY KEY,
            identifier TEXT NOT NULL,
            action TEXT NOT NULL,
            attempts INTEGER DEFAULT 0,
            window_start TEXT NOT NULL,
            created_at TEXT
        )');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_rate_limits ON rate_limits(identifier, action, window_start)');
    }

    public function check($identifier, $action, $maxAttempts = 5, $windowMinutes = 60) {
        // Sliding window rate limiting
        $windowStart = date('Y-m-d H:i:s', strtotime("-$windowMinutes minutes"));

        // Clean old entries
        $stmt = $this->db->prepare('DELETE FROM rate_limits WHERE window_start < :window_start');
        $stmt->bindValue(':window_start', $windowStart);
        $stmt->execute();

        // Check current attempts
        $stmt = $this->db->prepare('SELECT SUM(attempts) as total FROM rate_limits
            WHERE identifier = :identifier AND action = :action AND window_start >= :window_start');
        $stmt->bindValue(':identifier', $identifier);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':window_start', $windowStart);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        $currentAttempts = $result['total'] ?? 0;

        if ($currentAttempts >= $maxAttempts) {
            return ['allowed' => false, 'remaining' => 0];
        }

        // Record this attempt
        $id = uuid();
        $stmt = $this->db->prepare('INSERT INTO rate_limits (id, identifier, action, attempts, window_start, created_at)
            VALUES (:id, :identifier, :action, 1, datetime("now"), datetime("now"))');
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':identifier', $identifier);
        $stmt->bindValue(':action', $action);
        $stmt->execute();

        return ['allowed' => true, 'remaining' => $maxAttempts - $currentAttempts - 1];
    }
}
