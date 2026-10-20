<?php
/** Revision-checked authoring of new cards. Preview by default; never replaces existing authored code. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../CardEditor/Database/CardAbilityRepository.php';
try {
    $path = $argv[1] ?? '';
    if (!is_file($path)) throw new RuntimeException('Usage: php DevTools/AuthorCardCode.php <definitions.json> [--apply]');
    $input = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    foreach($argv as $arg) {
        if(!str_starts_with($arg, '--card=')) continue;
        $id = substr($arg, 7);
        if(!isset($input['cards'][$id])) throw new RuntimeException("Unknown card: $id");
        $input['cards'] = [$id => $input['cards'][$id]];
    }
    $root = $input['root'];
    $repo = OpenCardAbilityRepository($root);
    $pending = [];
    foreach ($input['cards'] as $id => $abilities) {
        if (!$abilities) throw new RuntimeException("$id: empty definition");
        foreach ($abilities as $ability) {
            if (trim($ability['macroName'] ?? '') === '' || trim($ability['abilityCode'] ?? '') === '') throw new RuntimeException("$id: empty ability");
        }
        $loaded = $repo->loadCardWithRevision($root, $id);
        $existing = array_values(array_filter($loaded['abilities'], fn($r) => trim($r['macro_name']) !== '' || trim($r['ability_code']) !== ''));
        $normalize = fn($r) => [
            'macroName' => $r['macro_name'], 'abilityCode' => $r['ability_code'],
            'prereqCode' => $r['prereq_code'] ?? null, 'abilityName' => $r['ability_name'] ?? null,
            'abilityType' => $r['ability_type'] ?? 'macro', 'listenerZones' => $r['listener_zones'] ?? null,
            'isImplemented' => (bool)$r['is_implemented'],
        ];
        $desired = array_map(fn($r) => array_replace(['prereqCode'=>null,'abilityName'=>null,'abilityType'=>'macro','listenerZones'=>null,'isImplemented'=>true], $r), $abilities);
        if (array_map($normalize, $existing) == $desired) { echo "$id: already authored\n"; continue; }
        if ($existing) throw new RuntimeException("$id: existing authored code differs; reconcile before writing");
        $pending[$id] = ['abilities' => $desired, 'revision' => $loaded['revision']];
        echo "$id: ready\n";
    }
    if (!in_array('--apply', $argv, true)) { echo 'Preview: '.count($pending)." cards; no writes.\n"; exit; }
    foreach ($pending as $id => $card) {
        $repo->replaceCardAbilities($root, $id, $card['abilities'], true, $card['revision']);
        echo "$id: saved\n";
    }
} catch (Throwable $error) { fwrite(STDERR, $error->getMessage()."\n"); exit(1); }
