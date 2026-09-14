<?php
require_once __DIR__.'/../inc/practical_information_migration.php';
require_once __DIR__.'/../components/seo-schemas.php';
$testLanguage='en';
function getCurrentLanguage(): string { global $testLanguage; return $testLanguage; }
function checkReview(bool $ok,string $message): void { if (!$ok) throw new RuntimeException($message); }
$data=json_decode(file_get_contents(__DIR__.'/../config/curation/practical-information-2026-09.json'),true,512,JSON_THROW_ON_ERROR);
$db=new SQLite3(':memory:');
$db->exec('CREATE TABLE beaches (id TEXT PRIMARY KEY,slug TEXT,name TEXT,municipality TEXT,lat REAL,lng REAL,description TEXT,description_es TEXT,parking_details TEXT,parking_details_es TEXT,access_label TEXT,safety_info TEXT,safety_info_es TEXT,best_time TEXT,best_time_es TEXT,local_tips TEXT,local_tips_es TEXT,notes TEXT,notes_es TEXT,seo_title TEXT,seo_title_es TEXT,seo_description TEXT,seo_description_es TEXT,safe_for_children INTEGER,swim_difficulty INTEGER,has_lifeguard INTEGER,publish_status TEXT,updated_at TEXT)');
$db->exec('CREATE TABLE beach_content_sections (id TEXT PRIMARY KEY,beach_id TEXT,section_type TEXT,heading TEXT,heading_es TEXT,content TEXT,content_es TEXT,status TEXT,version INTEGER,display_order INTEGER,metadata TEXT,UNIQUE(beach_id,section_type,version))');
foreach (['beach_tips'=>'tip TEXT','beach_features'=>'title TEXT','beach_tags'=>'tag TEXT','beach_amenities'=>'amenity TEXT'] as $t=>$c) $db->exec("CREATE TABLE $t (beach_id TEXT,$c)");
foreach ($data['beaches'] as $i=>$copy) {
    $id=(string)$i; $s=$db->prepare("INSERT INTO beaches (id,slug,name,municipality,description,description_es,local_tips,has_lifeguard,safe_for_children,publish_status) VALUES (:id,:slug,'Fixture','Town','Old claim','Texto viejo','Old tip',1,1,'published')");
    $s->bindValue(':id',$id);$s->bindValue(':slug',$copy['slug']);$s->execute();
    $db->exec("INSERT INTO beach_tips VALUES ('$id','Parking free and always safe')");
    $db->exec("INSERT INTO beach_features VALUES ('$id','Lifeguards always present')");
    $db->exec("INSERT INTO beach_tags VALUES ('$id','fishing'),('$id','family-friendly')");
    $db->exec("INSERT INTO beach_amenities VALUES ('$id','free-parking'),('$id','lifeguard')");
    $db->exec("INSERT INTO beach_content_sections VALUES ('old-$id','$id','local_tips','Old','Viejo','Old contradictory prose','Texto contradictorio','published',1,1,NULL)");
}
$stats=applyPracticalInformationReview($db,$data);
checkReview($stats['updated']===20 && count($stats['held'])===4,'all 20, four identity holds');
checkReview((int)$db->querySingle('SELECT COUNT(*) FROM practical_information_backups')===20,'backups');
checkReview((int)$db->querySingle('SELECT COUNT(*) FROM beach_tips')===0,'old tips removed');
checkReview((int)$db->querySingle("SELECT COUNT(*) FROM beach_content_sections WHERE status='published'")===20,'one reviewed section each');
checkReview((int)$db->querySingle("SELECT COUNT(*) FROM beach_content_sections WHERE status='draft'")===20,'old sections retained');
$before=json_decode($db->querySingle("SELECT before_json FROM practical_information_backups WHERE beach_id='0'"),true);
checkReview($before['beaches']['description']==='Old claim' && count($before['beach_tips'])===1,'recoverable before-images');
checkReview(applyPracticalInformationReview($db,$data)['already_applied']===20,'idempotent without clobbering later edits');
foreach (['en','es'] as $language) {
    $testLanguage=$language;
    $rows=$db->query('SELECT * FROM beaches');
    while ($beach=$rows->fetchArray(SQLITE3_ASSOC)) {
        $faqs=generateBeachFAQs($beach);
        checkReview(count($faqs)===5,'reviewed FAQ count');
        checkReview($faqs[0]['answer']===$beach[$language==='es'?'description_es':'description'],'FAQ language');
        checkReview(generateAtAGlanceSummary($beach,$language)===$faqs[0]['answer'],'summary agrees');
        $schema=faqSchema($faqs);
        preg_match('#<script[^>]*>(.*?)</script>#s',$schema,$match);
        $decoded=json_decode($match[1],true,512,JSON_THROW_ON_ERROR);
        checkReview($decoded['mainEntity'][0]['acceptedAnswer']['text']===$faqs[0]['answer'],'visible/schema parity');
        checkReview(!str_contains(json_encode($faqs),'Old claim'),'old prose absent');
    }
}
// A failure after the schema change must leave no partial update or archive.
$broken=new SQLite3(':memory:');$broken->exec('CREATE TABLE beaches (id TEXT,slug TEXT)');
$broken->exec("INSERT INTO beaches VALUES ('x','playa-de-vega')");
try { applyPracticalInformationReview($broken,$data); throw new RuntimeException('Expected failure'); }
catch (Exception $e) { checkReview($e->getMessage()!=='Expected failure','failure surfaced'); }
checkReview((int)$broken->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE name='practical_information_backups'")===0,'transaction rollback');
echo "PASS: 20 corrections, 4 identity holds, backups, idempotency, rollback, EN/ES FAQ and JSON-LD parity.\n";
