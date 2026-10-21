<?php
// Adds explicit declaration/resolution programs; preserves existing abilities and
// the old protocol used by saved games that predate target declarations.
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../CardEditor/Database/CardAbilityRepository.php';
try {
    $input = json_decode(file_get_contents($argv[1] ?? ''),true,512,JSON_THROW_ON_ERROR);
    $root = $input['root']; $repo = OpenCardAbilityRepository($root); $pending = [];
    $snapshot = method_exists($repo, 'loadCardsWithRevisions') ? $repo->loadCardsWithRevisions($root) : [];
    foreach($input['cards'] as $plan) {
        $id = $plan['card_id'];
        $filter = array_values(array_filter($argv,fn($v)=>str_starts_with($v,'--card=')));
        if($filter && $id !== substr($filter[0],7)) continue;
        $loaded = $snapshot[$id] ?? $repo->loadCardWithRevision($root,$id);
        $source = array_values(array_filter($loaded['abilities'],fn($r)=>$r['macro_name']===$plan['macro_name']));
        if(count($source)!==1 || $source[0]['ability_code']!==$plan['before']) throw new RuntimeException("$id source changed; reconcile before applying");
        $desired = ['ActivationTargets'=>$plan['declaration'], ($plan['macro_name']==='PlayCard' ? 'DeclaredPlayResolution' : 'DeclaredCardResolution')=>$plan['after']];
        foreach($plan['additionalMacros'] ?? [] as $macro=>$code) $desired[$macro] = $code;
        $abilities = []; $changed = false;
        foreach($loaded['abilities'] as $r) {
            // Empty implementation markers are represented by cardImplemented once real programs exist.
            if(trim($r['macro_name']) === '' && trim($r['ability_code']) === '') continue;
            $row = ['macroName'=>$r['macro_name'],'abilityCode'=>$r['ability_code'],'prereqCode'=>$r['prereq_code'], 'abilityName'=>$r['ability_name'],'abilityType'=>$r['ability_type'],'listenerZones'=>$r['listener_zones'],'isImplemented'=>(bool)$r['is_implemented']];
            if(isset($desired[$r['macro_name']])) {
                if($r['ability_code']!==$desired[$r['macro_name']]) throw new RuntimeException("$id declaration already differs; reconcile before applying");
                unset($desired[$r['macro_name']]);
            }
            $abilities[]=$row;
        }
        foreach($desired as $macro=>$code) { $abilities[]=['macroName'=>$macro,'abilityCode'=>$code,'isImplemented'=>true]; $changed=true; }
        if($changed) $pending[$id]=['abilities'=>$abilities,'revision'=>$loaded['revision']];
        echo "$id: ".($changed?'ready':'already applied')."\n";
    }
    if(!in_array('--apply',$argv,true)) { echo 'Preview: '.count($pending)." cards; no writes.\n"; exit; }
    foreach($pending as $id=>$change) {
        $repo->replaceCardAbilities($root,$id,$change['abilities'],true,$change['revision']);
        echo "$id: saved\n";
    }
} catch(Throwable $e) { fwrite(STDERR,$e->getMessage()."\n"); exit(1); }
