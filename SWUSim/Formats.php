<?php
// Single source of truth for SWU formats + queue types (config-as-code).
//
// To DISABLE a temporary format (e.g. Preview after its window closes): set
// 'enabled' => false (preferred — keeps in-flight matches resolvable) or comment
// out the whole block. SWUListFormats() hides disabled formats from selectors,
// but SWUGetFormat()/SWUCheckFormat() still resolve them for anything mid-flight.

function SWUFormatDefinitions() {
    return [
        'premier' => [
            'displayName'       => 'Premier',
            'legalSets'         => ['JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'ASH'], // curated rotation
            'banned'            => [],
            'copyExceptions'    => ['JTL_256' => 15],            // Vulture Droid
            'deckSizeModifiers' => ['JTL_024' => +10, 'JTL_025' => -5],
            'enabled'           => true,
        ],
        'eternal' => [
            'displayName'    => 'Eternal',
            'legalSets'      => '*',                              // every printed set
            'banned'         => [],
            'copyExceptions' => ['JTL_256' => 15],
            'enabled'        => true,
        ],
        'open' => [
            'displayName' => 'Open',
            'legalSets'   => '*',
            'banned'      => [],                                  // no bans, ever
            'enabled'     => true,
        ],

        // ── PREVIEW (temporary) ──────────────────────────────────────────────
        // Premier pool + the upcoming set's previews. Flip 'enabled' => true and
        // add the new set code to 'legalSets' when a preview window opens; set it
        // back to false (or comment out) when the window closes.
        'preview' => [
            'displayName'       => 'Preview',
            'legalSets'         => ['JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'ASH'], // + '<NEXT_SET>' when live
            'banned'            => [],
            'copyExceptions'    => ['JTL_256' => 15],
            'deckSizeModifiers' => ['JTL_024' => +10, 'JTL_025' => -5],
            'enabled'           => false,
        ],
    ];
}

function SWUGetFormat($formatId) {
    $defs = SWUFormatDefinitions();
    if (!isset($defs[$formatId])) return null;
    $f = $defs[$formatId];
    return [
        'id'                => $formatId,
        'displayName'       => $f['displayName']       ?? $formatId,
        'legalSets'         => $f['legalSets']         ?? [],
        'banned'            => $f['banned']            ?? [],
        'copyExceptions'    => $f['copyExceptions']    ?? [],
        'deckSizeModifiers' => $f['deckSizeModifiers'] ?? [],
        'enabled'           => $f['enabled']           ?? true,
    ];
}

function SWUListFormats() {
    $out = [];
    foreach (array_keys(SWUFormatDefinitions()) as $id) {
        $f = SWUGetFormat($id);
        if ($f['enabled']) $out[$id] = $f['displayName'];
    }
    return $out;
}

function SWUFormatLegalSets($formatId) {
    $f = SWUGetFormat($formatId);
    if ($f === null) return [];
    $legal = $f['legalSets'];
    if ($legal === '*') {
        static $allSetKeys = null;
        if ($allSetKeys === null) {
            $all = require __DIR__ . '/../SWUDeck/AllSets.php';
            $allSetKeys = is_array($all) ? array_keys($all) : [];
        }
        return $allSetKeys;
    }
    return is_array($legal) ? $legal : [];
}

function SWUQueueTypeDefinitions() {
    return [
        'bo1' => ['displayName' => 'Best of 1', 'bestOf' => 1, 'sideboard' => false],
        'bo3' => ['displayName' => 'Best of 3', 'bestOf' => 3, 'sideboard' => true],
    ];
}

function SWUGetQueueType($id) {
    $defs = SWUQueueTypeDefinitions();
    return $defs[$id] ?? null;
}
