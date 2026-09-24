<?php
/** September desk-review corrections. Before-images are retained for every affected record. */
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/practical_information_migration.php';
$data=json_decode(file_get_contents(__DIR__.'/../config/curation/practical-information-2026-09.json'),true,512,JSON_THROW_ON_ERROR);
try {
    $stats=applyPracticalInformationReview(getDb(),$data);
    echo json_encode($stats,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
} catch (Throwable $e) {
    fwrite(STDERR,"Practical information migration failed: ".$e->getMessage()."\n"); exit(1);
}
