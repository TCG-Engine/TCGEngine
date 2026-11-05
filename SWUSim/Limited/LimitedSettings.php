<?php
// Limited room settings: the host's choices, validated; the pack lineup each seat opens; the bases in play; the
// Sealed pool. Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.1, §3.3.
//
// Settings shape (all keys always present after SWULimitedDefaultSettings):
//   mode       'draft' | 'sealed'
//   bestOf     1 | 3
//   packCount  packs per player — Draft 3–6, Sealed 6–16 (below that a 30-card deck is impossible)
//   packMode   'single' (every pack is singleSet) | 'hostchoice' (everyone opens `lineup`) |
//              'chaos' (each seat's packs are rolled once from chaosSets — Draft needs ≥3 distinct, Sealed ≥2)
//   singleSet, lineup[], chaosSets[]   booster-set codes; only the active mode's field is read
require_once __DIR__ . '/LimitedDefaults.php';   // SWULimitedPackRange, SWULimitedDefaultSettings
require_once __DIR__ . '/LimitedPacks.php';
require_once __DIR__ . '/../Custom/BotCurveValue.php';   // SWUBotCurveValue — what a bot drafts and builds by (Plan 5)

// The settings a host's form POSTed, ready to validate: merged over the defaults (a client sends only what it shows),
// whole-number strings cast to int (a form sends "3"), list fields re-indexed, and the room's OWN mode kept — never the
// posted one. Anything still wrong is left for SWULimitedValidateSettings to name.
function SWULimitedSettingsFromRequest(array $posted, string $mode): array {
    $s = SWULimitedDefaultSettings($mode);
    foreach (['bestOf', 'packCount', 'packMode', 'singleSet', 'lineup', 'chaosSets'] as $k) {
        if (array_key_exists($k, $posted)) $s[$k] = $posted[$k];
    }
    foreach (['bestOf', 'packCount'] as $k) {
        if (is_string($s[$k]) && ctype_digit($s[$k])) $s[$k] = intval($s[$k]);
    }
    foreach (['lineup', 'chaosSets'] as $k) {
        if (is_array($s[$k])) $s[$k] = array_values($s[$k]);
    }
    $s['mode'] = $mode;
    return $s;
}

// Every problem with $s, as short messages the room can show the host. [] = valid. Unknown keys are ignored.
function SWULimitedValidateSettings(array $s): array {
    $errors = [];
    $mode = $s['mode'] ?? null;
    if (!in_array($mode, ['draft', 'sealed'], true)) {
        $errors[] = 'Choose Sealed or Draft.';
        $mode = null;
    }
    if (!in_array($s['bestOf'] ?? null, [1, 3], true)) $errors[] = 'Matches are Best of 1 or Best of 3.';

    $count = $s['packCount'] ?? null;
    if (!is_int($count)) {
        $errors[] = 'Packs per player must be a whole number.';
        $count = null;
    } elseif ($mode !== null) {
        [$min, $max] = SWULimitedPackRange($mode);
        if ($count < $min || $count > $max) $errors[] = ucfirst($mode) . " uses {$min}–{$max} packs per player.";
    }

    $notBooster = fn(array $sets) => array_values(array_filter($sets, fn($x) => !is_string($x) || !SWUIsBoosterSet($x)));
    switch ($s['packMode'] ?? null) {
        case 'single':
            if (!is_string($s['singleSet'] ?? null) || !SWUIsBoosterSet($s['singleSet'])) $errors[] = 'Pick a set that has booster packs.';
            break;
        case 'hostchoice':
            $lineup = is_array($s['lineup'] ?? null) ? array_values($s['lineup']) : [];
            if ($count !== null && count($lineup) !== $count) $errors[] = "Pick a set for each of the $count packs.";
            // Host's Choice alone may also name a supplemental set (IC27 Icons packs).
            if (array_filter($lineup, fn($x) => !is_string($x) || !SWUIsHostChoiceSet($x))) $errors[] = 'Every pack must be a set that has booster packs.';
            break;
        case 'chaos':
            $sets = is_array($s['chaosSets'] ?? null) ? array_values($s['chaosSets']) : [];
            if ($notBooster($sets)) $errors[] = 'Chaos can only roll sets that have booster packs.';
            $need = $mode === 'sealed' ? 2 : 3;
            if ($mode !== null && count(array_unique($sets)) < $need) $errors[] = 'Chaos ' . ucfirst($mode) . " needs at least $need different sets.";
            break;
        default:
            $errors[] = 'Choose Single Set, Host\'s Choice or Chaos.';
    }
    return $errors;
}

// The distinct sets a room draws from, in release order — this decides which common bases are in play.
function SWULimitedSetsInPlay(array $s): array {
    switch ($s['packMode']) {
        case 'single':     $sets = [$s['singleSet']]; break;
        case 'hostchoice': $sets = $s['lineup'];      break;
        default:           $sets = $s['chaosSets'];
    }
    return array_values(array_intersect(SWUHostChoiceSets(), $sets));   // release order, supplemental sets last
}

// [seat (1..N) => [set per pack]]. Chaos rolls each seat × pack once, independently, repeats allowed; the room
// stores the result, so there are no re-rolls. Settings must already be valid.
function SWULimitedRollLineups(array $s, int $seatCount, \Random\Randomizer $rng): array {
    $out = [];
    // Only the active mode's field is read: a minimal valid single/hostchoice request has no chaosSets at all.
    $chaos = $s['packMode'] === 'chaos' ? array_values(array_unique($s['chaosSets'])) : [];
    for ($seat = 1; $seat <= $seatCount; $seat++) {
        switch ($s['packMode']) {
            case 'single':     $out[$seat] = array_fill(0, $s['packCount'], $s['singleSet']); break;
            case 'hostchoice': $out[$seat] = array_values($s['lineup']); break;
            default:
                $out[$seat] = [];
                for ($i = 0; $i < $s['packCount']; $i++) $out[$seat][] = $chaos[$rng->getInt(0, count($chaos) - 1)];
        }
    }
    return $out;
}

// Every Common base of the sets in play — any of these is legal in a Limited deck without opening it. A lineup of only
// supplemental packs gets the latest main set's common bases (see the end).
function SWULimitedCommonBasesInPlay(array $setsInPlay): array {
    static $bySet = null;
    if ($bySet === null) {
        $bySet = [];
        foreach (GetAllCardIds() as $c) {
            if (CardType($c) === 'Base' && CardRarity($c) === 'Common') $bySet[CardSet($c)][] = $c;
        }
    }
    $out = [];
    foreach ($setsInPlay as $set) {
        foreach ($bySet[$set] ?? [] as $c) $out[] = $c;
        // A supplemental set's REPRINTED bases print under its own number: in play when printed Common there.
        if (SWUIsSupplementalBoosterSet($set)) {
            foreach (SWUReprintRaritiesIn($set) as $c => $r) if ($r === 'Common' && CardType($c) === 'Base' && !in_array($c, $out, true)) $out[] = $c;
        }
    }
    // Owner 2026-11-05: a lineup of supplemental packs alone (all IC27 — its bases are Starter Box Specials) would have no
    // base to build with: the latest main set's standard 30 HP common bases are in play instead.
    if ($out === [] && $setsInPlay !== [] && !array_filter($setsInPlay, 'SWUIsBoosterSet')) return SWULimitedCommonBasesInPlay([SWULatestBoosterSet()]);
    return $out;
}

// A Sealed pool: every card of every pack in $lineup (all 16 each, leader and base included — an Icons pack's 9), in
// pack order.
function SWULimitedOpenSealedPool(array $lineup, \Random\Randomizer $rng): array {
    $pool = [];
    foreach ($lineup as $set) foreach (SWULimitedOpenPack($set, $rng) as $e) $pool[] = $e['id'];
    return $pool;
}

// SPLASH bases (owner 2026-11-05): the LAW common bases' Epic Action plays a card "ignoring 1 of its Vigilance, Command,
// Aggression, or Cunning aspect penalties" — so with one in play a card ONE such aspect off is a playable splash.
function SWULimitedIsSplashBase(string $base): bool {
    return CardType($base) === 'Base' && stripos(strval(CardText($base) ?? ''), 'ignoring 1 of its Vigilance, Command, Aggression, or Cunning aspect penalties') !== false;
}
function SWULimitedSplashInPlay(array $setsInPlay): bool {
    foreach (SWULimitedCommonBasesInPlay($setsInPlay) as $b) if (SWULimitedIsSplashBase($b)) return true;
    return false;
}

// A card's worth to a bot, in RESOURCES: the bot's static curve value (SWUBotCurveValue surplus, the price it uses for a
// card in hand), or — for a card whose text it cannot price, and for leaders — its rarity (spec §3.7 [my call]).
const LT_BOT_RARITY_VALUE = ['Common' => 0.0, 'Uncommon' => 0.5, 'Rare' => 1.5, 'Legendary' => 2.5];
function SWULimitedCardValue(string $id): float {
    if (CardType($id) !== 'Leader') {
        $r = SWUBotCurveValue(0, $id, false);
        if ($r !== null) return round(floatval($r['surplus']), 2);
    }
    return LT_BOT_RARITY_VALUE[(string)CardRarity($id)] ?? 0.0;
}

// The facts a bot drafts by, for every card a Draft opened (stored in the blob so its pick stays pure).
function SWULimitedDraftFacts(array $ids): array {
    $out = [];
    foreach (array_unique($ids) as $id) {
        $out[$id] = ['t' => (string)CardType($id), 'c' => intval(CardCost($id)), 'v' => SWULimitedCardValue($id), 'r' => (string)CardRarity($id)];
    }
    return $out;
}

// Everything a Draft opens at start, for the live seats (in seat order): each seat's rolled lineup; its LEADER PACK (the
// leader of each of its packs); its CARD PACKS — every pack without its leader and its common base (spec §2.4: common
// bases go to the centre; a rare base in the rare slot is drafted like any card); every opened card's aspects (so the
// timeout auto-pick needs no card data later); and the seating, shuffled once.
function SWULimitedOpenDraft(array $settings, array $live, \Random\Randomizer $rng): array {
    $rolled = SWULimitedRollLineups($settings, count($live), $rng);
    $out = ['lineups' => [], 'leaderPacks' => [], 'cardPacks' => [], 'aspects' => [], 'order' => []];
    foreach (array_values($live) as $i => $pid) {
        $out['lineups'][$pid] = $rolled[$i + 1];
        foreach ($rolled[$i + 1] as $set) {
            $cards = [];
            foreach (SWULimitedOpenPack($set, $rng) as $e) {
                $out['aspects'][$e['id']] = array_values(array_filter(array_map('trim', explode(',', strval(CardAspect($e['id']) ?? ''))), 'strlen'));
                if ($e['slot'] === 'leader') $out['leaderPacks'][$pid][] = $e['id'];
                elseif ($e['slot'] !== 'base') $cards[] = $e['id'];
            }
            $out['cardPacks'][$pid][] = $cards;
        }
    }
    $live = array_values($live);
    foreach ($rng->shuffleArray(array_keys($live)) as $k) $out['order'][] = $live[$k];
    $out['facts'] = SWULimitedDraftFacts(array_keys($out['aspects']));   // for the bot seats (Plan 5)
    return $out;
}
