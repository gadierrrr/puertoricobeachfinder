<?php
/**
 * Beach Detail: Visitor Photos Section
 * Gallery grid with user photo uploads. Only rendered when photos exist.
 *
 * Expects: $beach, $lang, $hasPhotos (checked by parent)
 */
?>
            <!-- Visitor Photos - Hidden when empty -->
            <?php $hasPhotos = !empty($beach['gallery']) || !empty($userPhotos ?? []); ?>
            <?php if ($hasPhotos): ?>
            <section id="user-photos">
                <?php
                $userPhotos = query("SELECT p.id, p.filename, p.caption, p.created_at, u.name as user_name FROM beach_photos p LEFT JOIN users u ON p.user_id = u.id WHERE p.beach_id = :beach_id AND p.status = 'published' ORDER BY p.created_at DESC LIMIT 12", [':beach_id' => $beach['id']]);
                $totalUserPhotos = queryOne("SELECT COUNT(*) as count FROM beach_photos WHERE beach_id = :beach_id AND status = 'published'", [':beach_id' => $beach['id']]);
                ?>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <i data-lucide="camera" class="w-5 h-5 text-purple-400" aria-hidden="true"></i>
                        <span><?= h(__('beach.visitor_photos')) ?></span>
                        <?php if ($totalUserPhotos['count'] > 0): ?>
                        <span class="text-sm font-normal text-gray-400">(<?= $totalUserPhotos['count'] ?>)</span>
                        <?php endif; ?>
                    </h2>
                    <?php if (isAuthenticated()): ?>
                    <button data-action="openPhotoUploadModal" data-action-args='["<?= h($beach['id']) ?>","<?= h(addslashes($beach['name'])) ?>"]'
                            class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1.5 text-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span><?= __('beach.add_photo') ?></span>
                    </button>
                    <?php else: ?>
                    <a href="<?= h(routeUrl('login', $lang)) ?>?redirect=<?= urlencode(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']]) . '#user-photos') ?>"
                       class="text-sm text-purple-400 hover:text-purple-300 font-medium"><?= h(__('beach.sign_in_to_add')) ?></a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($userPhotos)): ?>
                <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                    <?php foreach ($userPhotos as $photo): ?>
                    <button data-action="openPhotoModal" data-action-args='["/uploads/photos/<?= h($photo['filename']) ?>","<?= h(addslashes($photo['caption'] ?? '')) ?>"]'
                            class="aspect-square rounded-lg overflow-hidden hover:opacity-90 transition-opacity">
                        <img src="/uploads/photos/thumbs/<?= h($photo['filename']) ?>" alt="<?= h($photo['caption'] ?? 'Visitor photo') ?>" class="w-full h-full object-cover" loading="lazy">
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-gray-400"><?= h(__('beach.no_photos_yet')) ?></p>
                <?php endif; ?>
            </section>

            <?php endif; // hasPhotos ?>
