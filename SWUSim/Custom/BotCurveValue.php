<?php
// ── SWUSim bot: CURVE VALUE — a card in hand priced against its cost ─────────────────────────────────────────
// Spec: docs/superpowers/specs/2026-10-05-swusim-curve-value-design.md (owner-approved 2026-10-05).
//
//   SWUBotCurveValue($seat, $cardID, $board = true, $horizon)  -> ?array  value / budget / surplus / parts / allowances
//   SWUBotCurveSurplus($seat, $cardID, $horizon, $board = true) -> ?float  value − budget, in RESOURCES
//
// The owner's prices: 1 resource = 2 stats; a unit's budget is cost + 1, an upgrade's or event's is its cost.
// A card's text is parsed ONCE per CardID into "terms" — closures that price themselves statically (no board: the
// review report, later SWUDeck) or against the live board. A card whose text cannot be fully priced is UNPRICED: the
// functions return null and every decision that consults them behaves exactly as before this file existed.
// The designers' aspect and uniqueness ALLOWANCES are computed and reported, never subtracted (owner, 2026-10-05):
// a 3-aspect Marrok the bot can cast really is a 2/6 Sentinel for 3.

const SWU_CURVE_STATS_PER_RESOURCE = 2.0;
const SWU_CURVE_POWER_STATS    = 1.1;   // owner: power is scored a tiny bit more than HP
const SWU_CURVE_HP_STATS       = 0.9;
const SWU_CURVE_KEYWORD_STATS  = ['ambush' => 2.0, 'sentinel' => 2.0, 'shielded' => 2.0];
const SWU_CURVE_FREE_KEYWORDS  = ['hidden', 'saboteur', 'grit', 'overwhelm'];   // + Raid 1 / Restore 1
const SWU_CURVE_FREE_STATS     = 0.5;   // owner: two free keywords together ≈ 1 stat
const SWU_CURVE_SPACE_STATS    = 1.0;
const SWU_CURVE_READY_STATS    = 3.0;
const SWU_CURVE_SUPPORT_STATS  = 1.0;
const SWU_CURVE_STATIC_HORIZON = 6;     // midrange: the no-board ramp price ("ramp uses round 1", spec §3)
// Owner review 2026-10-06 (rules A–D):
const SWU_CURVE_WHEN_DEFEATED = 0.5;    // A: late, the opponent picks when, never fires if bounced or captured
const SWU_CURVE_CONUNDRUM_STEP = 0.1;   // B: a 5+ cost unit's LASTING value × max(0.5, 1 − 0.1 × (cost − 4))
// Resourcing (proposal 'curveresource', spec §4.2): keep-score points per resource of surplus. A TIEBREAKER — the aggro
// wing's keep is −cost, so 0.5 lets a card 2 resources under curve go before one 1 cost cheaper, and no further.
const SWU_BOT_CURVE_RESOURCE_KEEP = 0.5;

function _SWUBotCurveText(string $text): string {
    $t = preg_replace('/\([^)]*\)/', '', $text);                        // reminder text
    $t = str_replace(["\r", "\n", "\u{2019}"], [' ', ' ', "'"], $t);
    $t = preg_replace('/\s+:/', ':', preg_replace('/\s+/', ' ', $t));       // "attack (and survives):" leaves "attack :"
    return trim(strtolower($t));
}

// Strips the leading keyword run off $text ("ambush shielded grit when played: …") and returns what it found.
function _SWUBotCurveKeywords(string &$text): array {
    $kw = [];
    while (preg_match('/^(ambush|sentinel|shielded|hidden|saboteur|grit|overwhelm|support|raid (\d+)|restore (\d+))\b\s*/', $text, $m)) {
        if (($m[2] ?? '') !== '')     $kw['raid'] = intval($m[2]);
        elseif (($m[3] ?? '') !== '') $kw['restore'] = intval($m[3]);
        else                          $kw[$m[1]] = 1;
        $text = substr($text, strlen($m[0]));
    }
    return $kw;
}

// Keyword prices in STATS, labelled for the breakdown.
function _SWUBotCurveKeywordStats(array $kw): array {
    $parts = [];
    foreach (SWU_CURVE_KEYWORD_STATS as $k => $s) if (isset($kw[$k])) $parts[ucfirst($k)] = $s;
    if (isset($kw['support'])) $parts['Support'] = SWU_CURVE_SUPPORT_STATS;
    $free = 0;
    foreach (SWU_CURVE_FREE_KEYWORDS as $k) if (isset($kw[$k])) $free++;
    foreach (['raid' => 'Raid', 'restore' => 'Restore'] as $k => $label) {
        if (!isset($kw[$k])) continue;
        if ($kw[$k] <= 1) $free++; else $parts["$label {$kw[$k]}"] = $kw[$k] - 1.0;   // Raid N / Restore N = N − 1
    }
    if ($free > 0) $parts["free keywords x$free"] = SWU_CURVE_FREE_STATS * $free;
    return $parts;
}

function _SWUBotCurveBodyStats(int $power, int $hp): float {
    return SWU_CURVE_POWER_STATS * $power + SWU_CURVE_HP_STATS * $hp;
}

// Body, space and keyword terms for a unit. Consumes the keyword run from $text.
function _SWUBotCurveUnitBodyTerms(string $cid, string &$text): array {
    $p = intval(CardPower($cid)); $h = intval(CardHp($cid));
    $terms = [["body $p/$h", fn(array $c) => _SWUBotCurveBodyStats($p, $h) / SWU_CURVE_STATS_PER_RESOURCE, 'last']];
    if (strval(CardArena($cid)) === 'Space') $terms[] = ['space', fn(array $c) => SWU_CURVE_SPACE_STATS / SWU_CURVE_STATS_PER_RESOURCE, 'last'];
    foreach (_SWUBotCurveKeywordStats(_SWUBotCurveKeywords($text)) as $label => $stats) {
        // Ambush and Support act the turn the unit lands (immediate); the rest live on the body (lasting).
        $kind = in_array($label, ['Ambush', 'Support'], true) ? 'now' : 'last';
        $terms[] = [$label, fn(array $c) => $stats / SWU_CURVE_STATS_PER_RESOURCE, $kind];
    }
    return $terms;
}

// The designers' curve terms from the 284-unit fit (spec §2.5), in resources. REPORT ONLY.
function _SWUBotCurveAllowances(string $cid): array {
    $n = count(array_filter(explode(',', strval(CardAspect($cid)))));
    $asp = [0 => -1.05, 1 => -0.47, 2 => 0.14, 3 => 1.75][min(3, $n)];
    return ['aspects' => $asp / SWU_CURVE_STATS_PER_RESOURCE,
            'unique'  => (CardUnique($cid) ? 0.79 : -0.31) / SWU_CURVE_STATS_PER_RESOURCE];
}

function _SWUBotCurveParse(string $cid): ?array {
    static $cache = [];
    if (!array_key_exists($cid, $cache)) $cache[$cid] = _SWUBotCurveParseUncached($cid);
    return $cache[$cid];
}

// Hand-priced cards (spec §2.6): text that spans sentences in a way the sentence parser can't read.
const SWU_CURVE_OVERRIDES = [
    'SOR_041' => 'opponent-chooses',       // Power of the Dark Side: "An opponent chooses a unit they control. Defeat that unit."
    'TWI_238' => 'each-player-chooses',    // Merciless Contest: each player defeats a non-leader unit they choose
    'JTL_221' => 'replayable-by-opponent', // Stolen AT-Hauler: When Defeated, the opponent may replay it free (drawback ≈ −2 stats)
];

function _SWUBotCurveParseUncached(string $cid): ?array {
    $type = strval(CardType($cid));
    if (!in_array($type, ['Unit', 'Event', 'Upgrade'], true)) return null;   // leaders, bases, tokens: out of scope (spec §7)
    $text = _SWUBotCurveText(strval(CardText($cid)));
    $state = ['discount' => null, 'lastIndirect' => 0, 'cid' => $cid, 'pending' => null];
    $terms = [];
    // Piloting (owner 2026-10-06): price the UNIT mode + 0.5 for the choice; the pilot text is not parsed.
    $piloting = (bool)preg_match('/\s*piloting \[[^\]]*\].*$/s', $text);
    if ($piloting) $text = trim(preg_replace('/\s*piloting \[[^\]]*\].*$/s', '', $text));
    // Plot (owner 2026-10-06): +1 — "gives flexibility on deploy". It is printed last.
    $plot = (bool)preg_match('/(?:^|\s)plot\.?$/', $text);
    if ($plot) $text = trim(preg_replace('/(?:^|\s)plot\.?$/', '', $text));
    if ($piloting) $terms[] = ['piloting choice', fn(array $c) => 0.5, 'now'];
    if ($plot)     $terms[] = ['plot', fn(array $c) => 1.0, 'now'];
    if ($type === 'Unit') {
        array_push($terms, ...(_SWUBotCurveUnitBodyTerms($cid, $text)));
    } elseif ($type === 'Upgrade') {
        $p = intval(CardUpgradePower($cid)); $h = intval(CardUpgradeHp($cid));
        $terms[] = ["+$p/+$h", fn(array $c) => _SWUBotCurveBodyStats($p, $h) / SWU_CURVE_STATS_PER_RESOURCE];
        $text = trim(preg_replace('/^attach to an? [^.]*\.\s*/', '', $text));
    }
    if (isset(SWU_CURVE_OVERRIDES[$cid])) {
        array_push($terms, ...(_SWUBotCurveOverrideTerms(SWU_CURVE_OVERRIDES[$cid])));
        return ['type' => $type, 'terms' => $terms, 'discount' => null];
    }
    if ($type === 'Event') {
        $sub = _SWUBotCurveBody($text, $state);
        if ($sub === null) return null;
        array_push($terms, ...$sub);
    } else {
        // (?<!\/) — never split INSIDE "when played/when defeated:" at its "when defeated:" half.
        $trig = 'when played\/when defeated|when played\/on attack|on attack\/when defeated|when played\/when this unit completes an attack'
              . '|when played|when defeated|on attack|when this unit is attacked|when attack ends|when this unit completes an attack'
              . '|when 1 or more upgrades attach to this unit';
        $chunks = preg_split('/(?<!\/)(?=\b(?:' . $trig . '|when an enemy unit is defeated):)/', $text, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '' || $chunk === '.') continue;
            if (preg_match('/^(' . $trig . '):\s*(.*)$/s', $chunk, $m)) {
                $sub = _SWUBotCurveBody($m[2], $state);
                if ($sub === null) return null;
                $wd = fn(callable $fn) => function (array $c) use ($fn): ?float { $r = $fn($c); return $r === null ? null : SWU_CURVE_WHEN_DEFEATED * $r; };
                // "when played/on attack" etc. — the same ability once per named trigger. When Played is immediate;
                // When Defeated is lasting at 50% (rule A); On Attack is lasting. A term may force its own kind.
                foreach (explode('/', $m[1]) as $tr) {
                    foreach ($sub as $t) {
                        if ($tr === 'when defeated') $terms[] = ["when defeated (50%): {$t[0]}", $wd($t[1]), 'last'];
                        elseif ($tr === 'when 1 or more upgrades attach to this unit') $terms[] = ["when upgraded (50%, derived): {$t[0]}", $wd($t[1]), 'last'];
                        else                         $terms[] = ["$tr: {$t[0]}", $t[1], $t[2] ?? ($tr === 'when played' ? 'now' : 'last')];
                    }
                }
            } else {
                $sub = _SWUBotCurveConstants($chunk, $cid);
                if ($sub === null) return null;
                array_push($terms, ...$sub);
            }
        }
    }
    return ['type' => $type, 'terms' => $terms, 'discount' => $state['discount']];
}

// $route (owner, 2026-10-06): 'paid' (default) charges the real cost; 'waived' — the route waives the aspect penalty
// (Daimyo's Palace Epic, the LAW_020 waiver prompt), so the printed cost; 'free' — played for free (a search that
// plays its picks), so the budget is 0 and the surplus is the card's whole value.
function SWUBotCurveValue(int $seat, string $cardID, bool $board = true, int $horizon = SWU_CURVE_STATIC_HORIZON, string $route = 'paid'): ?array {
    $p = _SWUBotCurveParse($cardID);
    if ($p === null) return null;
    $ctx = ['seat' => ($board && $seat > 0) ? $seat : null, 'h' => $horizon, 'cid' => $cardID];
    $parts = []; $value = 0.0; $lasting = 0.0;
    foreach ($p['terms'] as $t) {
        [$label, $fn] = $t;
        $r = $fn($ctx);
        if ($r === null) return null;                        // a board-only term with no board: unpriced in this mode
        $parts[$label] = ($parts[$label] ?? 0.0) + $r;
        $value += $r;
        if (($t[2] ?? 'now') === 'last') $lasting += $r;
    }
    // Rule B — the 5-cost conundrum (owner, 2026-10-06): one removal spell wastes a big unit's whole turn, so a 5+ cost
    // unit's LASTING value is discounted; what it does the turn it lands (When Played, Ambush, enters ready) is not.
    $printed = intval(CardCost($cardID));
    if ($p['type'] === 'Unit' && $printed >= 5 && $lasting != 0.0) {
        $f = max(0.5, 1.0 - SWU_CURVE_CONUNDRUM_STEP * ($printed - 4));
        $parts["conundrum ×$f on lasting value"] = ($f - 1.0) * $lasting;
        $value += ($f - 1.0) * $lasting;
    }
    // The COST ACTUALLY PAID (spec §3, item 2): with a board, printed + aspect penalty − an active discount.
    $cost = intval(CardCost($cardID));
    if ($ctx['seat'] !== null) {
        if ($route !== 'waived') $cost += intval(SWUAspectPenalty($ctx['seat'], $cardID));
        if ($p['discount'] !== null && _SWUBotCurveCondition($ctx['seat'], $p['discount'][0]) === true) $cost -= $p['discount'][1];
    }
    $cost = max(0, $cost);
    $budget = $p['type'] === 'Unit' ? $cost + 1.0 : (float)$cost;
    if ($route === 'free') $budget = 0.0;
    // A unique already in play: this copy is worth only what resourcing it gives — 0 surplus as a play (spec §3, item 4).
    if ($ctx['seat'] !== null && $p['type'] === 'Unit' && CardUnique($cardID) && _SWUBotCurveUniqueInPlay($ctx['seat'], $cardID)) {
        $parts = ['duplicate unique in play' => $budget];
        $value = $budget;
    }
    return ['value' => $value, 'budget' => $budget, 'surplus' => $value - $budget, 'parts' => $parts,
            'allowances' => _SWUBotCurveAllowances($cardID)];
}

function SWUBotCurveSurplus(int $seat, string $cardID, int $horizon = SWU_CURVE_STATIC_HORIZON, bool $board = true, string $route = 'paid'): ?float {
    $r = SWUBotCurveValue($seat, $cardID, $board, $horizon, $route);
    return $r === null ? null : $r['surplus'];
}

// ── Damage, board reads ─────────────────────────────────────────────────────────────────────────────────────────
// N damage to a unit = N − 1 resources for N ≥ 2, 0.5 for 1 — the same scaling as Raid N (owner, 2026-10-05).
function _SWUBotCurveUnitDamage(int $n): float {
    return $n <= 0 ? 0.0 : ($n === 1 ? 0.5 : $n - 1.0);
}

function _SWUBotCurveEnemyViews(int $seat): array {
    $out = [];
    foreach (SWUBotOpponents($seat) as $o) foreach (SWUBotUnits($o) as $v) $out[] = $v;
    return $out;
}

function _SWUBotCurveIsVehicle(string $cid): bool {
    return _SWUBotCurveHasTrait($cid, 'vehicle');
}

function _SWUBotCurveHasTrait(string $cid, string $trait): bool {
    $traits = array_map(fn($t) => strtolower(trim($t)), explode(',', strval(CardTrait($cid))));
    return in_array(strtolower(trim($trait)), $traits, true);
}

// "non-leader ground " → ['non-leader' => true, 'ground' => true]
function _SWUBotCurveWords(string $s): array {
    $f = [];
    foreach (preg_split('/\s+/', trim($s)) as $w) if ($w !== '') $f[$w] = true;
    return $f;
}

// Enemy units passing the filter. Keys: the card-text words (non-leader, ground, space, vehicle, non-vehicle, damaged)
// plus nonUnique / hpMax (remaining HP) / powMax / powMin / costMax.
function _SWUBotCurveTargets(int $seat, array $f): array {
    $out = [];
    foreach (_SWUBotCurveEnemyViews($seat) as $v) {
        $cid = $v['cardID'];
        if (!empty($f['non-leader']) && $v['isLeader']) continue;
        if (!empty($f['ground']) && $v['arena'] !== 'Ground') continue;
        if (!empty($f['space']) && $v['arena'] !== 'Space') continue;
        if (!empty($f['vehicle']) && !_SWUBotCurveIsVehicle($cid)) continue;
        if (!empty($f['non-vehicle']) && _SWUBotCurveIsVehicle($cid)) continue;
        if (!empty($f['damaged']) && $v['remaining'] >= $v['hp']) continue;
        if (!empty($f['nonUnique']) && CardUnique($cid)) continue;
        if (isset($f['hpMax']) && $v['remaining'] > $f['hpMax']) continue;
        if (isset($f['powMax']) && $v['power'] > $f['powMax']) continue;
        if (isset($f['powMin']) && $v['power'] < $f['powMin']) continue;
        if (isset($f['costMax']) && ($v['isLeader'] || $v['cost'] > $f['costMax'])) continue;
        $out[] = $v;
    }
    return $out;
}

// What removing this unit is worth: a leader = the any-unit anchor; else its own curve value (static), or its budget.
function _SWUBotCurveWorth(array $v): float {
    if ($v['isLeader']) return 6.0;
    $r = SWUBotCurveValue(0, $v['cardID'], false);
    if ($r === null) return intval(CardCost($v['cardID'])) + 1.0;
    // UNdiscounted: the conundrum (rule B) is the OWNER's removal risk; to the remover a big unit is a big kill.
    foreach ($r['parts'] as $k => $x) if (str_starts_with($k, 'conundrum')) return $r['value'] - $x;
    return $r['value'];
}

// Damage capped at what the best legal target can absorb (spec §3, item 3).
function _SWUBotCurveDamageToUnit(array $ctx, int $n, array $f): float {
    if ($ctx['seat'] === null) return _SWUBotCurveUnitDamage($n);
    $t = _SWUBotCurveTargets($ctx['seat'], $f);
    return $t ? _SWUBotCurveUnitDamage(min($n, max(array_column($t, 'remaining')))) : 0.0;
}

// A defeat effect: the best legal target's worth, capped at the anchor; with no board, priced by the restriction.
function _SWUBotCurveDefeat(array $ctx, array $f): float {
    $anchor = (!empty($f['non-leader']) || isset($f['powMin']) || isset($f['costMax'])) ? 5.0 : 6.0;
    if ($ctx['seat'] === null) {
        // Owner rule C (2026-10-06): a RESTRICTED kill is never worth Vanquish's unrestricted 5 — capped one below its
        // anchor; +1 if it can still hit a softened leader (Takedown); −1 if it reaches only one arena.
        $restricted = isset($f['hpMax']) || isset($f['powMax']) || isset($f['costMax']) || isset($f['powMin'])
                   || !empty($f['vehicle']) || !empty($f['ground']) || !empty($f['space']);
        if (!$restricted) return $anchor;
        $base = isset($f['hpMax']) ? _SWUBotCurveUnitDamage($f['hpMax'])
              : (isset($f['powMax']) ? (float)$f['powMax'] : (isset($f['costMax']) ? $f['costMax'] + 1.0 : $anchor));
        $leaders = $anchor === 6.0;                                                  // no non-leader / cost / power-floor clause
        $v = min($anchor - 1.0, $base + ($leaders ? 1.0 : 0.0));
        if (!empty($f['ground']) || !empty($f['space'])) $v -= 1.0;
        return max(0.0, $v);
    }
    $best = 0.0;
    foreach (_SWUBotCurveTargets($ctx['seat'], $f) as $v) $best = max($best, min($anchor, _SWUBotCurveWorth($v)));
    return $best;
}

// Indirect damage: the opponent splits it to hurt least — each unit soaks up to remaining − 1 at 0.25, the rest hits
// the base at 0.5 (owner, 2026-10-05). No board: all base damage.
function _SWUBotCurveIndirect(array $ctx, int $n): float {
    if ($ctx['seat'] === null) return $n / 2.0;
    $best = 0.0;
    foreach (SWUBotOpponents($ctx['seat']) as $o) {
        $soak = 0;
        foreach (SWUBotUnits($o) as $v) $soak += max(0, $v['remaining'] - 1);
        $best = max($best, 0.25 * min($n, $soak) + 0.5 * max(0, $n - $soak));
    }
    return $best;
}

// Token units by title (lowercase) → value in resources, priced like a unit body. null = unknown token or token text.
function _SWUBotCurveTokenValue(string $name): ?float {
    static $idx = null;
    if ($idx === null) {
        $idx = [];
        preg_match_all("/'([A-Z]{3}_T\d{2})' =>/", (string)@file_get_contents(__DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php'), $m);
        foreach (array_unique($m[1]) as $id) {
            if (strval(CardType($id)) === 'Token Unit') $idx[strtolower(strval(CardTitle($id)))] ??= $id;
        }
    }
    $id = $idx[strtolower(trim($name))] ?? '';
    if ($id === '') return null;
    $t = _SWUBotCurveText(strval(CardText($id)));
    $stats = _SWUBotCurveBodyStats(intval(CardPower($id)), intval(CardHp($id)))
           + (strval(CardArena($id)) === 'Space' ? SWU_CURVE_SPACE_STATS : 0.0)
           + array_sum(_SWUBotCurveKeywordStats(_SWUBotCurveKeywords($t)));
    if (trim($t, ' .') !== '') return null;
    return $stats / SWU_CURVE_STATS_PER_RESOURCE;
}

// ── Conditions (spec §3, item 1) ────────────────────────────────────────────────────────────────────────────────
// null = a condition this file can't read (the card is then unpriced); false whenever there is no board.
function _SWUBotCurveCondition(?int $seat, string $cond): ?bool {
    // Final review (2026-10-06): an UNREADABLE condition returns null (the card is then unpriced), never a silent false.
    // Checked in this order: the specific wordings, then "an X or Y unit", then "a/another <word> unit", then a title.
    $c = trim($cond); $m = [];
    $kind = null;
    if ($c === 'you control more space units than an opponent') $kind = 'space-majority';
    elseif ($c === 'you control fewer resources than an opponent') $kind = 'fewer-resources';
    elseif ($c === 'you have the initiative') $kind = 'initiative';
    elseif ($c === 'a friendly unit was defeated this phase') $kind = 'friendly-defeated';
    elseif (preg_match('/^there is an? (\w+) card in your discard pile$/', $c, $m)) $kind = 'discard-aspect';
    elseif ($c === 'the force is with you') $kind = 'force';
    elseif (preg_match('/^disclose ([a-z]+)(?: excluding ([A-Z0-9_]+))?$/', $c, $m)) $kind = 'disclose';
    elseif ($c === 'this unit is upgraded') $kind = 'upgraded';
    elseif (preg_match('/^you control an? ([\w-]+) base$/', $c, $m)) $kind = 'base-trait';
    elseif (preg_match('/^you control an? (\w+) or (\w+) unit$/', $c, $m)) $kind = 'either-aspect';
    elseif (preg_match('/^you control (?:another|an?) ([\w -]+?) unit$/', $c, $m)) $kind = 'word-unit';   // "a Capital Ship unit"
    elseif (preg_match('/^you control ([a-z][\w\' -]*)$/', $c, $m) && _SWUBotCurveIsTitle($m[1])) $kind = 'title';
    if ($kind === null) return null;
    if ($seat === null || $seat <= 0) return false;
    $aspects = ['vigilance', 'command', 'aggression', 'cunning', 'heroism', 'villainy'];
    $hasAspect = fn(string $cid, string $a) => in_array($a, array_map('trim', explode(',', strtolower(strval(CardAspect($cid))))), true);
    switch ($kind) {
        case 'space-majority':
            $mine = count(array_filter(SWUBotUnits($seat), fn($v) => $v['arena'] === 'Space'));
            foreach (SWUBotOpponents($seat) as $o) if ($mine > count(array_filter(SWUBotUnits($o), fn($v) => $v['arena'] === 'Space'))) return true;
            return false;
        case 'fewer-resources':
            foreach (SWUBotOpponents($seat) as $o) if (SWUResourceCount($seat) < SWUResourceCount($o)) return true;
            return false;
        case 'initiative':        return (bool)HasInitiative($seat);
        case 'friendly-defeated': return SWUTeamFlagCount($seat, 'SWU_FRIENDLY_DEFEATED') > 0;
        case 'discard-aspect':
            foreach (GetDiscard($seat) as $o) {
                if ($o !== null && empty($o->removed) && stripos(strval(CardAspect(strval($o->CardID ?? ''))), $m[1]) !== false) return true;
            }
            return false;
        case 'force':             return (bool)PlayerHasTheForce($seat);
        case 'disclose':                                                          // the hand's aspect icons cover the reveal
            preg_match_all('/vigilance|command|aggression|cunning|heroism|villainy/', $m[1], $need);
            $have = []; $skip = $m[2] ?? '';
            foreach (GetHand($seat) as $o) {
                if ($o === null || !empty($o->removed)) continue;
                $id = strval($o->CardID ?? '');
                if ($skip !== '' && $id === $skip) { $skip = ''; continue; }             // the card being priced is not revealed
                foreach (array_filter(explode(',', strtolower(strval(CardAspect($id))))) as $a) $have[$a] = ($have[$a] ?? 0) + 1;
            }
            foreach (array_count_values($need[0]) as $a => $n) if (($have[$a] ?? 0) < $n) return false;
            return true;
        case 'upgraded':          return false;                                   // a card being priced is never upgraded yet
        case 'base-trait':
            $b = GetBase($seat)[0] ?? null;
            return $b !== null && _SWUBotCurveHasTrait(strval($b->CardID ?? ''), $m[1]);
        case 'either-aspect':
            if (!in_array($m[1], $aspects, true) || !in_array($m[2], $aspects, true)) return null;
            foreach (SWUBotUnits($seat) as $v) if ($hasAspect($v['cardID'], $m[1]) || $hasAspect($v['cardID'], $m[2])) return true;
            return false;
        case 'word-unit':
            $w = $m[1];
            foreach (SWUBotUnits($seat) as $v) {
                if ($w === 'leader' ? $v['isLeader']
                    : ($w === 'token' ? strval(CardType($v['cardID'])) === 'Token Unit'
                    : (in_array($w, $aspects, true) ? $hasAspect($v['cardID'], $w)
                    : ($w === 'ground' || $w === 'space' ? strtolower($v['arena']) === $w
                    : ($w === 'damaged' ? $v['remaining'] < $v['hp']
                    : _SWUBotCurveHasTrait($v['cardID'], $w)))))) return true;
            }
            return false;
        case 'title':
            foreach (SWUBotUnits($seat) as $v) if (strtolower(strval(CardTitle($v['cardID']))) === $m[1]) return true;
            foreach (GetLeader($seat) as $l) if ($l !== null && strtolower(strval(CardTitle(strval($l->CardID ?? '')))) === $m[1]) return true;
            return false;
    }
    return null;
}

// Is $name (lowercase) the title of some card? Built once from the card dictionary.
function _SWUBotCurveIsTitle(string $name): bool {
    static $titles = null;
    if ($titles === null) {
        $titles = [];
        preg_match_all("/'([A-Z]{3}_\d{3})' =>/", (string)@file_get_contents(__DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php'), $mm);
        foreach (array_unique($mm[1]) as $id) $titles[strtolower(strval(CardTitle($id)))] = true;
    }
    return isset($titles[strtolower(trim($name))]);
}

// ── Effect patterns: [regex anchored at ^, builder(matches, &state) → list of [label, fn] | null] ─────────────────
function _SWUBotCurveEffectPatterns(): array {
    static $P = null;
    if ($P !== null) return $P;
    $num = fn(string $s): int => ctype_digit($s) ? intval($s) : 1;   // "a" / "an" = 1
    $one = fn(string $label, callable $fn) => [[$label, $fn]];
    $P = [
        ['/^draw (a|an|\d+) cards?/', fn(array $m, array &$st) => $one("draw {$m[1]}", fn($c) => (float)$num($m[1]))],
        ['/^deal (\d+) damage to an? ((?:enemy |ground |space |non-leader |non-vehicle )*)unit or base/',
            fn(array $m, array &$st) => $one("{$m[1]} damage to a unit or base",
                fn($c) => max(_SWUBotCurveDamageToUnit($c, intval($m[1]), _SWUBotCurveWords($m[2])), intval($m[1]) / 2.0))],
        ['/^deal (\d+) damage to (?:an?|another|that) ((?:enemy |ground |space |non-leader |non-vehicle |damaged )*)unit(?: that opponent controls| in the same arena)?/',
            fn(array $m, array &$st) => $one("{$m[1]} damage to a unit", fn($c) => _SWUBotCurveDamageToUnit($c, intval($m[1]), _SWUBotCurveWords($m[2])))],
        ['/^deal (\d+) damage to (?:a|an enemy|each enemy|its controller\'s|that player\'s|the defending player\'s) base/',
            fn(array $m, array &$st) => $one("{$m[1]} damage to a base", fn($c) => intval($m[1]) / 2.0)],
        ['/^deal (\d+) indirect damage to (?:a player|an opponent|each opponent|the defending player)/', function (array $m, array &$st) use ($one) {
            $n = intval($m[1]); $st['lastIndirect'] = $n;
            return $one("$n indirect damage", fn($c) => _SWUBotCurveIndirect($c, $n));
        }],
        ['/^deal (\d+) indirect damage instead/', function (array $m, array &$st) use ($one) {
            $n = intval($m[1]); $prev = intval($st['lastIndirect']);
            return $one("$n indirect instead of $prev", fn($c) => _SWUBotCurveIndirect($c, $n) - _SWUBotCurveIndirect($c, $prev));
        }],
        ['/^exhaust up to (\d+) units/', fn(array $m, array &$st) => $one("exhaust up to {$m[1]}",
            fn($c) => $c['seat'] === null ? (float)intval($m[1]) : (float)min(intval($m[1]), count(_SWUBotCurveEnemyViews($c['seat']))))],
        ['/^exhaust (?:an? (?:enemy |ground |space )*unit(?: in attached unit\'s arena)?|it)/', fn(array $m, array &$st) => $one('exhaust',
            fn($c) => ($c['seat'] === null || _SWUBotCurveEnemyViews($c['seat'])) ? 1.0 : 0.0)],
        ['/^that unit can\'t ready this round/', fn(array $m, array &$st) => $one("can't ready this round", fn($c) => 1.0)],
        ['/^heal (\d+) damage from (?:an? (?:friendly )?(?:unit|base)|your base)/', fn(array $m, array &$st) => $one("heal {$m[1]}", fn($c) => intval($m[1]) / 3.0)],
        ['/^look at its controller\'s hand and discard a card from it that shares an aspect with that unit/',
            fn(array $m, array &$st) => $one('restricted discard (derived)', fn($c) => 1.5)],
        ['/^look at an opponent\'s hand and discard a card from it/', fn(array $m, array &$st) => $one('targeted discard', fn($c) => 2.0)],
        ['/^look at an opponent\'s hand/', fn(array $m, array &$st) => $one('look at hand', fn($c) => 0.0)],
        ['/^discard a card from it/', fn(array $m, array &$st) => $one('targeted discard', fn($c) => 2.0)],
        ['/^an opponent discards a random card from their hand/', fn(array $m, array &$st) => $one('random discard (derived)', fn($c) => 1.0)],
        ['/^(?:they|that player|that unit\'s controller) draws? a card/', fn(array $m, array &$st) => $one('opponent draws', fn($c) => -1.0)],
        ['/^attack with (?:an?|another) (?:[\w-]+ )*?unit/', fn(array $m, array &$st) => $one('attack', fn($c) => 0.5)],
        ['/^(?:it|this unit|that [\w ]+? unit) gets \+(\d+)\/\+0 for this attack/', fn(array $m, array &$st) => $one("+{$m[1]} on the attack", fn($c) => 0.5 * intval($m[1]))],
        ['/^for this attack, it gets \+1\/\+0 for each card in your hand/', fn(array $m, array &$st) => $one('+1 per card in hand',
            fn($c) => 0.5 * ($c['seat'] === null ? 4 : max(0, count(GetHand($c['seat'])) - 1)))],
        ['/^it gains (?:saboteur|overwhelm|grit|raid 1) for this attack/', fn(array $m, array &$st) => $one('free keyword for the attack', fn($c) => 0.25)],
        ['/^give (?:an?|another) (?:friendly )?unit \+(\d+)\/\+(\d+) for this phase/', fn(array $m, array &$st) => $one("+{$m[1]}/+{$m[2]} for the phase",
            fn($c) => 0.5 * intval($m[1]) + 0.25 * intval($m[2]))],
        ['/^give a shield token to an? (?:friendly )?unit/', fn(array $m, array &$st) => $one('shield token', fn($c) => 1.0)],
        ['/^give (a|an|\d+) experience tokens? to (?:an? (?:friendly )?unit|this unit|it|another (?:[\w-]+ (?:or [\w-]+ )?)?unit)/', fn(array $m, array &$st) => $one("{$m[1]} experience", fn($c) => (float)$num($m[1]))],
        ['/^give a weakness token to (?:it|an? (?:enemy )?unit)/', fn(array $m, array &$st) => $one('weakness token (derived)', fn($c) => 1.0)],
        ['/^create (a|an|\d+) ([\w -]+?) tokens?(?! for each)/', function (array $m, array &$st) use ($one, $num) {
            $v = _SWUBotCurveTokenValue($m[2]);
            if ($v === null) return null;
            $n = $num($m[1]);
            return $one("create $n {$m[2]} token", fn($c) => $n * $v);
        }],
        ['/^take control of a non-leader unit, then defeat it/', fn(array $m, array &$st) => $one('take control, defeat',
            fn($c) => _SWUBotCurveDefeat($c, ['non-leader' => true]))],
        ['/^defeat all (?:(ground|space) )?units/', function (array $m, array &$st) use ($one) {
            $arena = ucfirst($m[1] ?? '');                                          // '' = both arenas
            return $one(trim($m[0]), function ($c) use ($arena): ?float {
                if ($c['seat'] === null) return null;                                // board-only
                $in = fn($v) => $arena === '' || $v['arena'] === $arena;
                $v = 0.0;
                foreach (_SWUBotCurveEnemyViews($c['seat']) as $e) if ($in($e)) $v += _SWUBotCurveWorth($e);
                foreach (SWUBotUnits($c['seat']) as $mine) if ($in($mine)) $v -= _SWUBotCurveWorth($mine);
                return $v;
            });
        }],
        ['/^for each enemy unit defeated this way, deal (\d+) damage to its controller\'s base/', fn(array $m, array &$st) => $one('burn per enemy unit',
            fn($c) => $c['seat'] === null ? null : intval($m[1]) * count(_SWUBotCurveEnemyViews($c['seat'])) / 2.0)],
        ['/^defeat an? ((?:non-leader |enemy |ground |space |vehicle |non-vehicle )*)unit(?: with (\d+) or (less|more) (remaining hp|power))?(?: that costs (\d+) or less)?/',
            function (array $m, array &$st) use ($one) {
                $f = _SWUBotCurveWords($m[1] ?? '');
                $n = intval($m[2] ?? 0); $dir = $m[3] ?? ''; $what = $m[4] ?? '';
                if ($what === 'remaining hp' && $dir === 'less') $f['hpMax'] = $n;
                if ($what === 'power' && $dir === 'less') $f['powMax'] = $n;
                if ($what === 'power' && $dir === 'more') $f['powMin'] = $n;
                if (($m[5] ?? '') !== '') $f['costMax'] = intval($m[5]);
                return $one(trim($m[0]), fn($c) => _SWUBotCurveDefeat($c, $f));
            }],
        ['/^return an? ((?:non-leader |enemy )*)unit(?: with (\d+) or less power)? to its owner\'s hand/', function (array $m, array &$st) use ($one) {
            $f = _SWUBotCurveWords($m[1] ?? ''); $f['non-leader'] = true;           // a leader can't be returned to hand
            if (($m[2] ?? '') !== '') $f['powMax'] = intval($m[2]);
            return $one('bounce', function ($c) use ($f): float {                 // the target's cost, in lost tempo
                if ($c['seat'] === null) return (float)min(4, $f['powMax'] ?? $f['costMax'] ?? 3);   // static: a typical 4-cost target (derived)
                $best = 0;
                foreach (_SWUBotCurveTargets($c['seat'], $f) as $v) $best = max($best, $v['cost']);
                return (float)$best;
            });
        }],
        ['/^(?:put this event into play as a resource|resource this unit from its owner\'s discard pile|resource this card)/', fn(array $m, array &$st) => $one('ramp',
            function ($c): float {                                                // 0.5 × the rounds left in the horizon
                $round = $c['seat'] === null ? 1 : max(1, intval(GetTurnNumber()));
                $h = $c['seat'] === null ? SWU_CURVE_STATIC_HORIZON : intval($c['h']);
                return 0.5 * max(0, $h + 1 - $round);
            })],
        ['/^return an? (?:non-<uq> )?(?:[\w-]+ )*?unit from your discard pile to your hand/', fn(array $m, array &$st) => $one('return from discard (derived)', fn($c) => 1.0)],
        ['/^a friendly (non-vehicle )?unit deals damage equal to its (remaining hp|power) to an? (non-unique )?enemy unit/',
            function (array $m, array &$st) use ($one) {
                $nonVeh = ($m[1] ?? '') !== ''; $stat = $m[2]; $f = ($m[3] ?? '') !== '' ? ['nonUnique' => true] : [];
                return $one("friendly unit hits for its $stat", function ($c) use ($nonVeh, $stat, $f): float {
                    if ($c['seat'] === null) return _SWUBotCurveUnitDamage(4);
                    $hit = 0;
                    foreach (SWUBotUnits($c['seat']) as $v) {
                        if ($nonVeh && _SWUBotCurveIsVehicle($v['cardID'])) continue;
                        $hit = max($hit, $stat === 'power' ? $v['power'] : $v['remaining']);
                    }
                    return _SWUBotCurveDamageToUnit($c, $hit, $f);
                });
            }],
        // ── Owner category prices (2026-10-06) ──────────────────────────────────────────────────────────────────
        ['/^use the force/', function (array $m, array &$st) use ($one) {             // −1, only when you can (then the effect follows)
            $st['pending'] = 'the force is with you';
            return $one('use the Force (−1)', fn($c) => _SWUBotCurveCondition($c['seat'], 'the force is with you') ? -1.0 : 0.0);
        }],
        ['/^the force is with you/', fn(array $m, array &$st) => $one('gain the Force', fn($c) => 1.0)],
        ['/^disclose ([a-z]+)/', function (array $m, array &$st) use ($one) {          // the reveal is a downside (−0.5, derived)
            $cond = "disclose {$m[1]} excluding {$st['cid']}";
            $st['pending'] = $cond;
            return $one("disclose {$m[1]} (−0.5, derived)", fn($c) => _SWUBotCurveCondition($c['seat'], $cond) ? -0.5 : 0.0);
        }],
        ['/^search the top \d+ cards of your deck for an? (?:[\w-]+ )*?unit, reveal it, and draw it/', fn(array $m, array &$st) => $one('search and draw', fn($c) => 1.0)],
        ['/^search the top \d+ cards of your deck for an? (?:[\w-]+ )*?unit, reveal it, and play it/', fn(array $m, array &$st) => $one('search and play', fn($c) => 1.0)],
        ['/^it costs (\d+) resources? less/', fn(array $m, array &$st) => $one("{$m[1]} less", fn($c) => (float)intval($m[1]))],
        ['/^the next unit you play this phase (with [^,]+? )?(?:costs \d+ resources? less|enters play ready)/', fn(array $m, array &$st) =>
            $one(($m[1] ?? '') !== '' ? 'restricted next-unit reducer (1, derived)' : 'next-unit reducer', fn($c) => ($m[1] ?? '') !== '' ? 1.0 : 2.0)],
        ['/^the first (?:([\w -]+?) )?unit you play each (?:round|phase)( that has a "[^"]*" ability)? costs \d+ resources? less/', function (array $m, array &$st) use ($one) {
            $restricted = ($m[1] ?? '') !== '' || ($m[2] ?? '') !== '';
            return [[$restricted ? 'restricted reducer (1, derived)' : 'reducer', fn($c) => $restricted ? 1.0 : 2.0, 'last']];
        }],
        ['/^ready it/', fn(array $m, array &$st) => $one('ready it (derived)', fn($c) => 0.5)],
        ['/^give (a|an|\d+) advantage tokens? to (?:an? (?:friendly )?unit|another unit|it|this unit|that unit)/', fn(array $m, array &$st) => $one("{$m[1]} advantage", fn($c) => 0.5 * $num($m[1]))],
        ['/^if this unit wasn\'t defeated by combat damage, (?:you may )?give \d+ advantage tokens to that unit instead/', fn(array $m, array &$st) => $one('more advantage if not combat (derived 0)', fn($c) => 0.0)],
        ['/^if that unit is defeated this way, give an advantage token to a unit/', fn(array $m, array &$st) => $one('advantage if it kills (derived)', fn($c) => 0.25)],
        ['/^(?:each )?(?:other )?friendly (?:[\w-]+ )*?units? gains? (.+?)(?: for this phase)?$/', function (array $m, array &$st) use ($one) {
            $kwText = str_replace(' and ', ' ', $m[1]);
            $kw = _SWUBotCurveKeywords($kwText);
            if ($kw === [] || trim($kwText, ' .') !== '') return null;
            $stats = array_sum(_SWUBotCurveKeywordStats($kw));
            return [['aura × 2 units', fn($c) => 2 * $stats / SWU_CURVE_STATS_PER_RESOURCE, 'last']];
        }],
        ['/^choose another friendly unit/', fn(array $m, array &$st) => $one('choose a unit', fn($c) => 0.0)],
        ['/^while this unit is in play, the chosen unit gets \+(\d+)\/\+(\d+)(?: and gains (saboteur|overwhelm|grit|hidden|sentinel|ambush|shielded))?/', function (array $m, array &$st) use ($one) {
            $stats = _SWUBotCurveBodyStats(intval($m[1]), intval($m[2])) + array_sum(_SWUBotCurveKeywordStats(($m[3] ?? '') !== '' ? [$m[3] => 1] : []));
            return $one('chosen unit buff', fn($c) => $stats / SWU_CURVE_STATS_PER_RESOURCE);
        }],
        ['/^if damage would be dealt to (?:this unit|another friendly unit), you may defeat [^.]+/', fn(array $m, array &$st) => [['bodyguard', fn($c) => 2.0, 'last']]],
        ['/^prevent that damage/', fn(array $m, array &$st) => $one('prevent', fn($c) => 0.0)],
        ['/^this unit can\'t be defeated by enemy card abilities/', fn(array $m, array &$st) => [['immune to enemy defeat (2, derived)', fn($c) => 2.0, 'last']]],
        ['/^opponents can\'t take control of this unit/', fn(array $m, array &$st) => [['immune to steal (derived)', fn($c) => 0.5, 'last']]],
        ['/^this unit can\'t be defeated or returned to hand by enemy card abilities/', fn(array $m, array &$st) => [['immune to enemy defeat/bounce (2, derived)', fn($c) => 2.0, 'last']]],
        ['/^name a card/', fn(array $m, array &$st) => $one('name a card', fn($c) => 1.5)],
        ['/^while this unit is in play, (?:opponents can\'t play cards with that name|each non-leader card an opponent owns with that name, including those not in play, loses all abilities)/',
            fn(array $m, array &$st) => $one('(the named lock)', fn($c) => 0.0)],
        ['/^discard a card with that name from it/', fn(array $m, array &$st) => $one('(named discard)', fn($c) => 0.0)],
        // ── Coverage growth (plan Task 4). "(derived)" = not an owner price; listed for the owner's review.
        ['/^deal damage to an enemy unit equal to attached unit\'s power/', function (array $m, array &$st) use ($one) {
            $up = intval(CardUpgradePower($st['cid']));
            return $one('damage = attached power (derived host 3)', function ($c) use ($up): float {
                if ($c['seat'] === null) return _SWUBotCurveUnitDamage(3 + $up);
                $host = 0;
                foreach (SWUBotUnits($c['seat']) as $v) $host = max($host, $v['power']);
                return _SWUBotCurveDamageToUnit($c, $host + $up, []);
            });
        }],
        ['/^give a shield token or an experience token to this unit/', fn(array $m, array &$st) => $one('shield or experience', fn($c) => 1.0)],
        ['/^(?:he|she|it|this unit) deals damage equal to (?:his|her|its) power to an? ((?:enemy |ground |space )*)unit/', function (array $m, array &$st) use ($one) {
            $pw = intval(CardPower($st['cid'])); $f = _SWUBotCurveWords($m[1] ?? '');
            return $one("deals its power ($pw)", fn($c) => _SWUBotCurveDamageToUnit($c, $pw, $f));
        }],
        ['/^an enemy unit loses all abilities for this phase/', fn(array $m, array &$st) => $one('loses abilities (derived 0)', fn($c) => 0.0)],
        ['/^if it costs (\d+) or less, defeat it/', fn(array $m, array &$st) => $one("defeat it if it costs ≤{$m[1]}",
            fn($c) => _SWUBotCurveDefeat($c, ['non-leader' => true, 'costMax' => intval($m[1])]))],
        ['/^defeat an? (?:non-<uq> |non-leader )?upgrade(?: attached to a unit)?(?! instead)/', fn(array $m, array &$st) => $one('defeat an upgrade (derived)', function ($c): float {
            if ($c['seat'] === null) return 1.0;
            $best = 0.0;
            foreach (_SWUBotCurveEnemyViews($c['seat']) as $v) if ($v['upgrades'] - $v['downgrades'] > 0) $best = max($best, min(3.0, (float)$v['upgradeValue']));
            return $best;
        })],
        ['/^deal (\d+) damage to each of up to (\d+) units/', fn(array $m, array &$st) => $one("{$m[1]} damage to each of up to {$m[2]}", function ($c) use ($m): float {
            $k = intval($m[2]);
            if ($c['seat'] !== null) $k = min($k, count(_SWUBotCurveEnemyViews($c['seat'])));
            return $k * _SWUBotCurveUnitDamage(intval($m[1]));
        })],
        ['/^ready an exhausted enemy unit/', fn(array $m, array &$st) => $one('readies an enemy (derived)', fn($c) => -1.0)],
        ['/^give an? (?:enemy )?unit -(\d+)\/-(\d+) for this phase/', fn(array $m, array &$st) => $one("-{$m[1]}/-{$m[2]} (derived as damage)",
            fn($c) => _SWUBotCurveDamageToUnit($c, intval($m[2]), []))],
        ['/^give each enemy unit -(\d+)\/-(\d+) for this phase/', fn(array $m, array &$st) => $one("-{$m[1]}/-{$m[2]} to each enemy (derived)", function ($c) use ($m): float {
            $n = intval($m[2]);
            if ($c['seat'] === null) return 2 * _SWUBotCurveUnitDamage($n);
            $v = 0.0;
            foreach (_SWUBotCurveEnemyViews($c['seat']) as $e) $v += _SWUBotCurveUnitDamage(min($n, $e['remaining']));
            return $v;
        })],
        ['/^deal damage to a unit equal to the number of friendly space units/', fn(array $m, array &$st) => $one('damage = friendly space units (derived)', function ($c): float {
            if ($c['seat'] === null) return _SWUBotCurveUnitDamage(2);
            $n = count(array_filter(SWUBotUnits($c['seat']), fn($v) => $v['arena'] === 'Space')) + 1;   // + itself, entering play
            return _SWUBotCurveDamageToUnit($c, $n, []);
        })],
        ['/^deal (\d+) damage divided as you choose among enemy units/', fn(array $m, array &$st) => $one("{$m[1]} divided (derived)", function ($c) use ($m): float {
            $n = intval($m[1]);
            if ($c['seat'] === null) return _SWUBotCurveUnitDamage($n);
            $rem = array_column(_SWUBotCurveEnemyViews($c['seat']), 'remaining'); sort($rem);
            $v = 0.0;
            foreach ($rem as $r) { if ($n <= 0) break; $d = min($n, $r); $v += _SWUBotCurveUnitDamage($d); $n -= $d; }   // smallest first: most kills
            return $v;
        })],
        ['/^defeat any number of non-leader units with a total of (\d+) or less remaining hp/', function (array $m, array &$st) use ($one) {
            $cap = intval($m[1]);
            $st['killCount'] = function ($c) use ($cap): int { return count(_SWUBotCurveKillSet($c['seat'], $cap)); };
            return $one("defeat ≤{$cap} total HP", function ($c) use ($cap): float {
                if ($c['seat'] === null) return _SWUBotCurveUnitDamage($cap);
                $v = 0.0;
                foreach (_SWUBotCurveKillSet($c['seat'], $cap) as $u) $v += _SWUBotCurveWorth($u);
                return $v;
            });
        }],
        ['/^create an? ([\w -]+?) token for each unit defeated this way/', function (array $m, array &$st) use ($one) {
            $v = _SWUBotCurveTokenValue($m[1]); $kills = $st['killCount'] ?? null;
            if ($v === null || $kills === null) return null;
            return $one("a {$m[1]} token per kill", fn($c) => ($c['seat'] === null ? 1 : $kills($c)) * $v);
        }],
        ['/^choose a non-leader unit that opponent controls that costs (\d+) or less and return it to its owner\'s hand/', function (array $m, array &$st) use ($one) {
            $f = ['non-leader' => true, 'costMax' => intval($m[1])];
            return $one('bounce', function ($c) use ($f): float {                 // the target's cost, in lost tempo
                if ($c['seat'] === null) return (float)min(4, $f['costMax']);
                $best = 0;
                foreach (_SWUBotCurveTargets($c['seat'], $f) as $v) $best = max($best, $v['cost']);
                return (float)$best;
            });
        }],
        ['/^pay (\d+) resources?/', fn(array $m, array &$st) => $one("pay {$m[1]}", fn($c) => -1.0 * intval($m[1]))],
        ['/^move this unit to the (?:ground|space) arena/', fn(array $m, array &$st) => $one('change arena (derived 0)', fn($c) => 0.0)],
        // ── One-off batch (2026-10-06): derived prices, each labelled ─────────────────────────────────────────────
        ['/^each opponent may ready a resource/', fn(array $m, array &$st) => $one('opponent readies a resource (derived)', fn($c) => -1.0)],
        ['/^choose an aspect/', fn(array $m, array &$st) => $one('choose an aspect', fn($c) => 0.0)],
        ['/^give each enemy unit with that aspect -(\d+)\/-(\d+) for this phase/', fn(array $m, array &$st) => $one("-{$m[1]}/-{$m[2]} to one aspect (derived)", function ($c) use ($m): float {
            $n = intval($m[2]);
            if ($c['seat'] === null) return _SWUBotCurveUnitDamage($n);             // one unit
            $best = 0.0;
            foreach (['vigilance', 'command', 'aggression', 'cunning', 'heroism', 'villainy'] as $a) {
                $v = 0.0;
                foreach (_SWUBotCurveEnemyViews($c['seat']) as $e) if (str_contains(strtolower(strval(CardAspect($e['cardID']))), $a)) $v += _SWUBotCurveUnitDamage(min($n, $e['remaining']));
                $best = max($best, $v);
            }
            return $best;
        })],
        ['/^put an? (\w+) card from your discard pile on the bottom of your deck/', function (array $m, array &$st) use ($one) {
            $st['pending'] = "there is a {$m[1]} card in your discard pile";
            return $one("bottom a {$m[1]} card from discard (derived 0)", fn($c) => 0.0);
        }],
        ['/^give an upgraded unit -(\d+)\/-0 for this phase/', fn(array $m, array &$st) => $one("-{$m[1]}/-0 to an upgraded enemy", function ($c) use ($m): float {
            if ($c['seat'] === null) return 0.0;
            foreach (_SWUBotCurveEnemyViews($c['seat']) as $v) if ($v['upgrades'] - $v['downgrades'] > 0) return 0.5 * intval($m[1]);
            return 0.0;
        })],
        ['/^choose a unit in your discard pile that costs \d+ or less/', fn(array $m, array &$st) => $one('choose from discard', fn($c) => 0.0)],
        ['/^either put that card on the bottom of your deck and heal (\d+) damage from your base or return it to your hand/', fn(array $m, array &$st) =>
            $one('heal or recursion (derived)', fn($c) => max(intval($m[1]) / 3.0, 1.0))],
        ['/^an opponent discards a card from their hand/', fn(array $m, array &$st) => $one('their-choice discard (derived)', fn($c) => 0.5)],
        ['/^deal damage equal to its cost divided as you choose among any number of units/', fn(array $m, array &$st) => $one('divided damage = discarded cost (derived 3)', fn($c) => _SWUBotCurveUnitDamage(3))],
        ['/^that unit can\'t ready while this unit is in play/', fn(array $m, array &$st) => [['lock: stays exhausted (derived)', fn($c) => 2.0, 'last']]],
        ['/^if it costs \d+ or less, (?:you may )?play it for free/', fn(array $m, array &$st) => $one('free cheap play (derived)', fn($c) => 1.0)],
        ['/^exhaust a friendly leader/', fn(array $m, array &$st) => $one('exhaust own leader (derived)', fn($c) => -0.5)],
        ['/^search its controller\'s deck and hand for each card with that unit\'s name and discard them/', fn(array $m, array &$st) => $one('discard its copies (derived)', fn($c) => 1.0)],
        ['/^deal (\d+) damage to each damaged unit/', fn(array $m, array &$st) => $one("{$m[1]} to each damaged unit", function ($c) use ($m): float {
            $n = intval($m[1]);
            if ($c['seat'] === null) return _SWUBotCurveUnitDamage($n);             // one unit (derived)
            $v = 0.0;
            foreach (_SWUBotCurveEnemyViews($c['seat']) as $e) if ($e['remaining'] < $e['hp']) $v += _SWUBotCurveUnitDamage(min($n, $e['remaining']));
            foreach (SWUBotUnits($c['seat']) as $mine) if ($mine['remaining'] < $mine['hp']) $v -= _SWUBotCurveUnitDamage(min($n, $mine['remaining']));
            return $v;
        })],
        ['/^if this unit dealt combat damage to a base, heal that much damage from your base/', function (array $m, array &$st) use ($one) {
            $pw = intval(CardPower($st['cid']));
            return $one('heal its base damage (half, derived)', fn($c) => 0.5 * $pw / 3.0);
        }],
        ['/^give those tokens sentinel for this phase/', fn(array $m, array &$st) => $one('tokens gain Sentinel (derived 0)', fn($c) => 0.0)],
        ['/^defeat an upgrade instead/', fn(array $m, array &$st) => $one('any upgrade instead (derived 0)', fn($c) => 0.0)],
        ['/^all units lose sentinel/', fn(array $m, array &$st) => $one('all lose Sentinel (derived 0)', fn($c) => 0.0)],
        ['/^discard a card from your hand/', fn(array $m, array &$st) => $one('discard own card (derived)', fn($c) => -1.0)],
        ['/^this unit gains sentinel for this phase/', fn(array $m, array &$st) => [['Sentinel for the phase (derived)', fn($c) => 0.5, 'last']]],
        ['/^choose an arena/', fn(array $m, array &$st) => $one('choose an arena', fn($c) => 0.0)],
        ['/^a friendly space unit deals damage equal to its power to each enemy unit in that arena/', fn(array $m, array &$st) => $one('space unit hits each enemy in an arena', function ($c): float {
            if ($c['seat'] === null) return 2 * _SWUBotCurveUnitDamage(4);          // power 4 × 2 units (derived)
            $p = 0;
            foreach (SWUBotUnits($c['seat']) as $v) if ($v['arena'] === 'Space') $p = max($p, $v['power']);
            $best = 0.0;
            foreach (['Ground', 'Space'] as $a) {
                $x = 0.0;
                foreach (_SWUBotCurveEnemyViews($c['seat']) as $e) if ($e['arena'] === $a) $x += _SWUBotCurveUnitDamage(min($p, $e['remaining']));
                $best = max($best, $x);
            }
            return $best;
        })],
        ['/^choose a friendly unit and an enemy non-leader unit/', fn(array $m, array &$st) => $one('choose a pair', fn($c) => 0.0)],
        ['/^defeat those units/', fn(array $m, array &$st) => $one('paired defeat (derived)', function ($c): float {
            if ($c['seat'] === null) return 5.0 - 1.0;                               // a non-leader kill (5) for my cheapest unit, ~a token (owner rule D)
            $e = array_map('_SWUBotCurveWorth', _SWUBotCurveTargets($c['seat'], ['non-leader' => true]));
            $mine = array_map('_SWUBotCurveWorth', array_values(array_filter(SWUBotUnits($c['seat']), fn($v) => !$v['isLeader'])));
            if (!$e || !$mine) return 0.0;                                            // "you may": skipped
            return max(0.0, min(5.0, max($e)) - min($mine));
        })],
    ];
    return $P;
}

// Pre Vizsla's kill set: non-leader enemy units whose remaining HP sums to <= $cap, most worth per HP first.
function _SWUBotCurveKillSet(int $seat, int $cap): array {
    $c = _SWUBotCurveTargets($seat, ['non-leader' => true]);
    usort($c, fn($a, $b) => (_SWUBotCurveWorth($b) / max(1, $b['remaining'])) <=> (_SWUBotCurveWorth($a) / max(1, $a['remaining'])));
    $out = []; $left = $cap;
    foreach ($c as $u) if ($u['remaining'] <= $left) { $out[] = $u; $left -= $u['remaining']; }
    return $out;
}

// One sentence → terms, or null when any part of it is unreadable.
function _SWUBotCurveSentence(string $s, array &$state): ?array {
    $s = trim($s);
    if ($s === '') return [];
    if (preg_match('/^if (you control .+?), this (?:event|unit|upgrade) costs \[(\d+) resources?\] less to play$/', $s, $m)) {
        if (_SWUBotCurveCondition(null, $m[1]) === null) return null;
        $state['discount'] = [$m[1], intval($m[2])];
        return [];
    }
    $s = preg_replace('/^(?:you may |then, |then |if you do, )+/', '', $s);
    $cond = null;
    if (preg_match('/^if (you control [^,]+|you have the initiative|a friendly unit was defeated this phase|there is an? \w+ card in your discard pile|the force is with you|this unit is upgraded), (.*)$/', $s, $m)) {
        if (_SWUBotCurveCondition(null, $m[1]) === null) return null;
        $cond = $m[1]; $s = $m[2];
    }
    $terms = [];
    // ltrim FIRST: after a match the remainder starts with a space (" and exhaust it"), which the ^-anchored connector
    // strip would otherwise miss.
    while (($s = trim(preg_replace('/^(?:,\s*|and |then, |then |you may |if you do, )+/', '', ltrim($s)))) !== '') {
        $hit = false;
        foreach (_SWUBotCurveEffectPatterns() as [$re, $build]) {
            if (!preg_match($re, $s, $m)) continue;
            $sub = $build($m, $state);
            if ($sub === null) return null;
            array_push($terms, ...$sub);
            $s = substr($s, strlen($m[0]));
            $hit = true;
            break;
        }
        if (!$hit) return null;
    }
    return $cond === null ? $terms : _SWUBotCurveWrapCond($terms, $cond);
}

// An ability body: sentences, or "choose two" modes priced live — the best two count (owner, 2026-10-05).
function _SWUBotCurveBody(string $body, array &$state): ?array {
    $body = trim($body);
    if ($body === '') return [];
    if (preg_match('/^choose two, in any order: (.*)$/', $body, $m)) return _SWUBotCurveModal($m[1], $state);
    $terms = []; $active = null;
    foreach (preg_split('/(?<=\.)\s+/', $body) as $sentence) {
        $sx = trim(rtrim(trim($sentence), '.'));
        $pending = $state['pending'] ?? null; $state['pending'] = null;
        $sub = _SWUBotCurveSentence($sx, $state);
        if ($sub === null) return null;
        // "you may disclose …" / "you may use the Force" — the "if you do," effect needs the same condition, and so does
        // everything after it in this ability (Cantwell: "…exhaust an enemy unit. That unit can't ready while …").
        if ($pending !== null && str_starts_with($sx, 'if you do,')) $active = $pending;
        if ($active !== null) $sub = _SWUBotCurveWrapCond($sub, $active);
        array_push($terms, ...$sub);
    }
    return $terms;
}

function _SWUBotCurveWrapCond(array $terms, string $cond): array {
    return array_map(fn($t) => array_values(array_filter(["if $cond: {$t[0]}", function (array $c) use ($t, $cond): ?float {
        return _SWUBotCurveCondition($c['seat'], $cond) ? ($t[1])($c) : 0.0;
    }, $t[2] ?? null], fn($x) => $x !== null)), $terms);
}

function _SWUBotCurveModal(string $opts, array &$state): ?array {
    $modes = [];
    foreach (preg_split('/(?<=\.)\s+/', trim($opts)) as $o) {
        $t = _SWUBotCurveSentence(rtrim(trim($o), '.'), $state);
        if ($t !== null && $t !== []) $modes[] = $t;                      // an unreadable mode is simply not offered
    }
    if (count($modes) < 2) return null;
    return [['best two modes', function (array $c) use ($modes): ?float {
        $vals = [];
        foreach ($modes as $terms) {
            $v = 0.0;
            foreach ($terms as [$l, $fn]) { $r = $fn($c); if ($r === null) { $v = null; break; } $v += $r; }
            if ($v !== null) $vals[] = $v;
        }
        if (count($vals) < 2) return null;
        rsort($vals);
        return $vals[0] + $vals[1];
    }]];
}

// Unit constants (text outside a trigger). Anything not listed → unpriced.
function _SWUBotCurveConstants(string $chunk, string $cid = ''): ?array {
    $terms = [];
    foreach (preg_split('/(?<=\.)\s+/', trim($chunk)) as $s) {
        $s = trim(rtrim(trim($s), '.'));
        if ($s === '') continue;
        if ($s === 'this unit enters play ready') {
            $terms[] = ['enters ready', fn(array $c) => SWU_CURVE_READY_STATS / SWU_CURVE_STATS_PER_RESOURCE, 'now'];
        } elseif (preg_match('/^while you control another ([\w -]+?) unit, this unit gains raid (\d+)$/', $s, $m)) {
            $cond = "you control another {$m[1]} unit"; $n = intval($m[2]);
            $stats = $n <= 1 ? SWU_CURVE_FREE_STATS : $n - 1.0;
            $terms[] = ["if $cond: raid $n", fn(array $c) => _SWUBotCurveCondition($c['seat'], $cond) ? $stats / SWU_CURVE_STATS_PER_RESOURCE : 0.0];
        } elseif (preg_match('/^(?:when you play an? [\w -]+? unit ?: this unit gains sentinel for this phase|while you control an? [\w -]+? unit, this unit gains sentinel)$/', $s)) {
            // Obi-Wan (LOF), Koska Reeves: a CONDITIONAL Sentinel = 1 stat (owner) — "immediate value, but not guaranteed".
            $terms[] = ['conditional Sentinel', fn(array $c) => 1.0 / SWU_CURVE_STATS_PER_RESOURCE];
        } elseif (preg_match('/^attached unit gains (saboteur|overwhelm|grit|hidden|raid 1|restore 1)$/', $s)) {
            $terms[] = ['grants a free keyword', fn(array $c) => SWU_CURVE_FREE_STATS / SWU_CURVE_STATS_PER_RESOURCE];
        } elseif (preg_match('/^attached unit gains (sentinel|ambush|shielded)$/', $s, $m)) {
            $terms[] = ["grants {$m[1]}", fn(array $c) => 2.0 / SWU_CURVE_STATS_PER_RESOURCE];
        } elseif (preg_match('/^while (the force is with you|you control an? [\w -]+? unit), this unit gains (ambush|sentinel|shielded|hidden|saboteur|grit|overwhelm|raid 1)$/', $s, $m)) {
            $cond = $m[1]; $kw = $m[2]; $stats = SWU_CURVE_KEYWORD_STATS[$kw] ?? SWU_CURVE_FREE_STATS;
            $terms[] = ["if $cond: $kw", fn(array $c) => _SWUBotCurveCondition($c['seat'], $cond) ? $stats / SWU_CURVE_STATS_PER_RESOURCE : 0.0, $kw === 'ambush' ? 'now' : 'last'];
        } elseif (preg_match('/^while this unit is upgraded, it gets \+\d+\/\+\d+$/', $s)) {
            $terms[] = ['while upgraded (never at play, derived 0)', fn(array $c) => 0.0];
        } elseif (preg_match('/^if attached unit is .+?, it gains (sentinel|ambush|shielded|hidden|saboteur|grit|overwhelm|raid (\d+)|restore (\d+))$/', $s, $m)) {
            $kw = _SWUBotCurveKeywords($m[1]); $stats = array_sum(_SWUBotCurveKeywordStats($kw));
            $terms[] = ["host-dependent {$m[1]} (50%, derived)", fn(array $c) => 0.5 * $stats / SWU_CURVE_STATS_PER_RESOURCE];
        } elseif (preg_match('/^this upgrade costs (\d+) resources? less to play on [^.]+$/', $s, $m)) {
            $n = intval($m[1]);
            $terms[] = ["conditional self-discount (50%, derived)", fn(array $c) => 0.5 * $n];
        } elseif ($s === 'you assign all indirect damage you deal to opponents') {
            $terms[] = ['assigns its own indirect (derived 0)', fn(array $c) => 0.0];
        } elseif (preg_match('/^when an enemy unit is defeated: (.+)$/', $s, $m)) {
            // An ENGINE: its effect × the enemy kills it can expect (owner rule D): one per round left in the horizon, capped at
            // cost + 1 — about how many rounds a unit of that cost survives (a 2-cost HK-47 rarely sees six rounds).
            $st = ['discount' => null, 'lastIndirect' => 0, 'cid' => ''];
            $inner = _SWUBotCurveSentence($m[1], $st);
            if ($inner === null) return null;
            foreach ($inner as [$l, $fn]) {
                $terms[] = ["per enemy kill (×rounds left, capped at cost + 1): $l", function (array $c) use ($fn, $cid): ?float {
                    $r = $fn($c);
                    if ($r === null) return null;
                    $round = $c['seat'] === null ? 1 : max(1, intval(GetTurnNumber()));
                    $h = $c['seat'] === null ? SWU_CURVE_STATIC_HORIZON : intval($c['h']);
                    $kills = min(max(1, $h + 1 - $round), intval(CardCost($cid)) + 1);
                    return $kills * $r;
                }];
            }
        } elseif (preg_match('/^while this unit is upgraded, (?:he|she|it) loses sentinel and gains saboteur$/', $s)) {
            $terms[] = ['upgraded: Sentinel → Saboteur', fn(array $c) => 0.0];   // Marrok: a trade, ≈ 0
        } else {
            // Not a known constant: read it as an effect sentence (auras, bodyguards, reducers…), LASTING.
            $st = ['discount' => null, 'lastIndirect' => 0, 'cid' => $cid, 'pending' => null];
            $sub = _SWUBotCurveSentence($s, $st);
            if ($sub === null) return null;
            foreach ($sub as $t) $terms[] = [$t[0], $t[1], $t[2] ?? 'last'];
        }
    }
    // Constants live on the unit: LASTING (rule B) unless marked immediate ('enters ready').
    return array_map(fn($t) => isset($t[2]) ? $t : [$t[0], $t[1], 'last'], $terms);
}

function _SWUBotCurveOverrideTerms(string $kind): array {
    switch ($kind) {
        case 'opponent-chooses':
            return [['opponent defeats their worst unit', function (array $c): ?float {
                if ($c['seat'] === null) return null;
                $w = array_map('_SWUBotCurveWorth', _SWUBotCurveEnemyViews($c['seat']));
                return $w ? min($w) : 0.0;
            }]];
        case 'each-player-chooses':
            return [['each player defeats their worst non-leader', function (array $c): ?float {
                if ($c['seat'] === null) return null;
                $e = array_map('_SWUBotCurveWorth', _SWUBotCurveTargets($c['seat'], ['non-leader' => true]));
                $mine = array_map('_SWUBotCurveWorth', array_values(array_filter(SWUBotUnits($c['seat']), fn($v) => !$v['isLeader'])));
                return ($e ? min($e) : 0.0) - ($mine ? min($mine) : 0.0);
            }]];
        case 'replayable-by-opponent':
            return [['drawback: opponent may replay it free', fn(array $c) => -1.0]];
    }
    return [];
}

// Uniqueness is by full name: title AND subtitle (another printing of the same character is a different card).
function _SWUBotCurveUniqueInPlay(int $seat, string $cid): bool {
    foreach (SWUBotUnits($seat) as $v) {
        if (CardTitle($v['cardID']) === CardTitle($cid) && CardSubtitle($v['cardID']) === CardSubtitle($cid)) return true;
    }
    return false;
}

// The curve-value mulligan (proposal 'curvemull', spec §4.3). Set aside the two cards the bot would resource; keep when
// at least 2 of the rest are castable by round 3 (printed cost <= 4) and their summed surplus is >= 0. The aggro wing
// (rank <= 1) also needs one play at cost <= 2. Costs are printed and there is no board (spec): unpriced cards count 0.
function _SWUBotCurveMulligan(int $seat, string $style): bool {
    $aside = array_map(fn($mz) => intval(substr($mz, strlen('myHand-'))), SWUBotChooseResourceCards(['seat' => $seat, 'style' => $style], 2));
    $H = SWUBotHorizon($style, $seat);
    $castable = 0; $surplus = 0.0; $cheap = false;
    foreach (GetHand($seat) as $i => $o) {
        if ($o === null || !empty($o->removed) || in_array($i, $aside, true)) continue;
        $cid = strval($o->CardID ?? '');
        $c = intval(CardCost($cid));
        if ($c <= 2) $cheap = true;
        if ($c <= 4) { $castable++; $surplus += SWUBotCurveSurplus($seat, $cid, $H, false) ?? 0.0; }
    }
    $keep = $castable >= 2 && $surplus >= 0.0 && (SWUBotStyleRank($style) > 1 || $cheap);
    return !$keep;
}
