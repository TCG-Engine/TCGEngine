<?php
// Single source of truth for SWU formats + queue types (config-as-code).
//
// To DISABLE a temporary format (e.g. Preview after its window closes): set
// 'enabled' => false (preferred — keeps in-flight matches resolvable) or comment
// out the whole block. SWUListFormats() hides disabled formats from selectors,
// but SWUGetFormat()/SWUCheckFormat() still resolve them for anything mid-flight.

function SWUFormatDefinitions() {
    return [
        // NOTE: JTL_256 (Vulture Droid) copy-exception and JTL_024/025 deck-size modifiers are
        // GLOBAL card-intrinsic rules (see SWUGlobal*() below) — applied to every format EXCEPT
        // Open. Do NOT re-list them per format.
        'premier' => [
            'displayName' => 'Premier',
            'legalSets'   => ['JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'ASH'],  // curated rotation
            'banned'      => [],
            'enabled'     => true,
        ],
        'eternal' => [
            'displayName' => 'Eternal',
            'legalSets'   => '*',                                 // every printed set
            'banned'      => ['JTL_140', 'JTL_170'],
            'enabled'     => true,
        ],
        'open' => [
            'displayName'           => 'Open',
            'legalSets'             => '*',
            'banned'                => [],                        // no bans, ever
            'ignoreGlobalCardRules' => true,                     // unrestricted pool: no copy-exceptions / deck-size mods
            'enabled'               => true,
        ],

        // ── SOLO / LOCAL MODES ────────────────────────────────────────────────
        // Not matchmade — JoinQueue creates the game immediately. Both validate decks
        // like Open (unrestricted pool). 'mode' marks them so the menu can special-case
        // the UI (single vs. double deck input) and skip the "logged-in" queue gate.
        'goldfish' => [
            'displayName'           => 'Goldfish (Solo)',
            'legalSets'             => '*',
            'banned'                => [],
            'ignoreGlobalCardRules' => true,
            'mode'                  => true,
            'enabled'               => true,
        ],
        'hotseat' => [
            'displayName'           => 'Hotseat (2P local)',
            'legalSets'             => '*',
            'banned'                => [],
            'ignoreGlobalCardRules' => true,
            'mode'                  => true,
            'enabled'               => true,
        ],

        // ── TWIN SUNS (multiplayer; footprint only) ──────────────────────────
        // CR §12: a 4-player format with UNIQUE deckbuilding — two leaders, and a
        // singleton (highlander) deck. Deck validation is scaffolded here, but the
        // format is NOT queueable (the engine has no 4-player support yet), so it
        // ships 'enabled' => false. Rules encoded: all sets, no bans (yet), exactly
        // 2 different leaders + 1 base, min 80 other cards, max 1 copy of any card
        // (CR §12.2.2 — the 1-copy limit includes leaders).
        // NOT YET ENFORCED: CR §12.2.1.a's leader aspect-pairing restriction ("faceup
        // sides cannot have both the <X> and <Y> aspects") — the aspect icons are
        // stripped from our CR copy; needs the exact aspect pair before implementing.
        'twinsuns' => [
            'displayName' => 'Twin Suns',
            'legalSets'   => '*',                                 // every printed set
            'banned'      => [],                                  // no bans yet
            'minDeck'     => 80,                                  // CR §12.2.1.a
            'maxCopies'   => 1,                                   // CR §12.2.2 (highlander)
            'leaderCount' => 2,                                   // CR §12.2.1.a / §12.3
            'enabled'     => true,                                // private-room lobby ships this session
        ],

        // ── PREVIEW (temporary) ──────────────────────────────────────────────
        // Premier pool + the upcoming set's previews. Flip 'enabled' => true and
        // add the new set code to 'legalSets' when a preview window opens; set it
        // back to false (or comment out) when the window closes.
        'preview' => [
            'displayName' => 'Preview',
            'legalSets'   => ['JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'ASH'],  // + '<NEXT_SET>' when live
            'banned'      => [],
            'enabled'     => false,
        ],
    ];
}

// Global card-intrinsic deckbuilding rules — the card text itself sets these, so they hold in every
// constructed format EXCEPT Open (unrestricted pool; opts out via 'ignoreGlobalCardRules' => true).
// Merged into each format's copy-exceptions / deck-size modifiers by SWUGetFormat().
function SWUGlobalCopyExceptions()    { return ['JTL_256' => 15]; }                     // Vulture Droid
function SWUGlobalDeckSizeModifiers() { return ['JTL_024' => +10, 'JTL_025' => -5]; }   // deck-size bases

function SWUGetFormat($formatId) {
    $defs = SWUFormatDefinitions();
    if (!isset($defs[$formatId])) return null;
    $f = $defs[$formatId];
    // Layer the global card-intrinsic rules on top of any format-specific entries, unless the format
    // opts out (Open). `+` = format-specific entry wins on a key clash.
    $copyExceptions    = $f['copyExceptions']    ?? [];
    $deckSizeModifiers = $f['deckSizeModifiers'] ?? [];
    if (empty($f['ignoreGlobalCardRules'])) {
        $copyExceptions    = $copyExceptions    + SWUGlobalCopyExceptions();
        $deckSizeModifiers = $deckSizeModifiers + SWUGlobalDeckSizeModifiers();
    }
    return [
        'id'                => $formatId,
        'displayName'       => $f['displayName']       ?? $formatId,
        'legalSets'         => $f['legalSets']         ?? [],
        'banned'            => $f['banned']            ?? [],
        'copyExceptions'    => $copyExceptions,
        'deckSizeModifiers' => $deckSizeModifiers,
        'minDeck'           => $f['minDeck']           ?? 50,   // min "other cards" (units/events/upgrades)
        'maxCopies'         => $f['maxCopies']         ?? 3,    // default copy limit per card
        'leaderCount'       => $f['leaderCount']       ?? 1,    // leaders required in the deck
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
            $all = require __DIR__ . '/AllSets.php';
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

// Public matchmaking (anonymous "Join Queue") is off at launch — SWUSim only.
// Flip to true when there's enough player volume to keep queues from sitting empty.
// Private invites, solo modes (goldfish/hotseat), and Twin Suns rooms are UNAFFECTED —
// they don't go through the public-queue scan this gates (see JoinQueue.php).
function SWUPublicQueueEnabled() {
    return false;
}
