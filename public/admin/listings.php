<?php
/**
 * Admin — Featured local listings + advertise leads.
 *
 * Tabs: listings (CRUD + beach assignment + click stats), leads (pipeline).
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/session.php';
require_once APP_ROOT . '/inc/admin.php';
require_once APP_ROOT . '/inc/listings.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

requireAdmin();

$tab = trim((string) ($_GET['tab'] ?? 'listings'));
if (!in_array($tab, ['listings', 'leads'], true)) {
    $tab = 'listings';
}

function listingsAdminRedirect(string $tab, string $status = 'saved'): void
{
    redirect('/admin/listings?tab=' . urlencode($tab) . '&' . urlencode($status) . '=1');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf((string) ($_POST['csrf_token'] ?? ''))) {
        listingsAdminRedirect($tab, 'csrf_error');
    }

    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'save_listing') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $isNew = $id === '';
        if ($isNew) {
            $id = uuid();
        }

        $businessName = mb_substr(trim((string) ($_POST['business_name'] ?? '')), 0, 120);
        if ($businessName === '') {
            listingsAdminRedirect('listings', 'invalid');
        }

        $category = trim((string) ($_POST['category'] ?? 'food'));
        if (!isset(LOCAL_LISTING_CATEGORIES[$category])) {
            $category = 'food';
        }

        $status = trim((string) ($_POST['status'] ?? 'draft'));
        if (!in_array($status, ['draft', 'active', 'paused', 'expired'], true)) {
            $status = 'draft';
        }

        $tier = trim((string) ($_POST['tier'] ?? 'featured'));
        if (!in_array($tier, ['featured', 'standard'], true)) {
            $tier = 'featured';
        }

        $params = [
            ':id' => $id,
            ':business_name' => $businessName,
            ':category' => $category,
            ':tagline' => mb_substr(trim((string) ($_POST['tagline'] ?? '')), 0, 160),
            ':tagline_es' => mb_substr(trim((string) ($_POST['tagline_es'] ?? '')), 0, 160),
            ':description' => mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 1000),
            ':description_es' => mb_substr(trim((string) ($_POST['description_es'] ?? '')), 0, 1000),
            ':image_url' => mb_substr(trim((string) ($_POST['image_url'] ?? '')), 0, 500),
            ':website_url' => mb_substr(trim((string) ($_POST['website_url'] ?? '')), 0, 500),
            ':instagram' => ltrim(mb_substr(trim((string) ($_POST['instagram'] ?? '')), 0, 60), '@'),
            ':phone' => mb_substr(trim((string) ($_POST['phone'] ?? '')), 0, 30),
            ':whatsapp' => mb_substr(trim((string) ($_POST['whatsapp'] ?? '')), 0, 30),
            ':address' => mb_substr(trim((string) ($_POST['address'] ?? '')), 0, 300),
            ':municipality' => mb_substr(trim((string) ($_POST['municipality'] ?? '')), 0, 60),
            ':tier' => $tier,
            ':status' => $status,
            ':active_from' => trim((string) ($_POST['active_from'] ?? '')) ?: null,
            ':active_to' => trim((string) ($_POST['active_to'] ?? '')) ?: null,
            ':notes' => mb_substr(trim((string) ($_POST['notes'] ?? '')), 0, 2000),
        ];

        if ($isNew) {
            execute(
                'INSERT INTO local_listings
                    (id, business_name, category, tagline, tagline_es, description, description_es,
                     image_url, website_url, instagram, phone, whatsapp, address, municipality,
                     tier, status, active_from, active_to, notes)
                 VALUES
                    (:id, :business_name, :category, :tagline, :tagline_es, :description, :description_es,
                     :image_url, :website_url, :instagram, :phone, :whatsapp, :address, :municipality,
                     :tier, :status, :active_from, :active_to, :notes)',
                $params
            );
        } else {
            execute(
                'UPDATE local_listings SET
                    business_name = :business_name, category = :category,
                    tagline = :tagline, tagline_es = :tagline_es,
                    description = :description, description_es = :description_es,
                    image_url = :image_url, website_url = :website_url, instagram = :instagram,
                    phone = :phone, whatsapp = :whatsapp, address = :address, municipality = :municipality,
                    tier = :tier, status = :status, active_from = :active_from, active_to = :active_to,
                    notes = :notes, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id',
                $params
            );
        }

        // Beach assignment (replace-all semantics).
        $beachIds = $_POST['beach_ids'] ?? [];
        if (!is_array($beachIds)) {
            $beachIds = [];
        }
        execute('DELETE FROM local_listing_beaches WHERE listing_id = :id', [':id' => $id]);
        $order = 0;
        foreach ($beachIds as $beachId) {
            $beachId = trim((string) $beachId);
            if ($beachId === '') {
                continue;
            }
            $exists = queryOne('SELECT id FROM beaches WHERE id = :id', [':id' => $beachId]);
            if (!$exists) {
                continue;
            }
            execute(
                'INSERT OR IGNORE INTO local_listing_beaches (listing_id, beach_id, display_order)
                 VALUES (:listing_id, :beach_id, :ord)',
                [':listing_id' => $id, ':beach_id' => $beachId, ':ord' => $order++]
            );
        }

        listingsAdminRedirect('listings');
    }

    if ($action === 'delete_listing') {
        $id = trim((string) ($_POST['id'] ?? ''));
        if ($id !== '') {
            // Explicit child deletes — don't rely on FK cascade being active.
            execute('DELETE FROM local_listing_beaches WHERE listing_id = :id', [':id' => $id]);
            execute('DELETE FROM local_listing_clicks WHERE listing_id = :id', [':id' => $id]);
            execute('DELETE FROM local_listings WHERE id = :id', [':id' => $id]);
        }
        listingsAdminRedirect('listings', 'deleted');
    }

    if ($action === 'update_lead') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'new'));
        if (!in_array($status, ['new', 'contacted', 'won', 'lost'], true)) {
            $status = 'new';
        }
        if ($id !== '') {
            execute('UPDATE local_listing_leads SET status = :status WHERE id = :id', [':status' => $status, ':id' => $id]);
        }
        listingsAdminRedirect('leads');
    }

    listingsAdminRedirect($tab, 'invalid');
}

// ---------------------------------------------------------------------------
// Data for render
// ---------------------------------------------------------------------------

$editId = trim((string) ($_GET['edit'] ?? ''));
$editListing = null;
$editBeachIds = [];
if ($editId !== '') {
    $editListing = queryOne('SELECT * FROM local_listings WHERE id = :id', [':id' => $editId]);
    if ($editListing) {
        $editBeachIds = array_column(
            query('SELECT beach_id FROM local_listing_beaches WHERE listing_id = :id ORDER BY display_order', [':id' => $editId]),
            'beach_id'
        );
    }
}

$listings = query(
    'SELECT l.*,
            (SELECT COUNT(*) FROM local_listing_beaches lb WHERE lb.listing_id = l.id) AS beach_count,
            (SELECT COUNT(*) FROM local_listing_clicks lc WHERE lc.listing_id = l.id
                AND lc.clicked_at >= datetime("now", "-30 days")) AS clicks_30d
     FROM local_listings l
     ORDER BY l.created_at DESC'
);

$leads = query('SELECT * FROM local_listing_leads ORDER BY created_at DESC LIMIT 200');
$newLeadCount = 0;
foreach ($leads as $l) {
    if (($l['status'] ?? '') === 'new') {
        $newLeadCount++;
    }
}

$allBeaches = query(
    'SELECT id, name, municipality FROM beaches
     WHERE publish_status = "published" AND (location_type = "beach" OR location_type IS NULL)
     ORDER BY municipality, name'
);

$pageTitle = 'Local Listings';
include __DIR__ . '/components/header.php';
?>

<div class="p-6 max-w-6xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Local Listings</h1>
        <a href="/admin/listings?tab=listings&edit=new" class="rounded-lg bg-blue-600 hover:bg-blue-700 px-4 py-2 text-sm font-semibold text-white">+ New listing</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">Saved.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="mb-4 rounded-lg bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-800">Deleted.</div>
    <?php elseif (isset($_GET['invalid'])): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">Invalid input — business name is required.</div>
    <?php elseif (isset($_GET['csrf_error'])): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">Session expired — try again.</div>
    <?php endif; ?>

    <div class="mb-6 flex gap-1 border-b border-gray-200">
        <a href="/admin/listings?tab=listings"
           class="px-4 py-2 text-sm font-semibold <?= $tab === 'listings' ? 'border-b-2 border-blue-600 text-blue-700' : 'text-gray-500 hover:text-gray-800' ?>">
            Listings (<?= count($listings) ?>)
        </a>
        <a href="/admin/listings?tab=leads"
           class="px-4 py-2 text-sm font-semibold <?= $tab === 'leads' ? 'border-b-2 border-blue-600 text-blue-700' : 'text-gray-500 hover:text-gray-800' ?>">
            Leads (<?= count($leads) ?>)<?= $newLeadCount > 0 ? ' · ' . $newLeadCount . ' new' : '' ?>
        </a>
    </div>

<?php if ($tab === 'listings'): ?>

    <?php if ($editId !== '' && ($editListing || $editId === 'new')): ?>
    <?php $l = $editListing ?? []; ?>
    <div class="mb-8 rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4"><?= $editListing ? 'Edit: ' . h($l['business_name']) : 'New listing' ?></h2>
        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="save_listing">
            <input type="hidden" name="id" value="<?= h($editListing ? $l['id'] : '') ?>">

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Business name *</label>
                    <input name="business_name" required value="<?= h($l['business_name'] ?? '') ?>" class="w-full rounded border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
                    <select name="category" class="w-full rounded border-gray-300 text-sm">
                        <?php foreach (LOCAL_LISTING_CATEGORIES as $key => $cat): ?>
                        <option value="<?= h($key) ?>" <?= ($l['category'] ?? 'food') === $key ? 'selected' : '' ?>><?= h($cat['icon'] . ' ' . $cat['en']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Municipality</label>
                    <input name="municipality" value="<?= h($l['municipality'] ?? '') ?>" class="w-full rounded border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tagline (EN)</label>
                    <input name="tagline" value="<?= h($l['tagline'] ?? '') ?>" maxlength="160" class="w-full rounded border-gray-300 text-sm" placeholder="Best fish tacos steps from the sand">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tagline (ES)</label>
                    <input name="tagline_es" value="<?= h($l['tagline_es'] ?? '') ?>" maxlength="160" class="w-full rounded border-gray-300 text-sm" placeholder="Los mejores tacos de pescado a pasos de la arena">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Image URL</label>
                    <input name="image_url" value="<?= h($l['image_url'] ?? '') ?>" class="w-full rounded border-gray-300 text-sm" placeholder="/images/listings/....webp">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Website URL</label>
                    <input name="website_url" value="<?= h($l['website_url'] ?? '') ?>" class="w-full rounded border-gray-300 text-sm" placeholder="https://...">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Instagram handle</label>
                    <input name="instagram" value="<?= h($l['instagram'] ?? '') ?>" class="w-full rounded border-gray-300 text-sm" placeholder="mybusiness">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Phone</label>
                    <input name="phone" value="<?= h($l['phone'] ?? '') ?>" class="w-full rounded border-gray-300 text-sm" placeholder="+1787...">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">WhatsApp (digits only)</label>
                    <input name="whatsapp" value="<?= h($l['whatsapp'] ?? '') ?>" class="w-full rounded border-gray-300 text-sm" placeholder="1787...">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tier</label>
                    <select name="tier" class="w-full rounded border-gray-300 text-sm">
                        <option value="featured" <?= ($l['tier'] ?? 'featured') === 'featured' ? 'selected' : '' ?>>Featured</option>
                        <option value="standard" <?= ($l['tier'] ?? '') === 'standard' ? 'selected' : '' ?>>Standard</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full rounded border-gray-300 text-sm">
                        <?php foreach (['draft', 'active', 'paused', 'expired'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($l['status'] ?? 'draft') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Active from (YYYY-MM-DD)</label>
                    <input name="active_from" value="<?= h($l['active_from'] ?? '') ?>" class="w-full rounded border-gray-300 text-sm" placeholder="2026-07-01">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Active to (YYYY-MM-DD)</label>
                    <input name="active_to" value="<?= h($l['active_to'] ?? '') ?>" class="w-full rounded border-gray-300 text-sm" placeholder="2026-08-01">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Address</label>
                    <input name="address" value="<?= h($l['address'] ?? '') ?>" class="w-full rounded border-gray-300 text-sm">
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Description (EN)</label>
                    <textarea name="description" rows="3" class="w-full rounded border-gray-300 text-sm"><?= h($l['description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Description (ES)</label>
                    <textarea name="description_es" rows="3" class="w-full rounded border-gray-300 text-sm"><?= h($l['description_es'] ?? '') ?></textarea>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    Beaches (Cmd/Ctrl-click to select multiple — the listing shows on these beach pages)
                </label>
                <select name="beach_ids[]" multiple size="12" class="w-full rounded border-gray-300 text-sm">
                    <?php foreach ($allBeaches as $b): ?>
                    <option value="<?= h($b['id']) ?>" <?= in_array($b['id'], $editBeachIds, true) ? 'selected' : '' ?>>
                        <?= h($b['municipality'] . ' — ' . $b['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Internal notes (deal terms, billing)</label>
                <textarea name="notes" rows="2" class="w-full rounded border-gray-300 text-sm"><?= h($l['notes'] ?? '') ?></textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-blue-600 hover:bg-blue-700 px-5 py-2 text-sm font-semibold text-white">Save listing</button>
                <a href="/admin/listings?tab=listings" class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Business</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Beaches</th>
                    <th class="px-4 py-3">Clicks (30d)</th>
                    <th class="px-4 py-3">Window</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (!$listings): ?>
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No listings yet. Leads from /advertise land in the Leads tab.</td></tr>
                <?php endif; ?>
                <?php foreach ($listings as $row): ?>
                <tr>
                    <td class="px-4 py-3 font-semibold text-gray-900"><?= h($row['business_name']) ?></td>
                    <td class="px-4 py-3"><?= h(listingCategoryIcon($row['category']) . ' ' . listingCategoryLabel($row['category'], 'en')) ?></td>
                    <td class="px-4 py-3">
                        <?php $sc = ['active' => 'bg-green-100 text-green-800', 'draft' => 'bg-gray-100 text-gray-700', 'paused' => 'bg-yellow-100 text-yellow-800', 'expired' => 'bg-red-100 text-red-700']; ?>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= $sc[$row['status']] ?? 'bg-gray-100 text-gray-700' ?>"><?= h($row['status']) ?></span>
                    </td>
                    <td class="px-4 py-3"><?= (int) $row['beach_count'] ?></td>
                    <td class="px-4 py-3"><?= (int) $row['clicks_30d'] ?></td>
                    <td class="px-4 py-3 text-xs text-gray-500"><?= h(($row['active_from'] ?: '—') . ' → ' . ($row['active_to'] ?: '—')) ?></td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="/admin/listings?tab=listings&edit=<?= h($row['id']) ?>" class="text-blue-600 hover:underline text-xs font-semibold">Edit</a>
                        <form method="post" class="inline ml-2" data-confirm="Delete this listing?">
                            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                            <input type="hidden" name="action" value="delete_listing">
                            <input type="hidden" name="id" value="<?= h($row['id']) ?>">
                            <button type="submit" class="text-red-600 hover:underline text-xs font-semibold">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Received</th>
                    <th class="px-4 py-3">Business</th>
                    <th class="px-4 py-3">Contact</th>
                    <th class="px-4 py-3">Beaches</th>
                    <th class="px-4 py-3">Message</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (!$leads): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No leads yet. Share /advertise with local businesses to get started.</td></tr>
                <?php endif; ?>
                <?php foreach ($leads as $lead): ?>
                <tr class="<?= $lead['status'] === 'new' ? 'bg-blue-50/40' : '' ?>">
                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap"><?= h(substr((string) $lead['created_at'], 0, 16)) ?></td>
                    <td class="px-4 py-3 font-semibold text-gray-900"><?= h($lead['business_name']) ?></td>
                    <td class="px-4 py-3">
                        <div><?= h($lead['contact_name'] ?: '—') ?></div>
                        <div class="text-xs"><a class="text-blue-600 hover:underline" href="mailto:<?= h($lead['email']) ?>"><?= h($lead['email']) ?></a></div>
                        <?php if (!empty($lead['phone'])): ?><div class="text-xs text-gray-500"><?= h($lead['phone']) ?></div><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-xs"><?= h($lead['beaches_interest'] ?: '—') ?></td>
                    <td class="px-4 py-3 text-xs text-gray-600 max-w-xs"><?= h(mb_substr((string) $lead['message'], 0, 200)) ?></td>
                    <td class="px-4 py-3">
                        <form method="post" class="flex items-center gap-1">
                            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                            <input type="hidden" name="action" value="update_lead">
                            <input type="hidden" name="id" value="<?= h($lead['id']) ?>">
                            <select name="status" class="rounded border-gray-300 text-xs">
                                <?php foreach (['new', 'contacted', 'won', 'lost'] as $s): ?>
                                <option value="<?= $s ?>" <?= $lead['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="rounded bg-gray-800 px-2 py-1 text-xs font-semibold text-white hover:bg-gray-700">Set</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>
</div>

<script <?= cspNonceAttr() ?>>
document.addEventListener('submit', function (e) {
    var form = e.target.closest('form[data-confirm]');
    if (form && !window.confirm(form.getAttribute('data-confirm'))) {
        e.preventDefault();
    }
});
</script>

<?php include __DIR__ . '/components/footer.php'; ?>
