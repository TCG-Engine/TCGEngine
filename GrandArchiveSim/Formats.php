<?php
// GrandArchive formats + queue types (config-as-code). GA has no separate deckbuilder app in this repo
// yet, so this lives in the GA root dir; promote to AppCore/ when a shared consumer needs it.
// Standard and Open are both all-sets-legal constructed formats; they differ ONLY by the banlist.

function GAFormatDefinitions() {
    return [
        'standard' => [
            'displayName' => 'Standard',
            'legalSets'   => '*',
            'banned'      => [
                '0hsncz1fz2', // Baby Gray Slime
                'rw8qq1uwq8', // Corhazi Outlook
                'dmfoA7jOjy', // Crystal of Empowerment
                'dBAdWMoPEz', // Erupting Rhapsody
                '6fxxgmuesd', // Icebound Slam
                'dmbBXRTVIk', // Sword of Avarice
                'gJ2dsgywEs', // Reckless Conversion
                'egbscxwjbq', // Lost in Thought
                '2d7rgchttu', // Dissonant Fractal
                'ye7f7o5yut', // Rile the Abyss
            ],
            'enabled'     => true,
        ],
        'open' => [
            'displayName' => 'Open',
            'legalSets'   => '*',
            'banned'      => [],
            'enabled'     => true,
        ],
        'goldfish' => [
            'displayName' => 'Goldfish (Solo)',
            'legalSets'   => '*',
            'banned'      => [],
            'mode'        => true,   // solo/testing mode: skips structural deckbuilding rules
            'enabled'     => true,
        ],
        'hotseat' => [
            'displayName' => 'Hotseat (2P local)',
            'legalSets'   => '*',
            'banned'      => [],
            'mode'        => true,   // solo/local mode: skips structural deckbuilding rules
            'enabled'     => true,
        ],
    ];
}

function GAGetFormat($id) {
    $defs = GAFormatDefinitions();
    return $defs[strtolower(trim((string)$id))] ?? null;
}

function GAListFormats() {
    $out = [];
    foreach (GAFormatDefinitions() as $id => $f) {
        if (!empty($f['enabled'])) $out[$id] = $f['displayName'];
    }
    return $out;
}

function GAQueueTypeDefinitions() {
    return [
        'bo1' => ['displayName' => 'Best of 1', 'bestOf' => 1, 'enabled' => true],
        'bo3' => ['displayName' => 'Best of 3', 'bestOf' => 3, 'enabled' => true],
    ];
}

function GAGetQueueType($id) {
    $q = GAQueueTypeDefinitions()[strtolower(trim((string)$id))] ?? null;
    return ($q && !empty($q['enabled'])) ? $q : null;
}

function GACardBanned($cardUUID, $formatId) {
    $f = GAGetFormat($formatId);
    if ($f === null) return false;
    return in_array($cardUUID, $f['banned'] ?? [], true);
}

// Constructed deckbuilding constraints (CR §129–132). Applied to non-mode formats only.
function GAConstructedDeckRules() {
    return [
        'mainMin'                        => 60,
        'mainMaxCopies'                  => 4,   // combined main + sideboard, by name
        'materialMax'                    => 12,
        'materialMaxCopies'              => 1,   // by name
        'materialNeedsLv0Champion'       => true,
        'sideboardMaxCards'              => 15,
        'sideboardMaxPoints'             => 15,
        'sideboardChampionRegaliaPoints' => 3,
    ];
}
