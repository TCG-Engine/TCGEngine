<?php
// The Unique rule overrides immortality without changing ordinary sacrifice.
chdir(dirname(__DIR__, 2));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_unique_immortality_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function ttCheck($ok, $label) {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    ++$checks;
}
try {
    foreach ([1, 2] as $seat) {
        $playerID = 1;
        copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt', $gameDir . '/Gamestate.txt');
        ParseGamestate('./GrandArchiveSim/');
        foreach ([1, 2] as $p) { $q = &GetDecisionQueue($p); $q = []; }
        $playerID = $seat;
        AddField($seat, CardID: '4qc47amgpp', Status: 2, Owner: $seat, Controller: $seat);
        $first = AddField($seat, CardID: 'pB4ngDOM6F', Status: 2, Owner: $seat, Controller: $seat);
        $second = AddField($seat, CardID: 'pB4ngDOM6F', Status: 2, Owner: $seat, Controller: $seat);
        $firstMZ = 'myField-' . $first->mzIndex;
        $secondMZ = 'myField-' . $second->mzIndex;
        ttCheck(HasImmortality($first) && HasImmortality($second), 'Verita protects both Mistys');
        DoSacrificeFighter($seat, $firstMZ);
        ttCheck(!$first->removed, 'Ordinary sacrifice respects immortality');
        DoAllyDestroyed($seat, $firstMZ);
        ttCheck(!$first->removed, 'Ordinary destruction respects immortality');
        ttCheck(QueueUniqueRuleResolution($seat, $secondMZ), 'Duplicate queues a Unique choice');
        $q = &GetDecisionQueue($seat); $q = [];
        $customDQHandlers['ResolveUniqueRule']($seat, ['pB4ngDOM6F'], $secondMZ);
        $survivors = array_filter(GetField($seat), fn($o) => !$o->removed && $o->CardID === 'pB4ngDOM6F');
        ttCheck(count($survivors) === 1, 'Unique rule removes the selected immortal duplicate');
        ttCheck(count(array_filter(GetGraveyard($seat), fn($o) => !$o->removed && $o->CardID === 'pB4ngDOM6F')) === 1, 'Selected Misty reaches graveyard');
        ttCheck(HasImmortality(reset($survivors)), 'Surviving Misty retains immortality');
        foreach (GetDecisionQueue($seat) as $d) ttCheck(!str_starts_with($d->Param ?? '', 'ResolveUniqueRule|'), 'No repeat Unique prompt');
    }
    echo "OK $checks checks\n";
} finally {
    @unlink($gameDir . '/Gamestate.txt');
    @rmdir($gameDir);
}
