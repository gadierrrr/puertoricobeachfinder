<?php
/**
 * Migration 046: Direct advertising platform.
 *
 * Adds the V1 sales/fulfillment model and V2-ready reporting tables, seeds the
 * initial package range, and safely imports any legacy local-listing records.
 * Idempotent and additive: legacy tables remain available during transition.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: advertising platform\n";

$db = getDb();
$db->exec('BEGIN IMMEDIATE');

try {
    $db->exec("CREATE TABLE IF NOT EXISTS advertisers (
        id TEXT PRIMARY KEY,
        business_name TEXT NOT NULL,
        legal_name TEXT,
        contact_name TEXT,
        contact_email TEXT,
        contact_phone TEXT,
        billing_email TEXT,
        website_url TEXT,
        category TEXT NOT NULL DEFAULT 'services',
        status TEXT NOT NULL DEFAULT 'prospect',
        notes TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_advertisers_status ON advertisers(status, created_at)");

    $db->exec("CREATE TABLE IF NOT EXISTS ad_packages (
        id TEXT PRIMARY KEY,
        slug TEXT NOT NULL UNIQUE,
        name_en TEXT NOT NULL,
        name_es TEXT NOT NULL,
        description_en TEXT NOT NULL,
        description_es TEXT NOT NULL,
        price_cents INTEGER NOT NULL,
        currency TEXT NOT NULL DEFAULT 'USD',
        billing_interval TEXT NOT NULL DEFAULT 'month',
        minimum_term_months INTEGER NOT NULL DEFAULT 1,
        placement_type TEXT NOT NULL,
        included_units INTEGER NOT NULL DEFAULT 1,
        exclusive INTEGER NOT NULL DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'active',
        display_order INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_packages_status ON ad_packages(status, display_order)");

    $db->exec("CREATE TABLE IF NOT EXISTS ad_leads (
        id TEXT PRIMARY KEY,
        legacy_lead_id TEXT UNIQUE,
        business_name TEXT NOT NULL,
        contact_name TEXT,
        email TEXT NOT NULL,
        phone TEXT,
        website_url TEXT,
        category TEXT NOT NULL DEFAULT 'services',
        package_slug TEXT,
        target_details TEXT,
        budget_range TEXT,
        preferred_start TEXT,
        message TEXT,
        source_page TEXT,
        locale TEXT NOT NULL DEFAULT 'en',
        consent_contact INTEGER NOT NULL DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'new',
        owner_user_id TEXT,
        advertiser_id TEXT,
        next_follow_up_at TEXT,
        last_contacted_at TEXT,
        lost_reason TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE SET NULL
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_leads_pipeline ON ad_leads(status, created_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_leads_owner ON ad_leads(owner_user_id, next_follow_up_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_leads_email ON ad_leads(email, created_at)");

    $db->exec("CREATE TABLE IF NOT EXISTS ad_campaigns (
        id TEXT PRIMARY KEY,
        advertiser_id TEXT NOT NULL,
        package_id TEXT NOT NULL,
        name TEXT NOT NULL,
        objective TEXT,
        status TEXT NOT NULL DEFAULT 'draft',
        starts_at TEXT,
        ends_at TEXT,
        contracted_amount_cents INTEGER NOT NULL DEFAULT 0,
        currency TEXT NOT NULL DEFAULT 'USD',
        billing_status TEXT NOT NULL DEFAULT 'unbilled',
        approved_by TEXT,
        approved_at TEXT,
        notes TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
        FOREIGN KEY (package_id) REFERENCES ad_packages(id),
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_campaigns_status_dates ON ad_campaigns(status, starts_at, ends_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_campaigns_advertiser ON ad_campaigns(advertiser_id, created_at)");

    $db->exec("CREATE TABLE IF NOT EXISTS ad_creatives (
        id TEXT PRIMARY KEY,
        advertiser_id TEXT NOT NULL,
        name TEXT NOT NULL,
        creative_type TEXT NOT NULL DEFAULT 'listing_card',
        headline_en TEXT NOT NULL,
        headline_es TEXT,
        body_en TEXT,
        body_es TEXT,
        image_url TEXT,
        alt_en TEXT,
        alt_es TEXT,
        destination_url TEXT,
        action_label_en TEXT,
        action_label_es TEXT,
        phone TEXT,
        whatsapp TEXT,
        instagram TEXT,
        status TEXT NOT NULL DEFAULT 'draft',
        approved_by TEXT,
        approved_at TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_creatives_advertiser ON ad_creatives(advertiser_id, status)");

    $db->exec("CREATE TABLE IF NOT EXISTS ad_slots (
        id TEXT PRIMARY KEY,
        slot_key TEXT NOT NULL UNIQUE,
        page_type TEXT NOT NULL,
        name TEXT NOT NULL,
        max_items INTEGER NOT NULL DEFAULT 1,
        exclusive INTEGER NOT NULL DEFAULT 0,
        disclosure_en TEXT NOT NULL DEFAULT 'Paid advertisement',
        disclosure_es TEXT NOT NULL DEFAULT 'Anuncio pagado',
        status TEXT NOT NULL DEFAULT 'active',
        display_order INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_slots_page ON ad_slots(page_type, status)");

    $db->exec("CREATE TABLE IF NOT EXISTS ad_assignments (
        id TEXT PRIMARY KEY,
        campaign_id TEXT NOT NULL,
        creative_id TEXT NOT NULL,
        slot_id TEXT NOT NULL,
        target_type TEXT NOT NULL,
        target_key TEXT NOT NULL,
        locale TEXT NOT NULL DEFAULT 'all',
        priority INTEGER NOT NULL DEFAULT 100,
        display_order INTEGER NOT NULL DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'draft',
        starts_at TEXT,
        ends_at TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (campaign_id) REFERENCES ad_campaigns(id) ON DELETE CASCADE,
        FOREIGN KEY (creative_id) REFERENCES ad_creatives(id) ON DELETE CASCADE,
        FOREIGN KEY (slot_id) REFERENCES ad_slots(id) ON DELETE CASCADE,
        UNIQUE (campaign_id, creative_id, slot_id, target_type, target_key, locale)
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_assignments_lookup ON ad_assignments(slot_id, target_type, target_key, locale, status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_assignments_campaign ON ad_assignments(campaign_id, status)");

    $db->exec("CREATE TABLE IF NOT EXISTS ad_events (
        id TEXT PRIMARY KEY,
        event_type TEXT NOT NULL,
        assignment_id TEXT NOT NULL,
        campaign_id TEXT NOT NULL,
        creative_id TEXT NOT NULL,
        slot_key TEXT NOT NULL,
        page_type TEXT NOT NULL,
        page_key TEXT NOT NULL,
        locale TEXT NOT NULL DEFAULT 'en',
        action TEXT NOT NULL DEFAULT '',
        visitor_hash TEXT,
        dedupe_key TEXT NOT NULL UNIQUE,
        is_valid INTEGER NOT NULL DEFAULT 1,
        occurred_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (assignment_id) REFERENCES ad_assignments(id) ON DELETE CASCADE,
        FOREIGN KEY (campaign_id) REFERENCES ad_campaigns(id) ON DELETE CASCADE,
        FOREIGN KEY (creative_id) REFERENCES ad_creatives(id) ON DELETE CASCADE
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_events_campaign_time ON ad_events(campaign_id, occurred_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_events_assignment_time ON ad_events(assignment_id, occurred_at)");

    $db->exec("CREATE TABLE IF NOT EXISTS ad_metrics_daily (
        metric_date TEXT NOT NULL,
        assignment_id TEXT NOT NULL,
        campaign_id TEXT NOT NULL,
        creative_id TEXT NOT NULL,
        slot_key TEXT NOT NULL,
        page_type TEXT NOT NULL,
        page_key TEXT NOT NULL,
        locale TEXT NOT NULL DEFAULT 'en',
        event_type TEXT NOT NULL,
        action TEXT NOT NULL DEFAULT '',
        valid_events INTEGER NOT NULL DEFAULT 0,
        invalid_events INTEGER NOT NULL DEFAULT 0,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (metric_date, assignment_id, page_key, locale, event_type, action)
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_metrics_campaign_date ON ad_metrics_daily(campaign_id, metric_date)");

    $db->exec("CREATE TABLE IF NOT EXISTS ad_conversions (
        id TEXT PRIMARY KEY,
        campaign_id TEXT NOT NULL,
        click_event_id TEXT,
        external_id TEXT,
        source TEXT NOT NULL DEFAULT 'manual',
        status TEXT NOT NULL DEFAULT 'confirmed',
        value_cents INTEGER NOT NULL DEFAULT 0,
        currency TEXT NOT NULL DEFAULT 'USD',
        occurred_at TEXT,
        notes TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (campaign_id) REFERENCES ad_campaigns(id) ON DELETE CASCADE,
        FOREIGN KEY (click_event_id) REFERENCES ad_events(id) ON DELETE SET NULL,
        UNIQUE (campaign_id, external_id)
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_conversions_campaign ON ad_conversions(campaign_id, occurred_at)");

    $db->exec("CREATE TABLE IF NOT EXISTS ad_delivery_incidents (
        id TEXT PRIMARY KEY,
        campaign_id TEXT NOT NULL,
        assignment_id TEXT,
        started_at TEXT NOT NULL,
        ended_at TEXT,
        cause TEXT NOT NULL DEFAULT 'site',
        missed_days REAL NOT NULL DEFAULT 0,
        resolution TEXT NOT NULL DEFAULT 'extension',
        credit_cents INTEGER NOT NULL DEFAULT 0,
        notes TEXT,
        created_by TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (campaign_id) REFERENCES ad_campaigns(id) ON DELETE CASCADE,
        FOREIGN KEY (assignment_id) REFERENCES ad_assignments(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS ad_audit_log (
        id TEXT PRIMARY KEY,
        actor_user_id TEXT,
        entity_type TEXT NOT NULL,
        entity_id TEXT NOT NULL,
        action TEXT NOT NULL,
        before_json TEXT,
        after_json TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_ad_audit_entity ON ad_audit_log(entity_type, entity_id, created_at)");

    $packages = [
        ['adpkg-standard', 'standard', 'Featured beach listing', 'Listado destacado en playa', 'One paid business card on one relevant beach page.', 'Una tarjeta de negocio pagada en una página de playa relevante.', 4900, 1, 'beach', 1, 0, 10],
        ['adpkg-cluster', 'beach-cluster', 'Nearby beach cluster', 'Grupo de playas cercanas', 'Reach up to three nearby beach pages with one creative.', 'Alcanza hasta tres páginas de playas cercanas con una creatividad.', 9900, 1, 'beach', 3, 0, 20],
        ['adpkg-regional', 'regional-partner', 'Regional partner', 'Socio regional', 'Reach up to ten contextually relevant beach pages.', 'Alcanza hasta diez páginas de playas contextualmente relevantes.', 24900, 3, 'beach', 10, 0, 30],
        ['adpkg-guide', 'sponsored-guide', 'Sponsored guide', 'Guía patrocinada', 'Exclusive paid placement inside one relevant guide.', 'Espacio pagado exclusivo dentro de una guía relevante.', 19900, 3, 'guide', 1, 1, 40],
        ['adpkg-collection', 'collection-sponsor', 'Collection sponsor', 'Patrocinador de colección', 'Exclusive sponsor strip on one collection page.', 'Franja de patrocinio exclusiva en una página de colección.', 29900, 3, 'collection', 1, 1, 50],
    ];
    $pkgStmt = $db->prepare("INSERT INTO ad_packages
        (id, slug, name_en, name_es, description_en, description_es, price_cents,
         minimum_term_months, placement_type, included_units, exclusive, display_order)
        VALUES (:id,:slug,:en,:es,:den,:des,:price,:term,:type,:units,:exclusive,:ord)
        ON CONFLICT(slug) DO UPDATE SET
          name_en=excluded.name_en,name_es=excluded.name_es,
          description_en=excluded.description_en,description_es=excluded.description_es,
          price_cents=excluded.price_cents,minimum_term_months=excluded.minimum_term_months,
          placement_type=excluded.placement_type,included_units=excluded.included_units,
          exclusive=excluded.exclusive,display_order=excluded.display_order,updated_at=CURRENT_TIMESTAMP");
    foreach ($packages as [$id,$slug,$en,$es,$den,$des,$price,$term,$type,$units,$exclusive,$ord]) {
        foreach (compact('id','slug','en','es','den','des','price','term','type','units','exclusive','ord') as $key => $value) {
            $pkgStmt->bindValue(':' . $key, $value);
        }
        $pkgStmt->execute();
    }

    $slots = [
        ['adslot-beach-local', 'beach.local-partners', 'beach', 'Nearby businesses', 2, 0, 10],
        ['adslot-guide-inline', 'guide.inline-sponsor', 'guide', 'Guide sponsor', 1, 1, 20],
        ['adslot-collection-lead', 'collection.lead-sponsor', 'collection', 'Collection sponsor', 1, 1, 30],
    ];
    $slotStmt = $db->prepare("INSERT INTO ad_slots
        (id,slot_key,page_type,name,max_items,exclusive,display_order)
        VALUES (:id,:key,:type,:name,:max,:exclusive,:ord)
        ON CONFLICT(slot_key) DO UPDATE SET page_type=excluded.page_type,name=excluded.name,
          max_items=excluded.max_items,exclusive=excluded.exclusive,display_order=excluded.display_order,
          updated_at=CURRENT_TIMESTAMP");
    foreach ($slots as [$id,$key,$type,$name,$max,$exclusive,$ord]) {
        $slotStmt->bindValue(':id', $id);
        $slotStmt->bindValue(':key', $key);
        $slotStmt->bindValue(':type', $type);
        $slotStmt->bindValue(':name', $name);
        $slotStmt->bindValue(':max', $max, SQLITE3_INTEGER);
        $slotStmt->bindValue(':exclusive', $exclusive, SQLITE3_INTEGER);
        $slotStmt->bindValue(':ord', $ord, SQLITE3_INTEGER);
        $slotStmt->execute();
    }

    $db->exec("CREATE TABLE IF NOT EXISTS site_settings (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("INSERT OR IGNORE INTO site_settings (key,value) VALUES ('advertising_enabled','1')");

    // Import legacy leads once, preserving the original row id for reconciliation.
    if ($db->querySingle("SELECT 1 FROM sqlite_master WHERE type='table' AND name='local_listing_leads'")) {
        $db->exec("INSERT OR IGNORE INTO ad_leads
            (id,legacy_lead_id,business_name,contact_name,email,phone,message,target_details,
             source_page,locale,consent_contact,status,created_at,updated_at)
            SELECT 'adlead-legacy-' || id,id,business_name,contact_name,email,phone,message,
                   beaches_interest,source_page,locale,1,
                   CASE status WHEN 'contacted' THEN 'qualified' WHEN 'won' THEN 'won'
                               WHEN 'lost' THEN 'lost' ELSE 'new' END,
                   created_at,created_at
            FROM local_listing_leads");
    }

    // Import legacy listings into a standard campaign/creative/assignment graph.
    if ($db->querySingle("SELECT 1 FROM sqlite_master WHERE type='table' AND name='local_listings'")) {
        $db->exec("INSERT OR IGNORE INTO advertisers
            (id,business_name,website_url,contact_phone,category,status,notes,created_at,updated_at)
            SELECT 'advertiser-legacy-' || id,business_name,website_url,phone,category,
                   CASE WHEN status='active' THEN 'active' ELSE 'prospect' END,
                   notes,created_at,updated_at FROM local_listings");
        $db->exec("INSERT OR IGNORE INTO ad_campaigns
            (id,advertiser_id,package_id,name,status,starts_at,ends_at,contracted_amount_cents,
             billing_status,notes,created_at,updated_at)
            SELECT 'campaign-legacy-' || id,'advertiser-legacy-' || id,'adpkg-standard',
                   business_name || ' legacy listing',
                   CASE status WHEN 'active' THEN 'active' WHEN 'paused' THEN 'paused'
                               WHEN 'expired' THEN 'ended' ELSE 'draft' END,
                   active_from,active_to,4900,'unbilled',notes,created_at,updated_at
            FROM local_listings");
        $db->exec("INSERT OR IGNORE INTO ad_creatives
            (id,advertiser_id,name,creative_type,headline_en,headline_es,body_en,body_es,
             image_url,alt_en,alt_es,destination_url,phone,whatsapp,instagram,status,
             approved_at,created_at,updated_at)
            SELECT 'creative-legacy-' || id,'advertiser-legacy-' || id,business_name || ' card',
                   'listing_card',business_name,business_name,
                   COALESCE(tagline,description,''),COALESCE(tagline_es,description_es,''),
                   image_url,business_name,business_name,website_url,phone,whatsapp,instagram,
                   CASE WHEN status='active' THEN 'approved' ELSE 'draft' END,
                   CASE WHEN status='active' THEN created_at ELSE NULL END,created_at,updated_at
            FROM local_listings");
        if ($db->querySingle("SELECT 1 FROM sqlite_master WHERE type='table' AND name='local_listing_beaches'")) {
            $db->exec("INSERT OR IGNORE INTO ad_assignments
                (id,campaign_id,creative_id,slot_id,target_type,target_key,locale,priority,
                 display_order,status,starts_at,ends_at,created_at,updated_at)
                SELECT 'assignment-legacy-' || lb.listing_id || '-' || lb.beach_id,
                       'campaign-legacy-' || lb.listing_id,'creative-legacy-' || lb.listing_id,
                       'adslot-beach-local','beach',b.slug,'all',100,lb.display_order,
                       CASE WHEN l.status='active' THEN 'active' ELSE 'draft' END,
                       l.active_from,l.active_to,l.created_at,l.updated_at
                FROM local_listing_beaches lb
                INNER JOIN local_listings l ON l.id=lb.listing_id
                INNER JOIN beaches b ON b.id=lb.beach_id");
        }
    }

    $db->exec('COMMIT');
    echo "Advertising platform ready\n";
} catch (Throwable $e) {
    $db->exec('ROLLBACK');
    fwrite(STDERR, "Advertising migration failed: " . $e->getMessage() . "\n");
    exit(1);
}

