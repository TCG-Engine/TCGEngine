<?php
// Load at file scope: generated card dictionaries and macro registries are globals.
foreach (['ZoneClasses.php', 'ZoneAccessors.php', 'GeneratedCode/GeneratedCardDictionaries.php',
    'GeneratedCode/GeneratedMacroCode.php', 'TurnController.php'] as $required) {
    if (!is_file(__DIR__ . '/' . $required)) throw new RuntimeException('Run php DevTools/PokeSim/setup.php first (missing ' . $required . ')');
}
require_once __DIR__ . '/../Core/DecisionQueueController.php';
require_once __DIR__ . '/../Core/CoreZoneModifiers.php';
require_once __DIR__ . '/../Core/NetworkingLibraries.php';
require_once __DIR__ . '/ZoneClasses.php';
require_once __DIR__ . '/ZoneAccessors.php';
require_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/GamestateParser.php';
require_once __DIR__ . '/Custom/DeckImport.php';
require_once __DIR__ . '/Decks/Registry.php';

function PokeStateExport(): array {
    $state = ['currentPlayer' => $GLOBALS['currentPlayer'] ?? 1, 'updateNumber' => $GLOBALS['updateNumber'] ?? 0];
    foreach ($GLOBALS as $key => $value) {
        if (!preg_match('/^(p[12][A-Z]|g[A-Z])/', $key) || $key === 'gRandomCounter') continue;
        if (is_array($value)) $state[$key] = array_values(array_map(fn($obj) => ['line'=>$obj->Serialize(),'removed'=>(bool)$obj->Removed()], $value));
        elseif (is_scalar($value)) $state[$key] = $value;
    }
    return $state;
}

function PokeStateImport(array $state): void {
    InitializeGamestate();
    foreach ($state as $key => $value) {
        if (preg_match('/^p([12])([A-Z]\w+)$/D', $key, $m) && is_array($value) && class_exists($m[2])) {
            $GLOBALS[$key] = [];
            foreach ($value as $i => $record) {
                $obj = new $m[2]($record['line'], $m[2], (int)$m[1], $i);
                $obj->removed = (bool)$record['removed']; $GLOBALS[$key][] = $obj;
            }
        } elseif (preg_match('/^p[12][A-Z]\w+$/D', $key) && array_key_exists($key, $GLOBALS) && is_scalar($value)) {
            $GLOBALS[$key] = $value;
        } elseif ($key === 'gStadium' && is_array($value)) {
            $GLOBALS[$key] = [];
            foreach ($value as $i => $record) { $obj = new Stadium($record['line'], 'Stadium', 0, $i); $obj->removed = (bool)$record['removed']; $GLOBALS[$key][] = $obj; }
        } elseif (preg_match('/^g[A-Z]\w+$/D', $key) && array_key_exists($key, $GLOBALS) && is_scalar($value)) $GLOBALS[$key] = $value;
    }
    $GLOBALS['currentPlayer'] = (int)($state['currentPlayer'] ?? 1);
    $GLOBALS['updateNumber'] = (int)($state['updateNumber'] ?? 0);
    $GLOBALS['playerID'] = GetTurnPlayer();
    // Pending chooser IDs and serialized await frames refer to current zone indices.
}
