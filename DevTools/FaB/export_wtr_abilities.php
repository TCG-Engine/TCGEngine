<?php
// Snapshot reviewed WTR CardEditor authoring through the configured repository.
require_once __DIR__ . '/../../CardEditor/Database/CardAbilityRepository.php';
$cache=json_decode(file_get_contents(__DIR__.'/../../FaBSim/GeneratedCode/cardArrayCache.json'),true,512,JSON_THROW_ON_ERROR);
$ids=[];
foreach($cache['cardArray']??[]as$card)foreach($card['printings']??[]as$printing)if(($printing['set_id']??'')==='WTR'){$ids[]=$card['id'];break;}
$ids=array_values(array_unique($ids));sort($ids);
if(count($ids)!==226)throw new RuntimeException('Unexpected WTR catalog size; review the source set before exporting.');
$repo=OpenCardAbilityRepository('FaBSim');$entries=[];
try{
    foreach($ids as$id){
        $abilities=[];
        foreach($repo->loadCardAbilities('FaBSim',$id)as$row){
            $macro=$row['macroName']??$row['macro_name']??'';if($macro==='')continue;
            $zones=$row['listenerZones']??$row['listener_zones']??[];
            if(is_string($zones))$zones=json_decode($zones,true)?:array_values(array_filter(array_map('trim',explode(',',$zones))));
            $abilities[]=['macroName'=>$macro,'abilityCode'=>$row['abilityCode']??$row['ability_code']??'',
                'prereqCode'=>$row['prereqCode']??$row['prereq_code']??null,
                'abilityName'=>$row['abilityName']??$row['ability_name']??null,
                'abilityType'=>$row['abilityType']??$row['ability_type']??'macro',
                'listenerZones'=>$zones,'isImplemented'=>(bool)($row['isImplemented']??$row['is_implemented']??false)];
        }
        $entries[]=['cardId'=>$id,'abilities'=>$abilities];
    }
}finally{$repo->close();}
file_put_contents(__DIR__.'/wtr_abilities.json',json_encode($entries,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);
echo 'Exported '.count($entries).' WTR identities.'.PHP_EOL;
