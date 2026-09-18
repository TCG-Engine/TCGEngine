<?php
// Single source of truth for SWU formats + queue types (config-as-code).
//
// To DISABLE a temporary format (e.g. Preview after its window closes): set
// 'enabled' => false (preferred — keeps in-flight matches resolvable) or comment
// out the whole block. SWUListFormats() hides disabled formats from selectors,
// but SWUGetFormat()/SWUCheckFormat() still resolve them for anything mid-flight.

function SWUFormatDefinitions() {
    $premierSets = ['JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'ASH', 'HMW',];   // HMW released 2026-09-16 (owner)
    $eternalSets = ['SOR', 'SHD', 'TWI', 'JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'TS26', 'ASH', 'HMW',];
    $previewSets = ['IC27',];  // the next set to release (or a future set's preview window)
    $premierBans = ['ASH_011'];
    $eternalBans = ['JTL_140', 'JTL_170'];
    return [
        // NOTE: JTL_256 (Vulture Droid) copy-exception and JTL_024/025 deck-size modifiers are
        // GLOBAL card-intrinsic rules (see SWUGlobal*() below). Do NOT re-list them per format.
        // The copy exception applies EVERYWHERE (printed card text); the deck-size modifiers apply
        // everywhere except the 'unrestricted' formats, which enforce nothing at all.
        'premier' => [
            'displayName' => 'Premier',
            'legalSets'   => $premierSets,
            'banned'      => $premierBans,
            'enabled'     => true,
            'publicQueue' => true,   // public matchmaking (owner, 2026-09-16: Constructed only)
        ],
        'eternal' => [
            'displayName' => 'Eternal',
            'legalSets'   => $eternalSets,
            'banned'      => $eternalBans,
            'enabled'     => true,
            'publicQueue' => true,   // public matchmaking (owner, 2026-09-16: Constructed only)
        ],
        // Open enforces NOTHING — USER RULING 2026-09-08: "Open format should allow any and all
        // lists. so no deck min or max. no copies min or max. no leader limit." 'unrestricted' makes
        // SWUCheckFormat accept every list outright, so a 20-card pile, 12 copies of a card, or a
        // two-leader Twin Suns list all load. SWUDeck's BUILDER still offers one leader slot for an
        // Open deck (the identity banner has one slot); that is leaderCount, below, and deliberate.
        'open' => [
            'displayName'   => 'Open',
            'legalSets'     => '*',
            'banned'        => [],                        // no bans, ever
            'unrestricted'  => true,
            'enabled'       => true,
            'publicQueue' => true,   // public matchmaking (owner, 2026-09-16: Constructed only)
        ],

        // ── SOLO / LOCAL MODES ────────────────────────────────────────────────
        // Not matchmade — JoinQueue creates the game immediately. Both validate decks
        // like Open: 'unrestricted', so a Twin Suns list (two leaders, 80 singleton cards) loads
        // into either of them unchanged. 'localMode' marks them so the menu can special-case
        // the UI (single vs. double deck input) and skip the "logged-in" queue gate.
        'goldfish' => [
            'displayName'   => 'Goldfish (Solo)',
            'legalSets'     => '*',
            'banned'        => [],
            'unrestricted'  => true,
            'localMode'     => true,
            'enabled'       => true,
        ],
        'hotseat' => [
            'displayName'   => 'Hotseat (2P local)',
            'legalSets'     => '*',
            'banned'        => [],
            'unrestricted'  => true,
            'localMode'     => true,
            'enabled'       => true,
        ],
        // Bot Practice: the human loads their own list plus a list for the bot, which occupies
        // seat 2 and answers its own prompts through Core/BotController.php. Unlike goldfish,
        // seat 2 is a REAL seat — real deck, real mulligan, losable base, equal shot at the die
        // roll — so none of the goldfish passive-seat gates may match it.
        //
        // ⚠ DISABLED ON PURPOSE — ADMIN-ONLY IN THE MENU. 'enabled' => false hides the format from
        // SWUListFormats(), so ordinary players' menus never list it. The SWUSim menu IS wired for it
        // (2026-09-14: applyFormatUI() shows the bot deck link and Play Style select, and the Start
        // button) and adds the entry itself when SWUBotPracticeAllowed() — local dev, or an approved
        // moderator (owner, 2026-09-15: admins try it and give feedback); JoinQueue enforces the same
        // gate. To ship it to players, flip this to true AND drop MainMenu's gated insert and
        // JoinQueue's check (and the two "disabled / hidden" checks in test_swusim_botpractice_mode.php).
        //
        // Disabled ≠ unreachable: SWUGetFormat('botpractice') still resolves (see the file header),
        // so APIs/Lobbies/JoinQueue.php still accepts format=botpractice, the deck check still runs
        // unrestricted, and SWUSim/CreateGame.php still stamps SWU_MODE_BOTPRACTICE. Phase 1 is
        // reachable programmatically and by the headless harness; it is only absent from the menu.
        'botpractice' => [
            'displayName'   => 'Arenabot',   // the player-facing name since 2026-09-16; the id stays botpractice
            'legalSets'     => '*',
            'banned'        => [],
            'unrestricted'  => true,
            'localMode'     => true,
            'enabled'       => false,   // admin-only via MainMenu's own insert (see above); true = shipped
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
        // JTL_256 Swarming Vulture Droid IS Common, and its 15-copy exception is printed card text,
        // so it stays live here (as it does in every format).
        'padawan' => [
            'displayName'   => 'Padawan',
            'legalSets'     => $eternalSets,
            'banned'        => [],
            'legalRarities' => ['Common'],
            'enabled'       => true,
            'publicQueue' => true,   // public matchmaking (owner, 2026-09-16: Constructed only)
        ],
        'padawan-preview' => [
            'displayName'   => 'Padawan Preview (IC27)',
            'legalSets'     => array_merge($eternalSets, $previewSets),
            'banned'        => [],
            'legalRarities' => ['Common'],
            'enabled'       => true,
            'publicQueue' => true,   // public matchmaking (owner, 2026-09-16: Constructed only)
        ],

        // ── PREVIEW (temporary) ──────────────────────────────────────────────
        // Premier pool + the upcoming set's previews. Flip 'enabled' => true and
        // add the new set code to 'legalSets' when a preview window opens; set it
        // back to false (or comment out) when the window closes.
        'preview' => [
            'displayName' => 'Premier Preview (IC27)',
            'legalSets'   => array_merge($premierSets, $previewSets),
            'banned'      => $premierBans,
            'enabled'     => true,
            'publicQueue' => true,   // public matchmaking (owner, 2026-09-16: Constructed only)
        ],
        'twinsuns-preview' => [
            'displayName' => 'Twin Suns Preview (IC27)',
            'legalSets'   => array_merge($eternalSets, $previewSets),
            'banned'      => [],
            'minDeck'     => 80,
            'maxCopies'   => 1,
            'leaderCount' => 2,
            // ⚠ REQUIRED, not optional. SWUFormatSeatRange defaults a missing min/maxPlayers to 2,
            // and SWUFormatIsRoomFormat is `max > 2` — so omitting these did not merely mis-size the
            // room, it took the format OUT of the room flow entirely: "Twin Suns Preview" shipped as
            // a 2-player fill-and-go queue that still demanded a 2-leader/80-card singleton deck.
            // A preview format mirrors its base on everything except the card pool; the parity check
            // in DevTools/tdd-regression/test_swusim_formats_config.php now enforces that.
            'minPlayers'  => 3,
            'maxPlayers'  => 4,
            'enabled'     => true,
        ],
        // Team Suns pool + the upcoming set's previews (owner, 2026-09-16: "add both options to Team Suns as well").
        // This REVERSES the 2026-08-29 decision recorded in DevTools/tdd-regression/test_swusim_formats_config.php
        // ("Team Suns previews are not offered"). It mirrors 'teamsuns' on every rule key, and the preview-family parity
        // check enforces that. The seat range and the team markers are REQUIRED, for the reason twinsuns-preview's note
        // above explains. The id is 16 characters: exactly the stats tables' `format varchar(16)` limit.
        'teamsuns-preview' => [
            'displayName'       => 'Team Suns Preview',
            'legalSets'         => array_merge($eternalSets, $previewSets),
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
        // Eternal pool + the upcoming set's previews — the Eternal counterpart of 'preview'. Same
        // shape as 'eternal' (no rarity restriction, standard deck rules); only the pool differs.
        // SWUFormatIsPreview derives preview-ness from the pool, so this needs no separate wiring.
        'eternal-preview' => [
            'displayName' => 'Eternal Preview',
            'legalSets'   => array_merge($eternalSets, $previewSets),
            'banned'      => $eternalBans,
            'enabled'     => true,
            'publicQueue' => true,   // public matchmaking (owner, 2026-09-16: Constructed only)
        ],
    ];
}

// Global card-intrinsic deckbuilding rules — the card text itself sets these.
//
// ⚠ The two halves are NOT symmetric, and the asymmetry is the point:
//   • A copy exception is a PERMISSION printed on the card ("a deck can have up to 15 copies of this
//     card"). **Card text always beats game rules — USER RULING 2026-09-08** — so it applies in EVERY
//     format with no opt-out, Open and the solo modes included, and it overrides a format's own
//     maxCopies (Twin Suns is highlander at 1, and still takes 15 Vulture Droids).
//   • A deck-size modifier is a REQUIREMENT the card imposes on the rest of the deck, so it is moot
//     in an 'unrestricted' format — those enforce no deck size at all.
// Merged into each format by SWUGetFormat().
function SWUGlobalCopyExceptions()    { return ['JTL_256' => 15]; }                     // Vulture Droid
function SWUGlobalDeckSizeModifiers() { return ['JTL_024' => +10, 'JTL_025' => -5]; }   // deck-size bases

function SWUGetFormat($formatId) {
    $defs = SWUFormatDefinitions();
    if (!isset($defs[$formatId])) return null;
    $f = $defs[$formatId];
    $unrestricted = !empty($f['unrestricted']);
    // Layer the global card-intrinsic rules on top of any format-specific entries. `+` = the
    // format-specific entry wins on a key clash.
    //
    // Copy exceptions have NO opt-out: they are printed card text, which beats format rules
    // (see SWUGlobalCopyExceptions). Before 2026-09-08 the unrestricted formats suppressed them,
    // which made Open the ONLY format that capped Swarming Vulture Droid at 3 — stricter than
    // Premier, the exact opposite of what "unrestricted" means.
    $copyExceptions    = ($f['copyExceptions'] ?? []) + SWUGlobalCopyExceptions();
    $deckSizeModifiers = $unrestricted ? [] : (($f['deckSizeModifiers'] ?? []) + SWUGlobalDeckSizeModifiers());
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
        // An unrestricted format has no floor and no ceiling. These sentinels are what a consumer
        // that reads the numbers directly (SWUDeck's add gate) sees; SWUCheckFormat short-circuits
        // on 'unrestricted' before it looks at either.
        'minDeck'           => $unrestricted ? 0 : ($f['minDeck'] ?? 50),   // min "other cards"
        'maxCopies'         => $unrestricted ? PHP_INT_MAX : ($f['maxCopies'] ?? 3),
        // Enforce nothing: every list is legal. Set on Open and the local solo modes.
        'unrestricted'      => $unrestricted,
        'leaderCount'       => $f['leaderCount']       ?? 1,    // leaders required in the deck
        'enabled'           => $f['enabled']           ?? true,
        // May this format use the public matchmaking queue (APIs/Lobbies/JoinQueue.php)? Ask
        // SWUFormatAllowsPublicQueue(), which also applies the site-wide switch — never read this key directly.
        'publicQueue'       => !empty($f['publicQueue']),
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

// ── The SWUSim game-setup menu (docs/superpowers/specs/2026-09-16-swusim-format-menu-design.md) ─────────────────────
// The menu is a VIEW over the format ids above; the ids do not change. Three questions, one dropdown each: the game type,
// then the opponent / players / mode, then the card pool.
//
// An option that carries its own 'format' (Arenabot, Goldfish, Hotseat) stores THAT format, and its pools only say which
// card pool it plays. Every other option stores the chosen pool's format. So Constructed → Arenabot → Premier stores
// 'botpractice' with card pool 'premier', while Constructed → PvP → Premier stores 'premier'.
//
// "Standard" and "Preview" are MENU labels only — the displayNames stay, because SWUDeck and the stats pages show them.
// SWUSim/DevTools/tests/menu_tree_test.php keeps this tree and the registry in step in both directions.
// " (IC27)" — the set(s) a preview pool is opened for, ready to append to a menu label so a player can
// see WHICH preview they are picking (owner, 2026-09-18). Derived from AppCore/SWU/PreviewSets.php, the
// same single source of truth SWUFormatIsPreview() reads, so removing a set on release day (the sunset
// checklist) drops it from every label with no edit here.
// ⚠ Returns '' when nothing is previewing — between windows the labels must read "Premier Preview", not
// "Premier Preview ()". Several sets would read " (IC27/IC28)".
function SWUPreviewSetSuffix(): string {
    static $suffix = null;
    if ($suffix === null) {
        $p = require __DIR__ . '/PreviewSets.php';
        $sets = is_array($p) ? array_values(array_filter($p)) : [];
        $suffix = empty($sets) ? '' : ' (' . implode('/', $sets) . ')';
    }
    return $suffix;
}

function SWUMenuTree(): array {
    $pv = SWUPreviewSetSuffix();   // '' between preview windows — see SWUPreviewSetSuffix
    $constructedPools = [
        ['format' => 'premier',         'label' => 'Premier'],
        ['format' => 'preview',         'label' => 'Premier Preview' . $pv],
        ['format' => 'eternal',         'label' => 'Eternal'],
        ['format' => 'eternal-preview', 'label' => 'Eternal Preview' . $pv],
        ['format' => 'padawan',         'label' => 'Padawan'],
        ['format' => 'padawan-preview', 'label' => 'Padawan Preview' . $pv],
        ['format' => 'open',            'label' => 'Open'],
    ];
    $tree = [
        ['id' => 'constructed', 'label' => 'Constructed', 'secondLabel' => 'Opponent', 'options' => [
            ['id' => 'pvp',      'label' => 'PvP',      'pools' => $constructedPools],
            // "(beta)" is deliberate (owner, 2026-09-18, at the point Arenabot opened to all logged-in
            // players): it keeps the heuristic bot from being mistaken for the competitive bot that was
            // promised. The format id and displayName stay 'botpractice' / 'Arenabot' — this is the
            // player-facing CHOICE label only.
            ['id' => 'arenabot', 'label' => 'Arenabot (beta)', 'format' => 'botpractice', 'pools' => $constructedPools],
        ]],
        ['id' => 'twinsuns', 'label' => 'Twin Suns', 'secondLabel' => 'Players', 'options' => [
            ['id' => 'ffa', 'label' => 'Free-for-all', 'pools' => [
                ['format' => 'twinsuns',         'label' => 'Standard'],
                ['format' => 'twinsuns-preview', 'label' => 'Preview' . $pv],
            ]],
            ['id' => 'teams', 'label' => 'Teams', 'pools' => [
                ['format' => 'teamsuns',         'label' => 'Standard'],
                ['format' => 'teamsuns-preview', 'label' => 'Preview' . $pv],
            ]],
        ]],
        ['id' => 'solo', 'label' => '1P Mode', 'secondLabel' => 'Mode', 'options' => [
            ['id' => 'goldfish', 'label' => 'Goldfish', 'format' => 'goldfish', 'pools' => []],
            ['id' => 'hotseat',  'label' => 'Hotseat',  'format' => 'hotseat',  'pools' => []],
        ]],
    ];
    // Join Queue is offered per POOL: only a pool whose option has no format of its own (PvP) stores the pool's format,
    // so only those can queue. Arenabot and 1P options store their own local-mode format and never queue.
    foreach ($tree as &$gt) {
        foreach ($gt['options'] as &$opt) {
            foreach ($opt['pools'] as &$p) {
                $p['publicQueue'] = !isset($opt['format']) && SWUFormatAllowsPublicQueue($p['format']);
            }
            unset($p);
        }
        unset($opt);
    }
    unset($gt);
    return $tree;
}

// Every selectable leaf of a tree, with what it stores. An option with no pools is itself a leaf and plays card pool 'open'.
function SWUMenuLeaves(array $tree): array {
    $out = [];
    foreach ($tree as $gt) {
        foreach ($gt['options'] as $opt) {
            if (empty($opt['pools'])) {
                $out[] = ['gameType' => $gt['id'], 'option' => $opt['id'], 'pool' => null,
                          'format' => strval($opt['format'] ?? ''), 'cardPool' => 'open'];
                continue;
            }
            foreach ($opt['pools'] as $p) {
                $out[] = ['gameType' => $gt['id'], 'option' => $opt['id'], 'pool' => $p['format'],
                          'format' => strval($opt['format'] ?? $p['format']), 'cardPool' => $p['format']];
            }
        }
    }
    return $out;
}

// The tree one viewer is offered. Dropped:
//   • disabled formats — a preview window that has closed;
//   • the Arenabot option unless $arenabotAllowed (SWUSim/Mod/DevGate.php SWUBotPracticeAllowed());
//   • logged out, every PvP pool but Open and the whole Twin Suns branch — APIs/Lobbies/JoinQueue.php refuses those
//     without an account, while Arenabot and 1P Mode never needed one;
//   • a pooled option whose every pool dropped, and a game type left with no options.
// $isEnabled is injectable for tests; by default it asks the registry.
function SWUMenuTreeFor(bool $loggedIn, bool $arenabotAllowed, ?callable $isEnabled = null): array {
    $isEnabled = $isEnabled ?? function (string $id): bool {
        $f = SWUGetFormat($id);
        return $f !== null && !empty($f['enabled']);
    };
    $out = [];
    foreach (SWUMenuTree() as $gt) {
        if (!$loggedIn && $gt['id'] === 'twinsuns') continue;
        $options = [];
        foreach ($gt['options'] as $opt) {
            if ($opt['id'] === 'arenabot') {
                if (!$arenabotAllowed) continue;
            } else if (isset($opt['format']) && !$isEnabled($opt['format'])) {
                continue;
            }
            if (empty($opt['pools'])) { $options[] = $opt; continue; }
            $pools = array_values(array_filter($opt['pools'], function ($p) use ($isEnabled, $loggedIn, $opt) {
                if (!$isEnabled($p['format'])) return false;
                return $loggedIn || $opt['id'] !== 'pvp' || $p['format'] === 'open';
            }));
            if (empty($pools)) continue;
            $opt['pools'] = $pools;
            $options[] = $opt;
        }
        if (empty($options)) continue;
        $gt['options'] = $options;
        $out[] = $gt;
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

// Site-wide kill switch for SWUSim public matchmaking. ON since 2026-09-16 (owner: public queues for Constructed).
// Return false to close every public queue at once; private rooms, invites and solo modes are unaffected.
function SWUPublicQueueEnabled() {
    return true;
}

// THE question every consumer asks — the lobby endpoint, the menu tree and the tests: may $formatId be queued publicly
// right now? True only for an enabled format flagged 'publicQueue' while the switch is on. $switchOn is injectable for
// tests; null reads SWUPublicQueueEnabled(). SWUSim/DevTools/tests/public_queue_policy_test.php.
function SWUFormatAllowsPublicQueue(string $formatId, ?bool $switchOn = null): bool {
    $on = $switchOn ?? SWUPublicQueueEnabled();
    if (!$on) return false;
    $f = SWUGetFormat($formatId);
    return $f !== null && !empty($f['enabled']) && !empty($f['publicQueue']);
}
