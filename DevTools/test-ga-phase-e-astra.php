<?php
// Retained loaded-state checks for Phase E Astra clauses that scenario play cannot reach (a direct action bypasses prereqs, so a fixture can only show the positive path):
//  - Stellarion Shift's graveyard ability needs a distant champion, the Class Bonus (RANGER) and the Element Bonus (an ASTRA champion).
//  - Cataleptic Constellation's wake-up lock ends once the Constellation has left the field.
//  - Astarte, Celestial Dawn's Fast Activation is a CLERIC Class Bonus (its hand-written table entry used to say GUARDIAN; the generic printed-keyword path already covered the Cleric case, so only a Guardian-class champion with the ASTRA element could have seen the difference).
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_phase_e_astra_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function asCheck($ok, $label) {
    global $checks;
    if (!$ok) { echo "MISS $label\n"; if (getenv('GA_CHECK_CONTINUE') !== '1') throw new RuntimeException($label); return; }
    ++$checks;
    echo "PASS $label\n";
}
function asLoad() {
    global $gameDir, $playerID;
    $playerID = 1;
    copy('Tests/Integration/GrandArchiveSim/slipstream-target-cost-ordinary/initial_gamestate.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/');
    $playerID = 1;
    foreach ([1, 2] as $seat) { $q = &GetDecisionQueue($seat); $q = []; }
}
try {
    $shift = 'ms2x2v4qe3'; $astarte = 'f0ht2tsn0y';
    $moonpiercer = 'v3vfjtwm7g';   // ASTRA, RANGER
    $arbiter = '7e22tk3ir1';       // ASTRA, CLERIC
    $fire = 'LMyKyVC2O9';          // FIRE, no class

    $shiftCase = function (string $champion, bool $distant) use ($shift) {
        asLoad();
        $champ = &GetField(1)[0]; $champ->CardID = $champion; $champ->Subcards = []; $champ->Counters = []; $champ->TurnEffects = $distant ? ['DISTANT'] : [];
        AddGraveyard(1, $shift);
        return CanActivateAbility(1, 'myGraveyard-' . (count(GetGraveyard(1)) - 1), 0);
    };
    asCheck($shiftCase($moonpiercer, true) === true, 'Stellarion Shift: a distant ASTRA Ranger champion can use the graveyard ability');
    asCheck($shiftCase($moonpiercer, false) === false, 'Stellarion Shift: a champion that is not distant cannot');
    asCheck($shiftCase($arbiter, true) === false, 'Stellarion Shift: an ASTRA Cleric champion has the Element Bonus but not the Ranger Class Bonus');
    asCheck($shiftCase($fire, true) === false, 'Stellarion Shift: a FIRE champion has neither bonus');

    $astarteCase = function (string $champion) use ($astarte) {
        asLoad();
        $champ = &GetField(1)[0]; $champ->CardID = $champion; $champ->Subcards = []; $champ->Counters = [];
        $hand = &GetHand(1); $hand = [];
        AddHand(1, $astarte);
        for ($i = 0; $i < 4; ++$i) AddHand(1, 'em6eEh9q8y'); // Dungeon Guides to pay (3) with
        $cards = GetPlayableFastCards(1);
        return in_array('myHand-0', $cards, true);
    };
    asCheck($astarteCase($arbiter) === true, 'Astarte: a CLERIC champion can fast-activate it');
    asCheck($astarteCase($fire) === false, 'Astarte: a champion without the Cleric class cannot');
    asCheck($astarteCase($moonpiercer) === false, 'Astarte: an ASTRA champion that is not a Cleric (a Ranger) cannot');

    // Cataleptic Constellation: the lock holds while it is on the caster's field and is dropped once it has left
    asLoad();
    $ally = AddField(1, CardID: 'L0RmNaDzhk', Owner: 1, Controller: 1);
    $ally->Counters = ['_catcon_controller' => 1];
    asCheck(CataleptConstellationHoldsLock($ally) === false, 'Cataleptic Constellation: with no Constellation on the field the lock does not hold');
    asCheck(!isset($ally->Counters['_catcon_controller']), 'Cataleptic Constellation: and the stale lock is dropped');
    AddField(1, CardID: 'lflzwiiewz', Owner: 1, Controller: 1);
    $ally->Counters = ['_catcon_controller' => 1];
    asCheck(CataleptConstellationHoldsLock($ally) === true, 'Cataleptic Constellation: while it is on its caster\'s field the lock holds');
    echo "OK $checks checks\n";
} finally {
    if (is_dir($gameDir)) { foreach (glob($gameDir . '/*') as $f) @unlink($f); @rmdir($gameDir); }
}
