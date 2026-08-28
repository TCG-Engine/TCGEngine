<?php
// Single source of truth for SWU formats + queue types (config-as-code).
//
// To DISABLE a temporary format (e.g. Preview after its window closes): set
// 'enabled' => false (preferred — keeps in-flight matches resolvable) or comment
// out the whole block. SWUListFormats() hides disabled formats from selectors,
// but SWUGetFormat()/SWUCheckFormat() still resolve them for anything mid-flight.

function SWUFormatDefinitions() {
    $premierSets = ['JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'ASH',];
    $eternalSets = ['SOR', 'SHD', 'TWI', 'JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'TS26', 'ASH',];
    $previewSets = ['HMW',];   // the next set to release (or a future set's preview window)
    $premierBans = ['ASH_011'];
    $eternalBans = ['JTL_140', 'JTL_170'];
    return [
        // NOTE: JTL_256 (Vulture Droid) copy-exception and JTL_024/025 deck-size modifiers are
        // GLOBAL card-intrinsic rules (see SWUGlobal*() below) — applied to every format EXCEPT
        // Open. Do NOT re-list them per format.
        'premier' => [
            'displayName' => 'Premier',
            'legalSets'   => $premierSets,
            'banned'      => $premierBans,
            'enabled'     => true,
        ],
        'eternal' => [
            'displayName' => 'Eternal',
            'legalSets'   => $eternalSets,
            'banned'      => $eternalBans,
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
        // like Open (unrestricted pool). 'localMode' marks them so the menu can special-case
        // the UI (single vs. double deck input) and skip the "logged-in" queue gate.
        'goldfish' => [
            'displayName'           => 'Goldfish (Solo)',
            'legalSets'             => '*',
            'banned'                => [],
            'ignoreGlobalCardRules' => true,
            'localMode'             => true,
            'enabled'               => true,
        ],
        'hotseat' => [
            'displayName'           => 'Hotseat (2P local)',
            'legalSets'             => '*',
            'banned'                => [],
            'ignoreGlobalCardRules' => true,
            'localMode'             => true,
            'enabled'               => true,
        ],

        // ── TWIN SUNS / TEAM SUNS (multiplayer rooms) ────────────────────────
        // CR §12: 4-player formats with UNIQUE deckbuilding — two leaders and a singleton
        // (highlander) deck. Rules encoded: all sets, no bans, exactly 2 different leaders +
        // 1 base, min 80 other cards, max 1 copy of any card (CR §12.2.2 — the 1-copy limit
        // includes leaders). CR §12.2.1.a's leader aspect-pairing restriction IS enforced, in
        // SWUCheckFormat (see _SWULeaderStartAlignment in DeckValidation.php).
        'twinsuns' => [
            'displayName' => 'Twin Suns',
            'legalSets'   => $eternalSets,                        // every printed set
            'banned'      => [],                                  // no bans yet
            'minDeck'     => 80,                                  // CR §12.2.1.a
            'maxCopies'   => 1,                                   // CR §12.2.2 (highlander)
            'leaderCount' => 2,                                   // CR §12.2.1.a / §12.3
            'minPlayers'  => 3,
            'maxPlayers'  => 4,
            'enabled'     => true,
        ],
        // Team Suns: 2v2 Twin Suns. Same deckbuilding, plus one TEAM-wide rule — no leader may
        // appear twice on a team (uniqueTeamLeaders). Strictly 4P; there is no 3P Team Suns.
        'teamsuns' => [
            'displayName'       => 'Team Suns',
            'legalSets'         => $eternalSets,
            'banned'            => [],
            'minDeck'           => 80,
            'maxCopies'         => 1,
            'leaderCount'       => 2,
            'minPlayers'        => 4,
            'maxPlayers'        => 4,
            'teams'             => 2,
            'uniqueTeamLeaders' => true,
            'enabled'           => true,
        ],

        // ── PADAWAN (SWU Pauper / Commons) ───────────────────────────────────
        // Community format run by Indy SWU. Eternal pool, but every card EXCEPT the leader must be
        // printed as a Common in a main/Twin Suns set. Leaders are unrestricted ("Any Leader") —
        // enforced structurally, since SWUCheckFormat receives leaders in their own parameter and
        // simply never rarity-checks them.
        //
        // legalSets is the Eternal list VERBATIM, deliberately including IBH. Per the ruling, IBH
        // LEADERS are legal but IBH CARDS are not — and that falls out for free, because all 104
        // IBH non-leader cards and both IBH bases are Special. Do NOT add a set-exclusion here.
        //
        // Nothing else needs configuring; the rarity rule already subsumes it:
        //   • "no ECL/TT/DV" — SOR_022 / SOR_025 / JTL_024 are all Rare bases
        //   • Eternal's bans  — JTL_140 is Rare, JTL_170 is Uncommon
        //   • deck size is always 50 — both deck-size-modifier bases (JTL_024, JTL_025) are Rare
        // JTL_256 Swarming Vulture Droid IS Common, so this must NOT set 'ignoreGlobalCardRules'
        // — its 15-copy exception stays live.
        'padawan' => [
            'displayName'   => 'Padawan',
            'legalSets'     => $eternalSets,
            'banned'        => [],
            'legalRarities' => ['Common'],
            'enabled'       => true,
        ],
        'padawan-preview' => [
            'displayName'   => 'Padawan Preview',
            'legalSets'     => array_merge($eternalSets, $previewSets),
            'banned'        => [],
            'legalRarities' => ['Common'],
            'enabled'       => true,
        ],

        // ── PREVIEW (temporary) ──────────────────────────────────────────────
        // Premier pool + the upcoming set's previews. Flip 'enabled' => true and
        // add the new set code to 'legalSets' when a preview window opens; set it
        // back to false (or comment out) when the window closes.
        'preview' => [
            'displayName' => 'Premier Preview',
            'legalSets'   => array_merge($premierSets, $previewSets),
            'banned'      => $premierBans,
            'enabled'     => true,
        ],
        'twinsuns-preview' => [
            'displayName' => 'Twin Suns Preview',
            'legalSets'   => array_merge($eternalSets, $previewSets),
            'banned'      => [],
            'minDeck'     => 80,
            'maxCopies'   => 1,
            'leaderCount' => 2,
            'enabled'     => true,
        ],
        // Eternal pool + the upcoming set's previews — the Eternal counterpart of 'preview'. Same
        // shape as 'eternal' (no rarity restriction, standard deck rules); only the pool differs.
        // SWUFormatIsPreview derives preview-ness from the pool, so this needs no separate wiring.
        'eternal-preview' => [
            'displayName' => 'Eternal Preview',
            'legalSets'   => array_merge($eternalSets, $previewSets),
            'banned'      => $eternalBans,
            'enabled'     => true,
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
        // null = no rarity restriction. Every pre-Padawan format omits the key and so keeps
        // byte-identical verdicts — this is the blast-radius guard for a shared AppCore file.
        'legalRarities'     => $f['legalRarities']     ?? null,
        'copyExceptions'    => $copyExceptions,
        'deckSizeModifiers' => $deckSizeModifiers,
        'minDeck'           => $f['minDeck']           ?? 50,   // min "other cards" (units/events/upgrades)
        'maxCopies'         => $f['maxCopies']         ?? 3,    // default copy limit per card
        'leaderCount'       => $f['leaderCount']       ?? 1,    // leaders required in the deck
        'enabled'           => $f['enabled']           ?? true,
        // Local/solo mode (Goldfish = solo; Hotseat = one human driving both seats) rather than a
        // matchmade format. This is the WAITING-ROOM ROUTING PREDICATE — a localMode format never
        // gets a lobby page — and it is also read by the stats gate to tell practice from results.
        // ⚠ This array is a WHITELIST: a key present in SWUFormatDefinitions() but absent here is
        // silently dropped, which would report localMode=false for EVERY format. Rename in both.
        'localMode'         => !empty($f['localMode']),
        // Seat count + team markers (Twin Suns / Team Suns). Defaults keep every 2-player format
        // byte-identical: minPlayers/maxPlayers 2, no teams. SWUGetFormat returns an explicit key
        // WHITELIST, so a key added to SWUFormatDefinitions() but not listed here is silently
        // dropped — add both, always.
        'minPlayers'        => intval($f['minPlayers'] ?? 2),
        'maxPlayers'        => intval($f['maxPlayers'] ?? 2),
        'teams'             => intval($f['teams'] ?? 0),
        'uniqueTeamLeaders' => !empty($f['uniqueTeamLeaders']),
    ];
}

// Is this format known to AppCore at all? Registration is separate from stats eligibility:
// open/goldfish/hotseat ARE registered (the endpoints accept them and record nothing), while an
// unregistered value is rejected outright — see the three-tier table in the design doc §4.
function SWUFormatIsRegistered($formatId) {
    $formatId = strtolower(trim(strval($formatId)));
    if ($formatId === '') return false;
    return SWUGetFormat($formatId) !== null;
}

// THE authority on which formats produce statistics. Every stats gate — the write allowlist, both
// meta API whitelists and both page dropdowns — derives from this. It used to be the literal
// ['premier','eternal','twinsuns','padawan'] retyped in eight places, which drifted: padawan was
// accepted by the APIs but absent from the dropdowns, so it could not be selected.
//
// Excluded: 'open' (an unrestricted anything-goes pool, so its results describe nothing) and the
// local/solo MODES — Goldfish is one player, Hotseat is one person playing both seats. Both are
// practice, not results. Derived from the 'localMode' flag so a future local mode is covered the day
// it is added rather than silently recording.
//
// Preview formats ARE included: they are first-class formats separated by their own format key,
// which is part of the PRIMARY KEY on the meta tables, so preview rows can never merge with
// released-format rows.
//
// $enabledOnly=true  → WRITE eligibility.
// $enabledOnly=false → READ eligibility. A preview format is DISABLED (not deleted) once its window
//                      closes, and its historical rows must stay selectable.
function SWUStatsFormats(bool $enabledOnly = true): array {
    $out = [];
    foreach (array_keys(SWUFormatDefinitions()) as $id) {
        if ($id === 'open') continue;
        $f = SWUGetFormat($id);
        if ($f === null || !empty($f['localMode'])) continue;
        if ($enabledOnly && empty($f['enabled'])) continue;
        $out[] = $id;
    }
    return $out;
}

function SWUListFormats() {
    $out = [];
    foreach (array_keys(SWUFormatDefinitions()) as $id) {
        $f = SWUGetFormat($id);
        if ($f['enabled']) $out[$id] = $f['displayName'];
    }
    return $out;
}

// Seat range for a format: [min, max]. Ordinary formats are strictly 2-player.
function SWUFormatSeatRange($formatId) {
    $f = SWUGetFormat($formatId);
    if ($f === null) return [2, 2];
    return [intval($f['minPlayers'] ?? 2), intval($f['maxPlayers'] ?? 2)];
}

// A "room" format is any format seating more than 2 — it uses the private-room lobby flow
// (roster, per-seat deck validation, explicit host Start) instead of fill-and-go matchmaking.
// Use this instead of comparing $lobby->format to a literal.
function SWUFormatIsRoomFormat($formatId) {
    [, $max] = SWUFormatSeatRange($formatId);
    return $max > 2;
}

// A "team" format splits its seats into fixed teams (Team Suns: Red/Blue, 2 each).
function SWUFormatIsTeamFormat($formatId) {
    $f = SWUGetFormat($formatId);
    return $f !== null && !empty($f['teams']);
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

// True when a format's pool DELIBERATELY includes an unreleased set — i.e. its legalSets list names
// one of AppCore/SWU/PreviewSets.php. Games in such a format are played with hand-curated mock cards
// that can be wrong, mid-errata, or deleted on release day, so they must not write stats
// (APIs/SubmitGameResult.php). See docs/superpowers/specs/2026-07-29-swu-preview-format-design.md §3.
//
// Derived from PreviewSets.php rather than a hardcoded format list, so removing a set on release day
// (sunset checklist step 5) turns the gate off by itself.
//
// The wildcard pool ('*' — Open/Goldfish/Hotseat) resolves to every registered set INCLUDING preview
// sets, but is deliberately NOT preview: it's an anything-goes pool rather than a curated preview
// window, and treating it as preview would silently change stats behavior for those formats.
function SWUFormatIsPreview($formatId) {
    $f = SWUGetFormat($formatId);
    if ($f === null) return false;
    $legal = $f['legalSets'];
    if (!is_array($legal)) return false;   // '*' wildcard
    static $previewSets = null;
    if ($previewSets === null) {
        $p = require __DIR__ . '/PreviewSets.php';
        $previewSets = is_array($p) ? $p : [];
    }
    return count(array_intersect($legal, $previewSets)) > 0;
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

// Public matchmaking (anonymous "Join Queue") is off in PRODUCTION at launch — SWUSim only — but ENABLED
// in the dev environment so Playwright/local dev can exercise the queue flow. This mirrors the Join Queue
// button gate exactly (SWUIsLocalDevRequest, SWUSim/Mod/DevGate.php): DEVENV, or a localhost/loopback Host
// over HTTP where php-fpm doesn't see DEVENV. Flip the production side on (return true) when there's enough
// player volume. Private invites, solo modes (goldfish/hotseat), and Twin Suns rooms are UNAFFECTED — they
// don't go through the public-queue scan this gates (see JoinQueue.php).
function SWUPublicQueueEnabled() {
    if (function_exists('SWUIsLocalDevRequest')) return SWUIsLocalDevRequest();
    if (getenv('DEVENV') === 'true') return true;
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    return str_starts_with($host, 'localhost')
        || str_starts_with($host, '127.0.0.1')
        || str_starts_with($host, '[::1]');
}
