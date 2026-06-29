<?php
/**
 * ⚠️ INACTIVE / WIP — unshipped redesign, not wired into any live page.
 *    Do not build on without reviving. See docs/mobile-homepage-bug-report.md.
 *
 * Beach Detail: Plan-your-day button row (wireframe)
 * Four action buttons: Add to itinerary / Book stay / Book tour / Rent car.
 *
 * Expects: $beach, $lang, $beachReferralPlanDay (optional)
 */
$beachReferralPlanDay = $beachReferralPlanDay ?? '';
?>
<section class="plan-row-block" aria-label="<?= h(__('beach.plan_your_day')) ?>">
    <div class="plan-row">
        <button type="button" class="btn-plan btn-plan--primary"
                data-action="addBeachToItinerary"
                data-action-args='["<?= h($beach['id']) ?>","<?= h(addslashes($beach['name'])) ?>"]'>
            <i data-lucide="plus" class="w-4 h-4" aria-hidden="true"></i>
            <?= h(__('beach.plan_add_to_itinerary')) ?>
        </button>
        <a href="https://www.booking.com/searchresults.html?ss=<?= urlencode($beach['municipality'] . ', Puerto Rico') ?>&aid=homepage-prbf"
           target="_blank" rel="sponsored nofollow noopener" class="btn-plan"
           data-bf-track="plan_book_stay"
           data-bf-beach-id="<?= h($beach['id']) ?>">
            <i data-lucide="bed" class="w-4 h-4" aria-hidden="true"></i>
            <?= h(__('beach.plan_book_stay')) ?>
        </a>
        <a href="https://www.viator.com/searchResults/all?text=<?= urlencode($beach['name'] . ' Puerto Rico') ?>"
           target="_blank" rel="sponsored nofollow noopener" class="btn-plan"
           data-bf-track="plan_book_tour"
           data-bf-beach-id="<?= h($beach['id']) ?>">
            <i data-lucide="ship" class="w-4 h-4" aria-hidden="true"></i>
            <?= h(__('beach.plan_book_tour')) ?>
        </a>
        <a href="https://www.discovercars.com/puerto-rico"
           target="_blank" rel="sponsored nofollow noopener" class="btn-plan"
           data-bf-track="plan_rent_car"
           data-bf-beach-id="<?= h($beach['id']) ?>">
            <i data-lucide="car" class="w-4 h-4" aria-hidden="true"></i>
            <?= h(__('beach.plan_rent_car')) ?>
        </a>
    </div>
    <?php if ($beachReferralPlanDay !== ''): ?>
    <div class="mt-4"><?= $beachReferralPlanDay ?></div>
    <?php endif; ?>
</section>
