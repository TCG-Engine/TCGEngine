<?php
// Reproducible CardEditor migration; uses the same local/remote repository as MCP.
// Set MYSQL_DATABASE_NAME to the database that owns this installation's card code.
require_once __DIR__ . '/../../CardEditor/Database/CardAbilityRepository.php';
$repo = OpenCardAbilityRepository('FaBSim');
$snapshotPath = $argv[1] ?? (__DIR__ . '/wtr_abilities.json');
$entries = json_decode(file_get_contents($snapshotPath), true, 512, JSON_THROW_ON_ERROR);
if(basename($snapshotPath)==='wtr_abilities.json'&&count(array_unique(array_column($entries,'cardId')))!==226)throw new RuntimeException('Expected the complete 226-identity WTR snapshot.');
foreach ($entries as $entry) {
    $id = $entry['cardId'];
    $old = $repo->loadCardAbilities('FaBSim', $id);
    $revision = $repo->revisionForCard('FaBSim', $id);
    $merged = $old;
    foreach ($entry['abilities'] as $ability) {
        $matches = array_filter($old, fn($a) => ($a['macro_name'] ?? $a['macroName'] ?? '') === $ability['macroName']);
        foreach ($matches as $existing) {
            $code = $existing['ability_code'] ?? $existing['abilityCode'] ?? '';
            if (trim($code) !== '' && trim($code) !== trim($ability['abilityCode'])) {
                throw new RuntimeException('Existing authored '.$ability['macroName'].' code for '.$id.' requires a manual merge.');
            }
        }
        $merged = array_values(array_filter($merged, fn($a) => ($a['macro_name'] ?? $a['macroName'] ?? '') !== $ability['macroName']));
        $merged[] = $ability;
    }
    // Normalize repository rows to the public CardEditor save shape.
    $payload = [];
    foreach ($merged as $row) {
        $macro = $row['macroName'] ?? $row['macro_name'] ?? '';
        if ($macro === '') continue;
        $zones=$row['listenerZones']??$row['listener_zones']??[];
        if(is_string($zones))$zones=json_decode($zones,true)?:array_values(array_filter(array_map('trim',explode(',',$zones))));
        $payload[] = ['macroName'=>$macro, 'abilityCode'=>$row['abilityCode'] ?? $row['ability_code'] ?? '',
            'prereqCode'=>$row['prereqCode'] ?? $row['prereq_code'] ?? null,
            'abilityName'=>$row['abilityName'] ?? $row['ability_name'] ?? null,
            'isImplemented'=>$row['isImplemented'] ?? $row['is_implemented'] ?? false,
            'abilityType'=>$row['abilityType'] ?? $row['ability_type'] ?? 'macro',
            'listenerZones'=>$zones];
    }
    $repo->replaceCardAbilities('FaBSim', $id, $payload, true, $revision);
    echo 'Saved '.$id.PHP_EOL;
}
$repo->close();
