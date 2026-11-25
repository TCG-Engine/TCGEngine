<?php
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__, 2));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_targets_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function targetCheck($ok, $label) { global $checks; if(!$ok) throw new RuntimeException($label); ++$checks; echo "PASS $label\n"; }
function targetReset() {
    global $gameDir, $playerID;
    copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt', $gameDir.'/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/'); $playerID = 1;
    foreach([1,2] as $p) foreach(['Field','Hand','Memory','Deck','Graveyard','Banish','DecisionQueue','GlobalEffects','Mastery'] as $zone) { $fn = 'Get'.$zone; $list = &$fn($p); $list = []; }
    $stack = &GetEffectStack(); $stack = [];
    SetMacroTurnIndex('{}'); SetMacroGameIndex('{}');
    MZAddZone(1,'myField',CardNameToID('Rai, Spellcrafter'));
    MZAddZone(2,'myField',CardNameToID('Spirit of Fire'));
}
try {
    targetReset();
    $glass = MZAddZone(1, 'myField', CardNameToID('The Looking Glass'));
    $glassMZ = 'myField-1';
    targetCheck(FilterSpellshroudTargets([$glassMZ]) === [], 'The Looking Glass still rejects explicit spell targets');
    $cardActivatedAbilities['aIbBhTilEN:0'](1);
    GASpellshroudFilterNewDecisions(1, []);
    $queue = &GetDecisionQueue(1);
    targetCheck(count($queue) === 1 && $queue[0]->Param === 'ReturnToArchiveChoose', 'Non-targeting sacrifice survives spell opener filtering');
    $queue = [];
    $customDQHandlers['ReturnToArchiveChoose'](1, [], '');
    targetCheck($queue[0]->Type === 'MZMAYCHOOSE' && $queue[0]->Param === $glassMZ, 'Omnishroud regalia can be chosen for optional sacrifice');
    GetField(1)[0]->Damage = 5;
    MZAddZone(1, 'myDeck', CardNameToID('Dungeon Guide'));
    $queue = [];
    $customDQHandlers['ReturnToArchiveProcess'](1, [], 'PASS');
    targetCheck(!$glass->removed && GetChampionDamage(1) === 5 && count(GetHand(1)) === 0, 'Declining sacrifices nothing, recovers nothing, and draws nothing');
    $customDQHandlers['ReturnToArchiveProcess'](1, [], $glassMZ);
    targetCheck($glass->removed && GetChampionDamage(1) === 3 && count(GetHand(1)) === 1, 'Sacrificing The Looking Glass recovers two and draws one');

    foreach(['4zEOAaLdap', 'qaA3sXFRFY', 'N0ipz8UWwf'] as $spellID) {
        targetReset();
        $regalia = MZAddZone(1, 'myField', CardNameToID('Sword of Adversity'));
        AddTurnEffect('myField-1', 'OMNISHROUD');
        $cardActivatedAbilities[$spellID.':0'](1);
        GASpellshroudFilterNewDecisions(1, [], $spellID);
        $q = GetDecisionQueue(1);
        targetCheck(count($q) === 2 && $q[0]->Param === 'myField-1', CardName($spellID).' preserves its protected regalia choice and continuation');
    }
    foreach(['NORTH', 'SOUTH'] as $direction) {
        targetReset();
        $ally = MZAddZone(1, 'myField', CardNameToID('Dungeon Guide'));
        AddTurnEffect('myField-1', 'SPELLSHROUD');
        $mastery = MZAddZone(1, 'myMastery', 'qh5mpkyl60');
        $mastery->Direction = $direction;
        $cardActivatedAbilities['jgyx38zpl0:0'](1);
        GASpellshroudFilterNewDecisions(1, [], 'jgyx38zpl0');
        $q = GetDecisionQueue(1);
        targetCheck(count($q) === 2 && $q[0]->Param === 'myField-1', 'Bagua '.$direction.' can choose a spellshrouded ally');
        $customDQHandlers[$q[1]->Param](1, [], 'myField-1');
        targetCheck($direction === 'NORTH' ? GetCounterCount($ally, 'buff') === 1 : $ally->removed, 'Bagua '.$direction.' resolves on the protected ally');
    }
    targetReset();
    GetField(1)[0]->Subcards = [CardNameToID('Malevolent Vow')];
    AddTurnEffect('myField-0', 'OMNISHROUD');
    $cardActivatedAbilities['u1xhs5jwsl:0'](1);
    GASpellshroudFilterNewDecisions(1, [], 'u1xhs5jwsl');
    targetCheck(GetDecisionQueue(1)[0]->Param === 'myField-0', 'Exorcise Curses can choose a protected champion lineage');

    targetReset();
    $glass = MZAddZone(1, 'myField', CardNameToID('The Looking Glass'));
    $ally = MZAddZone(2, 'myField', CardNameToID('Dungeon Guide'));
    AddTurnEffect('theirField-1', 'SPELLSHROUD');
    $cardActivatedAbilities['x79cuuw5vo:0'](1);
    GASpellshroudFilterNewDecisions(1, [], 'x79cuuw5vo');
    targetCheck(GetDecisionQueue(1)[0]->Param === '0|2|myField-1&theirField-1', 'Frostnip includes protected objects on both sides and excludes champions');
    $customDQHandlers['FrostnipPirouetteWitherLoop'](1, [], 'myField-1&theirField-1');
    targetCheck(GetCounterCount($glass, 'wither') === 1 && GetCounterCount($ally, 'wither') === 1, 'Frostnip places wither on both protected objects');
    $queue = &GetDecisionQueue(1); $queue = [];
    DecisionQueueController::AddDecision(1, 'MZCHOOSE', 'myField-1&theirField-1&myField-0', 1);
    GASpellshroudFilterNewDecisions(1, [], CardNameToID('Fracturize'));
    targetCheck(GetDecisionQueue(1)[0]->Param === 'myField-0', 'Legacy explicit targeting still filters both protections');

    // Non-targeting board-wide buffs must also include protected allies.
    targetReset();
    $ally = MZAddZone(1, 'myField', CardNameToID('Dungeon Guide'));
    AddTurnEffect('myField-1', 'OMNISHROUD');
    $cardActivatedAbilities[CardNameToID('Glow Forth').':0'](1);
    targetCheck(GetCounterCount($ally, 'buff') === 1, 'Glow Forth buffs an omnishrouded ally without targeting');

    targetReset();
    $memorite = MZAddZone(1, 'myField', CardNameToID('Memorite Obelith'));
    AddTurnEffect('myField-1', 'OMNISHROUD');
    $weapon = MZAddZone(1, 'myField', CardNameToID('Sword of Adversity'));
    AddTurnEffect('myField-2', 'SPELLSHROUD');
    $cardActivatedAbilities['XmsEbk19Iu:0'](1);
    GASpellshroudFilterNewDecisions(1, [], 'XmsEbk19Iu');
    targetCheck(GetDecisionQueue(1)[0]->Param === 'myField-1', 'Facet Together can sacrifice a protected Memorite');
    $queue = &GetDecisionQueue(1); $queue = [];
    $customDQHandlers['FacetTogetherSacrifice'](1, [], 'myField-1');
    targetCheck($memorite->removed, 'Facet Together sacrificed the protected object');
    DecisionQueueController::CleanupRemovedCards();
    $queue = &GetDecisionQueue(1); $queue = [];
    $customDQHandlers['FacetTogetherSacrifice'](1, [], 'PASS');
    targetCheck(count(array_filter(GetDecisionQueue(1), fn($d) => in_array($d->Type, ['MZCHOOSE','MZMAYCHOOSE']))) === 0, 'Facet Together sacrifices the object but cannot target a spellshrouded weapon');

    foreach(['4a8hl5dben', 'pgt4lhko8w', 'yuo7dbge3b'] as $spellID) {
        targetReset();
        AddTurnEffect('myField-0', 'OMNISHROUD');
        AddTurnEffect('theirField-0', 'SPELLSHROUD');
        $cardActivatedAbilities[$spellID.':0'](1);
        GASpellshroudFilterNewDecisions(1, [], $spellID);
        targetCheck(GetDecisionQueue(1)[0]->Param === 'myField-0&theirField-0', CardName($spellID).' targets players regardless of champion protection');
    }
    echo "$checks non-targeting sacrifice checks passed.\n";
} finally {
    foreach(glob($gameDir.'/*') ?: [] as $file) if(is_file($file)) unlink($file);
    @rmdir($gameDir);
}
