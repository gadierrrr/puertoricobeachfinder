<?php
/** Apply the reviewed September content atomically, keeping full before-images. */
function applyPracticalInformationReview(SQLite3 $db, array $data): array
{
    $db->enableExceptions(true);
    $db->exec('BEGIN IMMEDIATE');
    try {
        $columns = [];
        $result = $db->query('PRAGMA table_info(beaches)');
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $columns[] = $row['name'];
        if (!in_array('practical_reviewed_at', $columns, true)) {
            $db->exec('ALTER TABLE beaches ADD COLUMN practical_reviewed_at TEXT');
        }
        if (!in_array('local_tips_es', $columns, true)) {
            $db->exec('ALTER TABLE beaches ADD COLUMN local_tips_es TEXT');
            $columns[] = 'local_tips_es';
        }
        $db->exec('CREATE TABLE IF NOT EXISTS practical_information_backups (
            beach_id TEXT PRIMARY KEY, reviewed_at TEXT NOT NULL, before_json TEXT NOT NULL
        )');
        $fetch = static function (string $sql, array $params = []) use ($db): array {
            $s = $db->prepare($sql);
            foreach ($params as $k => $v) $s->bindValue($k, $v);
            $r = $s->execute(); $rows = [];
            while ($row = $r->fetchArray(SQLITE3_ASSOC)) $rows[] = $row;
            return $rows;
        };
        $run = static function (string $sql, array $params = []) use ($db): void {
            $s = $db->prepare($sql);
            foreach ($params as $k => $v) $s->bindValue($k, $v);
            $s->execute();
        };
        $stats = ['updated' => 0, 'held' => [], 'missing' => [], 'already_applied' => 0];
        foreach ($data['beaches'] as $copy) {
            // Exact records only: do not guess identity or overwrite a merged destination.
            $beach = $fetch('SELECT * FROM beaches WHERE slug=:slug', [':slug' => $copy['slug']])[0] ?? null;
            if (!$beach) { $stats['missing'][] = $copy['slug']; continue; }
            if ($fetch('SELECT beach_id FROM practical_information_backups WHERE beach_id=:id', [':id' => $beach['id']])) {
                $stats['already_applied']++; continue;
            }
            $id = $beach['id']; $before = ['beaches' => $beach];
            foreach (['beach_tips', 'beach_features', 'beach_content_sections', 'beach_tags', 'beach_amenities'] as $table) {
                $before[$table] = $fetch("SELECT * FROM $table WHERE beach_id=:id", [':id' => $id]);
            }
            $run('INSERT INTO practical_information_backups VALUES (:id,:date,:json)', [
                ':id'=>$id, ':date'=>$data['checked_on'], ':json'=>json_encode($before, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
            $values = [
                'description'=>$copy['description'], 'description_es'=>$copy['description_es'],
                'parking_details'=>'Exact parking location, current fee and payment methods are not confirmed. Check with the site operator before travel.',
                'parking_details_es'=>'No se han confirmado la ubicación exacta del estacionamiento, la tarifa vigente ni los métodos de pago. Consulta al administrador antes de viajar.',
                'access_label'=>$copy['access_label'],
                'safety_info'=>$copy['safety_info'], 'safety_info_es'=>$copy['safety_info_es'],
                'best_time'=>'Confirm opening hours and current conditions before departure.',
                'best_time_es'=>'Confirma el horario y las condiciones actuales antes de salir.',
                'local_tips'=>$copy['practical'], 'local_tips_es'=>$copy['practical_es'],
                'notes'=>null, 'notes_es'=>null,
                'seo_title'=>null, 'seo_title_es'=>null,
                'seo_description'=>$copy['description'], 'seo_description_es'=>$copy['description_es'],
                'safe_for_children'=>0, 'swim_difficulty'=>3, 'has_lifeguard'=>null,
                'practical_reviewed_at'=>$data['checked_on'], 'updated_at'=>gmdate('Y-m-d H:i:s'),
            ];
            if ($copy['hold']) {
                $values['publish_status']='draft';
                $stats['held'][]=$copy['slug'];
            }
            $sets=[]; $params=[':id'=>$id];
            foreach ($values as $key=>$value) {
                if ($key !== 'practical_reviewed_at' && !in_array($key,$columns,true)) continue;
                $sets[]="$key=:$key"; $params[":$key"]=$value;
            }
            $run('UPDATE beaches SET '.implode(',',$sets).' WHERE id=:id',$params);
            // Preserve generated content in the snapshot; remove competing public claims.
            $run('DELETE FROM beach_tips WHERE beach_id=:id',[':id'=>$id]);
            $run('DELETE FROM beach_features WHERE beach_id=:id',[':id'=>$id]);
            $run("UPDATE beach_content_sections SET status='draft' WHERE beach_id=:id",[':id'=>$id]);
            $run("DELETE FROM beach_amenities WHERE beach_id=:id AND amenity IN ('free-parking','lifeguard','accessibility','wheelchair-accessible')",[':id'=>$id]);
            $run("DELETE FROM beach_tags WHERE beach_id=:id AND tag IN ('calm-waters','family-friendly','accessible')",[':id'=>$id]);
            if (in_array($copy['slug'], ['black-eagle-beach','muelle-de-azucar-beach'],true)) {
                $run("DELETE FROM beach_tags WHERE beach_id=:id AND tag IN ('fishing','swimming','snorkeling','diving','surfing')",[':id'=>$id]);
            }
            $html=[];
            foreach (['en','es'] as $lang) {
                $es=$lang==='es';
                $content='<p>'.htmlspecialchars($copy[$es?'practical_es':'practical'],ENT_QUOTES,'UTF-8').'</p>';
                $content.='<p>'.($es?'Fuentes consultadas el ':'Sources checked on ').$data['checked_on'].'. '.($es?'Revisión de fuentes en línea; no es una inspección presencial. Confirma los servicios y las tarifas actuales.':'Online source review, not an on-site inspection. Confirm current services and prices.').'</p><ul>';
                foreach ($copy['source_ids'] as $sid) {
                    $source=$data['sources'][$sid];
                    $content.='<li><a href="'.htmlspecialchars($source['url'],ENT_QUOTES,'UTF-8').'" rel="noopener">'.htmlspecialchars($source['title'],ENT_QUOTES,'UTF-8').'</a></li>';
                }
                $html[$lang]=$content.'</ul>';
            }
            $version=(int)($fetch("SELECT MAX(version) AS n FROM beach_content_sections WHERE beach_id=:id AND section_type='local_tips'",[':id'=>$id])[0]['n']??0)+1;
            $run("INSERT INTO beach_content_sections (id,beach_id,section_type,heading,heading_es,content,content_es,status,version,display_order,metadata)
                VALUES (:sid,:id,'local_tips',:heading,:heading_es,:en,:es,'published',:version,1,:meta)",[
                ':sid'=>'practical-20260914-'.$id,':id'=>$id,':heading'=>'Planning your visit',':heading_es'=>'Planifica tu visita',
                ':en'=>$html['en'],':es'=>$html['es'],':version'=>$version,':meta'=>json_encode(['source_ids'=>$copy['source_ids'],'checked_on'=>$data['checked_on'],'field_verified'=>false],JSON_THROW_ON_ERROR)]);
            $stats['updated']++;
        }
        $db->exec('COMMIT'); return $stats;
    } catch (Throwable $e) { $db->exec('ROLLBACK'); throw $e; }
}
