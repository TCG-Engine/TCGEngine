<?php
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_remaining_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function remainingCheck($ok,$label) { global $checks; if(!$ok) throw new RuntimeException($label); ++$checks; echo "PASS $label\n"; }
function remainingReset($champion='Spirit of Fire') {
    global $gameDir,$playerID;
    copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt',$gameDir.'/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/'); $playerID=1;
    foreach([1,2] as $p) foreach(['Field','Hand','Memory','Deck','Graveyard','Banish','DecisionQueue','GlobalEffects','Mastery'] as $zone) { $fn='Get'.$zone; $list=&$fn($p); $list=[]; }
    SetMacroTurnIndex('{}'); SetMacroGameIndex('{}');
    foreach([1,2] as $p) MZAddZone($p,'myField',CardNameToID($champion));
}
try {
    remainingReset();
    $monk=MZAddZone(1,'myField','NBf2d3iqXp');
    $regalia=MZAddZone(1,'myField',CardNameToID('Bauble of Abundance'));
    $enemy=MZAddZone(2,'myField',CardNameToID('Bauble of Abundance'));
    remainingCheck(count(GADatabaseGrantedAbilities($regalia))===1, 'Monk grants a Spell ability to own regalia');
    remainingCheck(GADatabaseGrantedAbilities($enemy)===[], 'Monk does not grant to opposing regalia');
    $regalia->Status=1;
    remainingCheck(GADatabaseGrantedAbilities($regalia)===[], 'Rested regalia cannot pay the granted REST cost');
    $regalia->Status=2;
    AddTurnEffect($monk->GetMzID(),'NO_ABILITIES');
    remainingCheck(GADatabaseGrantedAbilities($regalia)===[], 'Suppressing Monk removes its grant');
    $monk->TurnEffects=[];
    $source=GAAsSpellSource($regalia);
    $info=ResolveDamageSourceCardInfo($source,1);
    remainingCheck($info['obj']===$regalia && $info['asSpell'] && $info['cardID']===$regalia->CardID, 'Granted Spell retains regalia source identity');
    $regalia->removed=true;
    $info=ResolveDamageSourceCardInfo($source,1);
    remainingCheck($info['obj']===null && $info['asSpell'] && $info['cardID']===$regalia->CardID, 'Source identity survives regalia leaving play');
    $target=GetField(2)[0];
    AddTurnEffect($target->GetMzID(),'WEAKEN_RES_2');
    DealDamage(1,$source,$target->GetMzID(),1);
    remainingCheck($target->Damage===3, 'Granted Spell consumes Weaken Resistance and deals three damage');
    remainingReset();
    $a=MZAddZone(1,'myBanish',CardNameToID('Dungeon Guide'));
    $b=MZAddZone(1,'myBanish',CardNameToID('Dungeon Guide'));
    $c=MZAddZone(2,'myBanish',CardNameToID('Dungeon Guide'));
    $r=MZAddZone(1,'myBanish',CardNameToID('Bauble of Abundance'));
    $champ=MZAddZone(1,'myBanish',CardNameToID('Spirit of Fire'));
    AddCounters(1,'myBanish-0','omen',2);
    remainingCheck(count(GAOrbTargets())===3,'Orb excludes champions and regalia');
    remainingCheck(!GAOrbSeal(1,'myBanish-0&theirBanish-0') && !GABanishFaceDown($a),'Orb rejects different banishments atomically');
    remainingCheck(GAOrbSeal(1,'myBanish-0&myBanish-1'),'Orb seals two cards in one banishment');
    remainingCheck(GABanishFaceDown($a) && GetCounterCount($a,'seal')===1,'Seal counter and face-down state are recorded');
    remainingCheck(EffectiveCardType($a)===null && EffectiveCardClasses($a)===null && HasNoAbilities($a),'Face-down banished cards have no visible characteristics or abilities');
    remainingCheck(GetOmenCount(1)===0,'Sealed cards do not function as omens');
    remainingCheck(!ActivateBanishedCard(1,'myBanish-0'),'Sealed cards cannot be activated');
    remainingCheck(GAOrbTargets()===['theirBanish-0'],'Already face-down cards cannot be sealed again');
    if(!function_exists('ClientRenderedCard')) require_once 'Core/UILibraries.php';
    $render=file_get_contents('GrandArchiveSim/GetNextTurn.php');
    preg_match('/\$arr = &GetBanish\(1\);(.*?)echo\("<~>"\);/s', $render, $fragment);
    remainingCheck(!empty($fragment), 'Generated banishment renderer is available');
    ob_start(); eval('$arr = &GetBanish(1);' . $fragment[1]); $payload=ob_get_clean();
    remainingCheck(!str_contains($payload,$a->CardID) && !str_contains($payload,'_sealFaceDown') && substr_count($payload,'CardBack')===2,'Sealed card identities and private JSON never reach the browser payload');

    AddCounters(2,'theirBanish-0','seal',3);
    $c->Counters['_sealFaceDown']=1;
    $orb=MZAddZone(1,'myField','mekutzp19y');
    OnLeaveField(1,$orb->GetMzID());
    remainingCheck(!GABanishFaceDown($a) && !GABanishFaceDown($c) && GetCounterCount($c,'seal')===0,'Orb leaving removes all seals in both banishments');
    remainingCheck(GetOmenCount(1)===1 && GetCounterCount($a,'omen')===2,'Unsealing restores omen abilities without erasing other counters');
    remainingReset('Merlin, Kingslayer');
    GetField(2)[0]->CardID=CardNameToID('Diana, Moonpiercer');
    $before=EffectiveCardClasses(GetField(1)[0]);
    GASoulGainClasses(1);
    remainingCheck(PropertyContains(EffectiveCardClasses(GetField(1)[0]), 'RANGER') && !array_diff(explode(',', $before), explode(',', EffectiveCardClasses(GetField(1)[0]))), 'Soul adds opposing classes without removing existing classes');
    DecisionQueueController::StoreVariable('soulMaterialLook','1:2');
    remainingCheck(GASoulCanLookMaterial(1,2) && !GASoulCanLookMaterial(2,1) && !GASoulCanLookMaterial(0,2),'Soul material visibility is restricted to the designated viewer and deck');
    DecisionQueueController::ClearVariable('soulMaterialLook');
    remainingCheck(!GASoulCanLookMaterial(1,2),'Soul closes material visibility after the choice');
    $material=&GetMaterial(2); $material=[];
    MZAddZone(2,'myMaterial',CardNameToID('Bauble of Abundance'));
    DoMaterialize(1,'theirMaterial-0');
    $stolen=GetField(1)[1];
    remainingCheck($stolen->Owner===2 && $stolen->Controller===1,'Opponent materialized regalia retains its owner and gains caster control');
    remainingReset();
    $stack=&GetEffectStack(); $stack=[];
    $own=AddEffectStack(CardID:CardNameToID('Dungeon Guide'),Controller:1);
    $enemy=AddEffectStack(CardID:CardNameToID('Dungeon Guide'),Controller:2);
    $trigger=AddEffectStack(CardID:'NBf2d3iqXp',Controller:2,TriggerType:'ENTER');
    $protected=AddEffectStack(CardID:CardNameToID('Dungeon Guide'),Controller:2,TurnEffects:['CANT_BE_NEGATED']);
    GARegulusNegateOpposing(1);
    $tokens=explode(',',DecisionQueueController::GetVariable('NegateByCardTargets'));
    remainingCheck(count($tokens)===1 && GARegulusStackRef($tokens[0])==='EffectStack-1','Regulus targets opposing card activations, excluding abilities and unnegatable cards');
    array_shift($stack);
    remainingCheck(GARegulusStackRef($tokens[0])==='EffectStack-0','Regulus tracks its target across stack compaction');
    remainingReset(); $stack=&GetEffectStack(); $stack=[];
    AddEffectStack(CardID:CardNameToID('Dungeon Guide'),Controller:2);
    AddEffectStack(CardID:CardNameToID('Dungeon Guide'),Controller:2);
    GARegulusNegateOpposing(1);
    $customDQHandlers['NegateByCardContinue'](1,['0'],'-');
    $customDQHandlers['NegateByCardContinue'](1,['1'],'-');
    remainingCheck(count(GetEffectStack())===0 && count(GetGraveyard(2))===2,'Regulus negates both unpaid opposing activations despite stack compaction');

    remainingReset();
    AddGlobalEffects(1,'REGULUS_NEXT_ATTACK');
    $action=MZAddZone(1,'myIntent',CardNameToID('Dungeon Guide'));
    GARegulusMarkActivation(1,$action);
    remainingCheck(GlobalEffectCount(1,'REGULUS_NEXT_ATTACK')===1,'Non-attacks do not consume Regulus permission');
    $command=MZAddZone(1,'myIntent','zuAmYGyCcL');
    GARegulusMarkActivation(1,$command);
    remainingCheck(GlobalEffectCount(1,'REGULUS_NEXT_ATTACK')===1,'Command attacks do not consume Regulus permission');

    $attack=MZAddZone(1,'myIntent','y7o97Xbidv');
    GARegulusMarkActivation(1,$attack);
    remainingCheck(GlobalEffectCount(1,'REGULUS_NEXT_ATTACK')===0 && GARegulusIntentNoRest(1),'Regulus applies to the next non-Command attack');
    remainingCheck(GARegulusIntentNoRest(1,true) && !GARegulusIntentNoRest(1),'Regulus removes the rest cost for exactly one declaration');
    remainingReset(); SetTurnNumber(3);
    $attack=MZAddZone(1,'myIntent','y7o97Xbidv'); $attack->TurnEffects[]='REGULUS_NO_REST';
    remainingCheck(DeclareChampionAttack(1) && GetField(1)[0]->Status===2,'Regulus attack declaration leaves an awake champion awake');
    remainingReset(); SetTurnNumber(3); GetField(1)[0]->Status=1;
    $attack=MZAddZone(1,'myIntent','y7o97Xbidv'); $attack->TurnEffects[]='REGULUS_NO_REST';
    remainingCheck(DeclareChampionAttack(1) && GetField(1)[0]->Status===1,'Removing the rest cost permits a rested champion to attack');

    remainingReset('Tristan, Ascendant Shadow');
    $champ=GetField(1)[0];
    ApplyPersistentOverride($champ->GetMzID(), ['type'=>EffectiveCardType($champ) . ',ASCENDANT']);
    remainingCheck(!GATristanAscendantCanActivate($champ),'Tristan requires 2025 available reserve');
    $hand=&GetHand(1);
    for($i=0;$i<2025;++$i) MZAddZone(1,'myHand',CardNameToID('Dungeon Guide'));
    remainingCheck(GATristanAscendantCanActivate($champ),'Tristan accepts the full printed 2025 cost');
    GATristanAscendantPay(1,$champ);
    $customDQHandlers['TristanAscendantPaid'](1, [strval($champ->UniqueID)], 'myHand-0');
    remainingCheck(count(GetMemory(1))===0,'Incomplete Tristan reserve selection cannot partially pay');
    GATristanAscendantPay(1,$champ);
    $selection=implode('&',array_map(fn($i)=>'myHand-'.$i,range(0,2024)));
    $customDQHandlers['TristanAscendantPaid'](1,[strval($champ->UniqueID)],$selection);
    DecisionQueueController::CleanupRemovedCards();
    remainingCheck(count(ZoneSearch('myMemory'))===2025 && count(ZoneSearch('myHand'))===0,'Tristan pays exactly 2025 reserve before resolving');

    remainingReset('Zander, Blinding Steel');
    $shape=MZAddZone(1,'myField','RBco1DfZ1B');
    $champ=GetField(1)[0]; $champ->Damage=15;
    MZAddZone(1,'myMemory','uwnHTLG3fL');
    MZAddZone(1,'myMemory','uwnHTLG3fL');
    foreach([1,2] as $seat) { $queue=&GetDecisionQueue($seat); $queue=[]; }
    $stack=&GetEffectStack(); $stack=[];
    $GLOBALS['__pendingTriggerBatch']=['depth'=>0,'entries'=>[]];
    GALightweaverResetRevealGroup();
    BeginTriggeredAbilityBatch();
    DoRevealCard(1,'myMemory-0'); DoRevealCard(1,'myMemory-1');
    $entries=$GLOBALS['__pendingTriggerBatch']['entries'];
    remainingCheck(count($entries)===4,'Two revealed Luxem Sights each have both recover abilities');
    $GLOBALS['__pendingTriggerBatch']=['depth'=>0,'entries'=>[]];
    foreach($entries as $entry) {
        foreach($entry['context'] as $key=>$value) DecisionQueueController::StoreVariable($key,$value);
        FireRevealTriggeredAbility(1,$entry['cardID']);
    }
    remainingCheck($champ->Damage===3,'All four copied and original reveal triggers recover three');
    GALightweaverResetRevealGroup();
    AddTurnEffect($shape->GetMzID(),'NO_ABILITIES');
    BeginTriggeredAbilityBatch(); DoRevealCard(1,'myMemory-0'); DoRevealCard(1,'myMemory-1');
    remainingCheck(count($GLOBALS['__pendingTriggerBatch']['entries'])===2,'Suppressed Shaping leaves only original reveal abilities');
    $GLOBALS['__pendingTriggerBatch']=['depth'=>0,'entries'=>[]];
    echo "$checks checks passed\n";
} finally { foreach(glob($gameDir."/*") ?: [] as $file) if(is_file($file)) unlink($file); @rmdir($gameDir); }
