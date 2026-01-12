<?php
// inc/db.php - Database connection and helpers

function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key)."=".trim($value));
    }
}

loadEnv(__DIR__ . '/../.env');

function getDB() {
    static $db = null;
    if ($db === null) {
        $dbPath = $_ENV['DB_PATH'] ?? '/var/www/beach-finder/data/beach-finder.db';
        $db = new SQLite3($dbPath);
        $db->exec('PRAGMA journal_mode=WAL;');
        $db->exec('PRAGMA foreign_keys=ON;');
        $db->exec('PRAGMA busy_timeout=5000;');
    }
    return $db;
}

function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function query($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        error_log("SQL Error: " . $db->lastErrorMsg());
        return false;
    }
    foreach ($params as $key => $value) {
        $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value);
    }
    $result = $stmt->execute();
    if (!$result) {
        error_log("Query Error: " . $db->lastErrorMsg());
        return false;
    }
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

function queryOne($sql, $params = []) {
    $rows = query($sql, $params);
    return $rows ? ($rows[0] ?? null) : null;
}

function execute($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        error_log("SQL Error: " . $db->lastErrorMsg());
        return false;
    }
    foreach ($params as $key => $value) {
        $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value);
    }
    $result = $stmt->execute();
    return $result !== false;
}

/**
 * Batch fetch tags for multiple beaches
 * @param array $beachIds Array of beach IDs
 * @return array Keyed by beach_id => [tags]
 */
function batchGetTags($beachIds) {
    if (empty($beachIds)) return [];
    $placeholders = implode(',', array_fill(0, count($beachIds), '?'));
    $rows = query("SELECT beach_id, tag FROM beach_tags WHERE beach_id IN ($placeholders)", array_values($beachIds));
    $result = array_fill_keys($beachIds, []);
    foreach ($rows as $row) {
        $result[$row['beach_id']][] = $row['tag'];
    }
    return $result;
}

/**
 * Batch fetch amenities for multiple beaches
 * @param array $beachIds Array of beach IDs
 * @return array Keyed by beach_id => [amenities]
 */
function batchGetAmenities($beachIds) {
    if (empty($beachIds)) return [];
    $placeholders = implode(',', array_fill(0, count($beachIds), '?'));
    $rows = query("SELECT beach_id, amenity FROM beach_amenities WHERE beach_id IN ($placeholders)", array_values($beachIds));
    $result = array_fill_keys($beachIds, []);
    foreach ($rows as $row) {
        $result[$row['beach_id']][] = $row['amenity'];
    }
    return $result;
}

/**
 * Attach tags and amenities to beaches array efficiently
 * @param array &$beaches Array of beach records (modified in place)
 */
function attachBeachMetadata(&$beaches) {
    if (empty($beaches)) return;
    $beachIds = array_column($beaches, 'id');
    $allTags = batchGetTags($beachIds);
    $allAmenities = batchGetAmenities($beachIds);
    foreach ($beaches as &$beach) {
        $beach['tags'] = $allTags[$beach['id']] ?? [];
        $beach['amenities'] = $allAmenities[$beach['id']] ?? [];
    }
}
