<?php
declare(strict_types=1);
$repo=__DIR__;while(!is_file($repo.'/Core/EngineActionRunner.php')&&dirname($repo)!==$repo)$repo=dirname($repo);
chdir($repo); require_once $repo.'/Core/EngineActionRunner.php'; EngineLoadRootRuntime('FaBSim');
$checks=0;
function requireSame($actual,$expected,string $label):void {
    ++$GLOBALS['checks'];
    if($actual!==$expected)throw new RuntimeException($label.': actual='.json_encode($actual).' expected='.json_encode($expected));
}
InitializeGamestate(); SetSeatOrder('12'); SetLiveSeats('12'); SetTurnPlayer(1); SetTurnNumber(2); SetCurrentPhase('MAIN'); SetPriorityPlayer(1); FaBSetState(FaBStateDefaults());
AddHero(1,CardID:'oscilio_scion_of_the_third_age',Owner:1,Controller:1); AddHero(2,CardID:'zyggy',Owner:2,Controller:2);
FaBOMNSetup(); requireSame(FaBGetState()['omnMacro']??false,false,'No macro means setup does not enable the effect');
requireSame(count(GetArena(1))+count(GetArena(2)),0,'No starting Flow without a macro');
$macro=AddArena(1,CardID:'omens_of_arcana',Owner:0,Controller:0);
requireSame(intval($macro->UniqueID),3,'Shared macro physical ID');
FaBOMNSetup(); requireSame(FaBGetState()['omnMacro'],true,'Native setup recognizes the actual shared macro');
$flowIDs=[];
foreach([1,2]as$p){
    $arena=array_values(array_filter(GetArena($p),fn($o)=>is_object($o)&&empty($o->removed)&&$o->CardID==='lightning_flow'));
    requireSame(count($arena),1,'Exactly one native starting Flow for seat'.$p);
    $f=$arena[0];$flowIDs[$p]=intval($f->UniqueID);
    requireSame(intval($f->Owner),$p,'Flow owned by recipient'.$p);requireSame(intval($f->Controller),$p,'Flow controlled by recipient'.$p);
    requireSame(FaBHasKeyword($f,'Spellvoid 1'),true,'Macro grants Spellvoid1 to seat'.$p.' Flow');
}
requireSame($flowIDs,[1=>4,2=>5],'Creation order');
$uid=intval(GetUniqueIDCounter());FaBOMNSetup();requireSame(intval(GetUniqueIDCounter()),$uid,'Native setup is idempotent');
requireSame(intval($macro->Owner),0,'Macro has no owner');requireSame(intval($macro->Controller),0,'Macro has no controller');
echo "Omens of Arcana native setup component: PASS ($checks checks). No gameplay, files or authentication.\n";
