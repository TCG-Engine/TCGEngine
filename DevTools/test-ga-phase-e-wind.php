<?php
// Retained loaded-state checks for Phase E (Wind) clauses that scenario play cannot reach.
// Diablerie: the opponent cannot play a regalia during the caster's turn through normal flow, so the theft path is driven through FieldAfterAdd.
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_phase_e_wind_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function weCheck($ok, $label) {
    global $checks;
    if (!$ok) { echo "MISS $label\n"; if (getenv('GA_CHECK_CONTINUE') !== '1') throw new RuntimeException($label); return; }
    ++$checks;
    echo "PASS $label\n";
}
function weLoad() {
    global $gameDir, $playerID;
    $playerID = 1;
    copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/');
    $playerID = 1;
    foreach ([1, 2] as $seat) { $q = &GetDecisionQueue($seat); $q = []; }
}
try {
    $relic = 'fjne9ri261'; // Art of War: Divine Relic regalia
    $plain = 'K15jWbHAMY';  // a regalia without divine relic
    foreach ([1, 2] as $caster) {
        $victim = 3 - $caster;
        // The Diablerie effect is stored on the caster; the victim's relic enters under the caster's control and consumes the effect.
        weLoad();
        AddGlobalEffects($caster, '0plqbtjuxz');
        $obj = AddField($victim, $relic, Owner: $victim, Controller: $victim);
        $stolen = null;
        foreach (GetField($caster) as $o) if ($o->CardID === $relic) $stolen = $o;
        $kept = null;
        foreach (GetField($victim) as $o) if ($o->CardID === $relic) $kept = $o;
        weCheck(GlobalEffectCount($caster, '0plqbtjuxz') === 0, "Diablerie cast by seat $caster: the effect is consumed by the opponent's relic");
        weCheck($obj !== null && intval($obj->Controller) === $caster, "Diablerie cast by seat $caster: the relic's Controller is the caster");

        // A regalia without divine relic is not taken, and the effect stays.
        weLoad();
        AddGlobalEffects($caster, '0plqbtjuxz');
        $obj2 = AddField($victim, $plain, Owner: $victim, Controller: $victim);
        weCheck($obj2 !== null && intval($obj2->Controller) === $victim, "Diablerie cast by seat $caster: a regalia without divine relic stays with its owner");
        weCheck(GlobalEffectCount($caster, '0plqbtjuxz') === 1, "Diablerie cast by seat $caster: the effect waits for a divine relic");

        // The caster's own relic is not stolen from themselves, and the effect stays.
        weLoad();
        AddGlobalEffects($caster, '0plqbtjuxz');
        $obj3 = AddField($caster, $relic, Owner: $caster, Controller: $caster);
        weCheck($obj3 !== null && intval($obj3->Controller) === $caster && GlobalEffectCount($caster, '0plqbtjuxz') === 1, "Diablerie cast by seat $caster: the caster's own relic does not consume the effect");
    }
    echo "OK $checks checks\n";
} finally {
    if (is_dir($gameDir)) { foreach (glob($gameDir . '/*') as $f) @unlink($f); @rmdir($gameDir); }
}
