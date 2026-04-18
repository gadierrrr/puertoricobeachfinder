<?php
/**
 * Beach Detail: Referral Hero Slot
 * Renders the optional referral hero placement above the section nav.
 *
 * Expects: $beachReferralHero
 */
?>
    <?php if ($beachReferralHero !== ''): ?>
    <div class="mb-6">
        <?= $beachReferralHero ?>
    </div>
    <?php endif; ?>
