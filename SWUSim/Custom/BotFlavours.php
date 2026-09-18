<?php
// FLAVOUR REGISTRY (RL bots spec, Section 5 "Flavour profiles"): the owner's flavour tags per ARCHETYPE, keyed by
// leader + the base ("LEADER|BASE_ID", else "LEADER|Aspect", else "LEADER|*"), so any deck — not only the fixtures — gets its
// flavours in live play. A deck not listed has none and plays its style alone. Source: the owner's labels (spec,
// "Archetype vocabulary"), as tagged in SWUSim/Tests/BotFixtures/meta-2026-09/README.md (bot_flavours_test.php
// checks every fixture header against this table).
require_once __DIR__ . '/../Rl/CardTags.php';

const SWU_BOT_FLAVOURS = [
    'ASH_009|Vigilance' => ['ground', 'combo'],          // Ahsoka, Blue
    'ASH_009|Cunning'   => ['mixed-space'],              // Ahsoka, Yellow
    'JTL_009|*'         => ['burn'],                     // Boba Fett (JTL)
    'ASH_017|*'         => ['go-wide', 'mixed'],         // Greef Karga
    'JTL_006|*'         => ['space'],                    // Darth Vader (JTL)
    'LAW_004|*'         => ['hard'],                     // Aurra Sing
    'SEC_010|*'         => ['hard'],                     // Dedra Meero
    'LAW_008|*'         => ['credit-ramp'],              // Director Krennic
    'LAW_018|*'         => ['credit-ramp', 'tempo'],     // Lando (LAW)
    'JTL_005|*'         => ['capital-ship'],             // Admiral Piett — red (Normal) and blue (Control)
    'JTL_012|*'         => ['space', 'combo', 'pilot'],  // Luke (JTL)
    'LOF_009|*'         => ['tempo', 'force'],           // Darth Maul
    'LOF_002|*'         => ['tempo', 'force'],           // Mother Talzin
    // Second fixture batch (2026-09-15). A label that names a BASE is keyed by the base's CardID (owner: "the flavor
    // profiles should come from the leader/base combo"); one that names a colour, by the aspect.
    'ASH_001|JTL_028'   => ['go-tall', 'upgrades'],      // The Armorer, Nabat Village
    'LOF_008|LOF_019'   => ['go-wide', 'force', 'high-hp'], // Obi-Wan, Vergence Temple (not the 28-HP Force-base lists)
    'JTL_002|Cunning'   => ['combo', 'when-defeated'],   // Thrawn (JTL), Yellow
    'JTL_002|JTL_024'   => ['bombs'],                    // Thrawn (JTL), Data Vault
    'ASH_014|JTL_021'   => ['hard', 'combo'],            // The Mandalorian (ASH), Colossus
    'LAW_004|JTL_024'   => ['setup'],                    // Aurra Sing, Data Vault (the red list stays 'hard')
    'LAW_013|LAW_019'   => ['hyper', 'credit'],          // Chewbacca (LAW), Alliance Outpost
];

// Flavours that change the ARCHETYPE RANK rather than a single weight (feature 'flavourrank').
// ⚠ History: giving flavours teeth as weight MULTIPLIERS failed its gate at 49.8%, and 'tempo' changed literally zero
// games, because tempo is about SEQUENCING (when to take initiative, exhausting a blocker first) which the scorer
// cannot express (part-3 plan, "Flavour layer, attempt 1 — REVERTED"). A rank shift is a much larger lever: it moves
// the whole kill ladder a step, so midrange's "remove a 3-cost blocker" becomes soft control's "remove a 2-cost".
// It is gated separately so the fidelity sweep can attribute it.
const SWU_BOT_FLAVOUR_RANK_SHIFT = [
    'tempo' => 1,   // owner, 2026-09-17: "a tempo deck would trade. a normal midrange deck would hit base"
];

function SWUBotFlavourRankShift(int $seat): int {
    if (function_exists('SWUBotFeatureOn') && !SWUBotFeatureOn('flavourrank')) return 0;
    // No live board (unit tests, offline tooling): there are no deck flavours to read. A pure scoring
    // input must never require game state — SWUBotWeights() calls through here on every decision.
    if (!function_exists('GetLeader')) return 0;
    $shift = 0;
    foreach (SWUBotDeckFlavours($seat) as $f) $shift += intval(SWU_BOT_FLAVOUR_RANK_SHIFT[$f] ?? 0);
    return $shift;
}

function SWUBotDeckFlavours(int $seat): array {
    $leader = strval((GetLeader($seat)[0] ?? null)->CardID ?? '');
    $base = GetBase($seat)[0] ?? null;
    $baseID = $base !== null ? strval($base->CardID ?? '') : '';
    $aspect = $baseID !== '' ? strval(CardAspect($baseID) ?? '') : '';
    return SWU_BOT_FLAVOURS["$leader|$baseID"] ?? SWU_BOT_FLAVOURS["$leader|$aspect"] ?? SWU_BOT_FLAVOURS["$leader|*"] ?? [];
}

// A card this deck should not resource while it holds filler (feature 'keep'): its answers (removal, wipe), its
// reach (burn), and its flavour's key cards — a Capital Ship in a capital-ship deck.
function SWUBotIsKeyCard(int $seat, string $cardID): bool {
    if (array_intersect(SWUBotCardTags($cardID), ['removal', 'wipe', 'burn'])) return true;
    return in_array('capital-ship', SWUBotDeckFlavours($seat), true) && str_contains(strval(CardTrait($cardID) ?? ''), 'Capital Ship');
}

// The cards this seat owns and can see (deck, hand, resources, discard, non-leader non-token units in play): the
// deck's composition, for the resource stop.
function SWUBotOwnCardIDs(int $seat): array {
    $ids = [];
    foreach ([GetDeck($seat), GetHand($seat), GetResources($seat), GetDiscard($seat)] as $zone) {
        foreach ($zone as $o) { if ($o !== null && empty($o->removed)) $ids[] = strval($o->CardID ?? ''); }
    }
    foreach (SWUBotUnits($seat) as $v) { if (!$v['isLeader'] && $v['cost'] > 0) $ids[] = $v['cardID']; }
    return $ids;
}
