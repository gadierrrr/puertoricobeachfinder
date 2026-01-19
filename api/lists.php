<?php
/**
 * Beach Lists API
 *
 * Manage custom beach collections
 */

require_once __DIR__ . '/../inc/session.php';
session_start();
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Require authentication for all list operations
if (!isAuthenticated()) {
    jsonResponse(['error' => 'Please sign in to manage lists'], 401);
}

$userId = $_SESSION['user_id'];

switch ($action) {
    case 'create':
        createList($userId);
        break;
    case 'update':
        updateList($userId);
        break;
    case 'delete':
        deleteList($userId);
        break;
    case 'add-beach':
        addBeachToList($userId);
        break;
    case 'remove-beach':
        removeBeachFromList($userId);
        break;
    case 'get':
        getList($userId);
        break;
    case 'get-all':
    default:
        getUserLists($userId);
        break;
}

function createList($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isPublic = isset($_POST['is_public']) && $_POST['is_public'] === '1' ? 1 : 0;

    if (!$name) {
        jsonResponse(['error' => 'List name is required'], 400);
    }

    if (strlen($name) > 100) {
        jsonResponse(['error' => 'List name too long (max 100 characters)'], 400);
    }

    // Generate unique slug
    $baseSlug = slugify($name);
    $slug = $baseSlug;
    $counter = 1;
    while (queryOne('SELECT id FROM beach_lists WHERE slug = :slug', [':slug' => $slug])) {
        $slug = $baseSlug . '-' . $counter++;
    }

    $result = execute("
        INSERT INTO beach_lists (user_id, name, description, is_public, slug, created_at, updated_at)
        VALUES (:user_id, :name, :description, :is_public, :slug, datetime('now'), datetime('now'))
    ", [
        ':user_id' => $userId,
        ':name' => $name,
        ':description' => $description ?: null,
        ':is_public' => $isPublic,
        ':slug' => $slug
    ]);

    if ($result) {
        $listId = getDB()->lastInsertRowID();
        jsonResponse([
            'success' => true,
            'message' => 'List created!',
            'list' => [
                'id' => $listId,
                'slug' => $slug,
                'name' => $name
            ]
        ]);
    } else {
        jsonResponse(['error' => 'Failed to create list'], 500);
    }
}

function updateList($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    $listId = intval($_POST['list_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isPublic = isset($_POST['is_public']) && $_POST['is_public'] === '1' ? 1 : 0;

    // Verify ownership
    $list = queryOne('SELECT id FROM beach_lists WHERE id = :id AND user_id = :user_id', [
        ':id' => $listId,
        ':user_id' => $userId
    ]);

    if (!$list) {
        jsonResponse(['error' => 'List not found'], 404);
    }

    if (!$name) {
        jsonResponse(['error' => 'List name is required'], 400);
    }

    $result = execute("
        UPDATE beach_lists
        SET name = :name, description = :description, is_public = :is_public, updated_at = datetime('now')
        WHERE id = :id AND user_id = :user_id
    ", [
        ':name' => $name,
        ':description' => $description ?: null,
        ':is_public' => $isPublic,
        ':id' => $listId,
        ':user_id' => $userId
    ]);

    jsonResponse(['success' => true, 'message' => 'List updated!']);
}

function deleteList($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    $listId = intval($_POST['list_id'] ?? 0);

    // Verify ownership
    $list = queryOne('SELECT id FROM beach_lists WHERE id = :id AND user_id = :user_id', [
        ':id' => $listId,
        ':user_id' => $userId
    ]);

    if (!$list) {
        jsonResponse(['error' => 'List not found'], 404);
    }

    execute('DELETE FROM beach_lists WHERE id = :id', [':id' => $listId]);

    jsonResponse(['success' => true, 'message' => 'List deleted']);
}

function addBeachToList($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    $listId = intval($_POST['list_id'] ?? 0);
    $beachId = $_POST['beach_id'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    // Verify list ownership
    $list = queryOne('SELECT id, name FROM beach_lists WHERE id = :id AND user_id = :user_id', [
        ':id' => $listId,
        ':user_id' => $userId
    ]);

    if (!$list) {
        jsonResponse(['error' => 'List not found'], 404);
    }

    // Verify beach exists
    $beach = queryOne('SELECT id, name FROM beaches WHERE id = :id', [':id' => $beachId]);
    if (!$beach) {
        jsonResponse(['error' => 'Beach not found'], 404);
    }

    // Check if already in list
    $existing = queryOne('SELECT id FROM beach_list_items WHERE list_id = :list_id AND beach_id = :beach_id', [
        ':list_id' => $listId,
        ':beach_id' => $beachId
    ]);

    if ($existing) {
        jsonResponse(['error' => 'Beach is already in this list'], 400);
    }

    // Get next position
    $maxPos = queryOne('SELECT MAX(position) as pos FROM beach_list_items WHERE list_id = :list_id', [':list_id' => $listId]);
    $position = ($maxPos['pos'] ?? 0) + 1;

    $result = execute("
        INSERT INTO beach_list_items (list_id, beach_id, position, notes, added_at)
        VALUES (:list_id, :beach_id, :position, :notes, datetime('now'))
    ", [
        ':list_id' => $listId,
        ':beach_id' => $beachId,
        ':position' => $position,
        ':notes' => $notes ?: null
    ]);

    if ($result) {
        jsonResponse([
            'success' => true,
            'message' => "Added to \"{$list['name']}\"!"
        ]);
    } else {
        jsonResponse(['error' => 'Failed to add beach'], 500);
    }
}

function removeBeachFromList($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfToken)) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    $listId = intval($_POST['list_id'] ?? 0);
    $beachId = $_POST['beach_id'] ?? '';

    // Verify list ownership
    $list = queryOne('SELECT id FROM beach_lists WHERE id = :id AND user_id = :user_id', [
        ':id' => $listId,
        ':user_id' => $userId
    ]);

    if (!$list) {
        jsonResponse(['error' => 'List not found'], 404);
    }

    execute('DELETE FROM beach_list_items WHERE list_id = :list_id AND beach_id = :beach_id', [
        ':list_id' => $listId,
        ':beach_id' => $beachId
    ]);

    jsonResponse(['success' => true, 'message' => 'Beach removed from list']);
}

function getList($userId) {
    $listId = intval($_GET['list_id'] ?? 0);
    $slug = $_GET['slug'] ?? '';

    if ($slug) {
        $list = queryOne("
            SELECT l.*, u.name as owner_name
            FROM beach_lists l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE l.slug = :slug AND (l.user_id = :user_id OR l.is_public = 1)
        ", [':slug' => $slug, ':user_id' => $userId]);
    } else {
        $list = queryOne("
            SELECT l.*, u.name as owner_name
            FROM beach_lists l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE l.id = :id AND (l.user_id = :user_id OR l.is_public = 1)
        ", [':id' => $listId, ':user_id' => $userId]);
    }

    if (!$list) {
        jsonResponse(['error' => 'List not found'], 404);
    }

    // Get beaches in list
    $beaches = query("
        SELECT b.*, li.notes as list_notes, li.position
        FROM beach_list_items li
        INNER JOIN beaches b ON li.beach_id = b.id
        WHERE li.list_id = :list_id
        ORDER BY li.position
    ", [':list_id' => $list['id']]);

    if (!empty($beaches)) {
        attachBeachMetadata($beaches);
    }

    $list['beaches'] = $beaches;
    $list['beach_count'] = count($beaches);
    $list['is_owner'] = $list['user_id'] === $userId;

    jsonResponse($list);
}

function getUserLists($userId) {
    $lists = query("
        SELECT l.*, COUNT(li.id) as beach_count
        FROM beach_lists l
        LEFT JOIN beach_list_items li ON l.id = li.list_id
        WHERE l.user_id = :user_id
        GROUP BY l.id
        ORDER BY l.updated_at DESC
    ", [':user_id' => $userId]);

    jsonResponse($lists);
}
