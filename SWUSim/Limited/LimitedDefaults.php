<?php
// Limited room defaults and pack-count ranges — the part of the settings that needs no card data, so the room state (and
// the lobby poll that loads it on every request) never pulls in the card dictionary. LimitedSettings.php requires this.
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.1.
require_once __DIR__ . '/../../AppCore/SWU/BoosterSets.php';

function SWULimitedPackRange(string $mode): array {
    return $mode === 'sealed' ? [6, 16] : [3, 6];
}

function SWULimitedDefaultSettings(string $mode): array {
    $latest = SWULatestBoosterSet();
    $count  = SWULimitedPackRange($mode)[0];
    return ['mode' => $mode, 'bestOf' => 1, 'packCount' => $count, 'packMode' => 'single',
            'singleSet' => $latest, 'lineup' => array_fill(0, $count, $latest), 'chaosSets' => []];
}
