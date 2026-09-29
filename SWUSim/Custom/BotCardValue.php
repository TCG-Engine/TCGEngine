<?php
// ── SWUSim bot: CARD VALUE ────────────────────────────────────────────────────────────────────────────
// Spec: docs/superpowers/specs/2026-09-28-swusim-card-value-design.md (owner chose the FULL scope, with
// defence in v1, 2026-09-28).
//
//   SWUBotCardValue($player, $cardID, $fromZone) -> float, in EXPECTED BASE DAMAGE
//
// ⚠ THE GAMESTATE IS NOT A PARAMETER because this engine has no first-class gamestate object — the board
// lives in the engine's globals/zones, loaded per request, and every zone read is relative to $playerID.
// So the value is computed against the LIVE gamestate in $player's frame, which the function sets and
// restores itself. A caller that wants a hypothetical board evaluates inside that board's frame; that is
// exactly what BotLookahead.php already does when it applies an action and re-scores.
//
// WHY ONE CURRENCY. Everything is expressed in expected base damage because W['base'] (0.60) is the only
// weight that is FLAT across all five archetypes, with the stated rationale "a point of base damage is
// 1/30th of a win for everyone". Anchoring to it means a tag's worth is a FUNCTION OF BOARD STATE
// returning damage-equivalents, not a hand-tuned constant per archetype — which matters because a
// 415-cell table (83 inert tags x 5 archetypes) is not measurable at ~±1.3pp per 500-game arm.
//
// ⚠ COST STAYS AS A FLOOR. `develop x printed cost` is KEPT and added to, never replaced. Proposal
// 'unitvalue' measured −50 (p .076) doing the opposite, and 'unitvalue2' was built to explain why: pricing
// a card by its body loses what the designers charged for its TEXT. The body terms ADD to the floor.
//
// Default OFF behind proposal 'cardvalue'. With the flag off, _SWUBotPlayValue is byte-identical.

// Expected remaining turns the archetype plays for, by RACING rank (hyper, soft, mid, softcontrol, hard).
// Falls out of two measurements rather than taste: the human's Krennic separated PERFECTLY at round 7
// across 15 real games (all 6 losses ended <= round 6, all 9 wins >= round 7), and control wins ~53% of
// the games that reach round 8 while reaching it ~32% of the time.
const SWU_BOT_HORIZON = [3, 4, 6, 8, 9];

// Per-round chance a unit on the board is still there next round. Expected swings is the sum of the
// survival series, NOT the horizon itself — a raw "H-1 swings" made a 3-power unit worth 8 swings to hard
// control (14 damage-equivalents against a 0.9 cost floor), which would have swamped every other term.
const SWU_BOT_SURVIVAL = 0.60;

// A defeated unit eats about this much damage on its way out, measured: the bot's damage per swing is a
// constant 2.6 regardless of board (memory `bot-damage-per-swing-is-a-constant-2-6`). Used to price one
// prevented instance (a Shield) and to size Sentinel soak.
const SWU_BOT_AVG_HIT = 2.6;

// How much of a Sentinel's HP actually gets spent soaking attacks that would otherwise hit the base.
// Not 1.0: some Sentinels are answered by removal, or the game ends first.
const SWU_BOT_SENTINEL_SOAK = 0.80;

// Zone reachability. A card's text is only worth something once the card can be PLAYED.
const SWU_BOT_ZONE_FACTOR = [
    'hand'      => 1.00,   // payable now; the aspect surcharge is priced below
    'deck'      => 0.85,   // real, but not in hand yet — this is what a search effect is choosing between
    'discard'   => 0.50,   // needs recursion to matter at all
    'resources' => 0.00,   // inert where it sits; Smuggle is the exception, handled explicitly
];

// ── TUNING HOOK ───────────────────────────────────────────────────────────────────────────────────────
// Every constant below is a GUESS. `SWUCV_<NAME>` overrides one at runtime so a sweep can ask whether the
// model is insensitive to them (2026-09-28: v1 measured +1.4pp arrival, p=0.58 — a null, and an
// unmeasured coefficient is one of the three candidate explanations).
// ⚠ With no environment variable set this returns the default, so ordinary play and every test are
// byte-identical. It is a probe hook, not a config surface: nothing in the product reads these.
function _SWUBotCVParam(string $name, float $default): float {
    static $cache = [];
    if (array_key_exists($name, $cache)) return $cache[$name];
    $raw = getenv('SWUCV_' . $name);
    return $cache[$name] = ($raw === false || trim((string)$raw) === '') ? $default : floatval($raw);
}

// Expected number of attacks a unit played NOW makes inside the horizon.
// A unit cannot attack the turn it arrives (Ambush is the exception), so the series starts next round.
function _SWUBotExpectedSwings(array $tags, int $H): float {
    $p = _SWUBotCVParam('SURVIVAL', SWU_BOT_SURVIVAL);
    $n = max(0, $H - 1);
    // Sum_{i=1..n} p^i — bounded by p/(1-p) however long the horizon is.
    $swings = ($n <= 0) ? 0.0 : $p * (1.0 - pow($p, $n)) / (1.0 - $p);
    if (in_array('ambush', $tags, true)) $swings += 1.0;   // attacks the turn it lands
    return $swings;
}

// The horizon this seat is playing to.
function SWUBotHorizon(string $style, int $seat): int {
    return SWU_BOT_HORIZON[SWUBotRacingRank($style, $seat)] ?? 6;
}

// First number in the first clause matching $re, else $default. Card text is the only place several of
// these amounts exist — a tag says "burn", never "burn for 3".
function _SWUBotTextNumber(string $text, string $re, int $default = 0): int {
    return preg_match($re, $text, $m) ? intval($m[1]) : $default;
}

// ── BODY ──────────────────────────────────────────────────────────────────────────────────────────────
// What a UNIT is worth for existing: the damage it deals, plus the damage it prevents.
// Defence is the term the shipped model has NO equivalent of, and the most likely cause of the control
// deficit (memories `hardcontrol-trades-buy-no-defence`, `control-loses-by-not-reaching-round-8`).
function _SWUBotCardBody(string $cid, array $tags, array $W, int $H): float {
    if (!str_contains(strval(CardType($cid)), 'Unit')) return 0.0;
    $power  = max(0, intval(CardPower($cid)));
    $hp     = max(0, intval(CardHp($cid)));
    $text   = strval(CardText($cid)) . ' ' . strval(CardDeployText($cid));
    $swings = _SWUBotExpectedSwings($tags, $H);

    // OFFENCE. Raid adds its N while attacking, so it rides every swing.
    $raid     = in_array('raid', $tags, true) ? _SWUBotTextNumber($text, '/\bRaid\s+(\d+)/i', 1) : 0;
    $offence  = ($power + $raid) * $swings * $W['base'];

    // DEFENCE.
    //  · Sentinel is the only keyword that REDIRECTS attacks, so it is the only one that converts HP into
    //    base damage prevented.
    //  · Shielded prevents one instance wherever it sits — priced at the measured average hit, not at HP.
    // A non-Sentinel body prevents nothing directly: in SWU units do not block, so "it might get attacked
    // instead" is not a guarantee and is deliberately worth 0 here.
    $defence = 0.0;
    if (in_array('sentinel', $tags, true))  $defence += $hp * _SWUBotCVParam('SENTINEL_SOAK', SWU_BOT_SENTINEL_SOAK) * $W['base'];
    if (in_array('shielded', $tags, true))  $defence += _SWUBotCVParam('AVG_HIT', SWU_BOT_AVG_HIT) * $W['base'];
    return $offence + $defence;
}

// ── EFFECT ────────────────────────────────────────────────────────────────────────────────────────────
// Tags -> functions of the board. This is what fixes the model's TARGET BLINDNESS: today a removal event
// scores `develop x cost + W['removal']` whether the best target is a 2-drop or a bomb.
function _SWUBotCardEffect(int $seat, string $cid, array $tags, array $W, int $H): float {
    $has  = fn(string $t) => in_array($t, $tags, true);
    $text = strval(CardText($cid)) . ' ' . strval(CardDeployText($cid));
    $v    = 0.0;
    $opp  = function_exists('OpponentsOf') ? (OpponentsOf($seat)[0] ?? 0) : 0;

    // Enemy and friendly unit views, valued in the same currency the attack scorer uses.
    $enemyVals = $friendlyVals = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $view = SWUBotViewForMz($seat, $mz);
            if ($view !== null) $enemyVals[] = SWUBotUnitValue($view);
        }
    }
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $view = SWUBotViewForMz($seat, $mz);
            if ($view !== null) $friendlyVals[] = SWUBotUnitValue($view);
        }
    }
    $bestEnemy = $enemyVals ? max($enemyVals) : 0.0;

    // REMOVAL family — worth the BEST LEGAL TARGET, not a constant. Zero on an empty enemy board, which is
    // the whole point: a removal spell with nothing to kill is a dead card.
    if ($has('removal') || $has('capture') || $has('bounce') || $has('take-control')) {
        $v += $W['kill'] * $bestEnemy;
    }
    // Damage to units: what it actually kills, else chip.
    if ($has('damage')) {
        $dmg = _SWUBotTextNumber($text, '/deal (\d+) damage/i', 1);
        $v += $W['chip'] * $dmg;
    }
    // WIPES — sum over what each side loses. The friendly half is a real cost, which is why the tags were
    // split into enemy/friendly halves in v3.
    if ($has('damage-wipe-enemy') || $has('defeat-wipe-enemy')) {
        foreach ($enemyVals as $ev) $v += $W['kill'] * $ev;
    }
    if ($has('damage-wipe-friendly') || $has('defeat-wipe-friendly')) {
        foreach ($friendlyVals as $fv) $v -= $W['loss'] * $fv;
    }
    // BURN — damage straight to a base needs no board read; it is already the anchor currency.
    if ($has('burn') || $has('indirect-damage')) {
        $v += $W['base'] * _SWUBotTextNumber($text, '/deal (\d+) damage/i', 1);
    }
    // HEAL — worth ~0 at full health, and most when the base is nearly dead.
    if ($has('heal') || $has('restore')) {
        $n    = _SWUBotTextNumber($text, '/heal (\d+) damage/i', 1);
        $base = GetBase($seat)[0] ?? null;
        $dmgOn = $base !== null ? intval($base->Damage ?? 0) : 0;
        $hp    = $base !== null ? max(1, intval(CardHp($base->CardID ?? ''))) : 30;
        $v += $W['base'] * min($n, $dmgOn) * ($dmgOn / $hp);
    }
    // GRANTED DEFENCE — the same damage-prevented arithmetic as Body, applied to somebody else.
    if ($has('gives-sentinel')) $v += _SWUBotCVParam('AVG_HIT', SWU_BOT_AVG_HIT) * _SWUBotCVParam('SENTINEL_SOAK', SWU_BOT_SENTINEL_SOAK) * $W['base'];
    if ($has('gives-shield'))   $v += _SWUBotCVParam('AVG_HIT', SWU_BOT_AVG_HIT) * $W['base'];
    // RAMP — worth the card it brings into reach. Reuses the creditvalue work rather than re-deriving it.
    // ⚠ Its second argument is the CREDIT COUNT, not the weights. Passing $W there was a TypeError that
    // killed 5 of 6 games in the first canary run — the harness caught it, the unit test did not, because
    // no fixture in it held a ramp card.
    // ⚠ _SWUBotCreditUnlockValue calls _SWUBotPlayValue, which with this proposal ON re-enters
    // SWUBotCardValue — see the depth guard there.
    if (($has('credit-ramp') || $has('resource-ramp') || $has('discount'))
        && function_exists('_SWUBotCreditUnlockValue')) {
        $n = max(1, _SWUBotTextNumber($text, '/create (\d+) Credit/i', 1));
        $v += _SWUBotCreditUnlockValue($seat, $n, $W);
    }
    // DRAW — cards x card-equivalent, through the deck-out-aware multiplier the old path already used.
    if ($has('draw') || $has('search-top-deck')) {
        $n = _SWUBotTextNumber($text, '/draw (\d+) card/i', 1);
        $v += $W['draw'] * $n * SWUBotDrawMultiplier($seat);
    }
    // TOKENS — a created body is a body. Priced through the same Body function so a Battle Droid and a
    // real 1/1 cannot disagree about what a 1/1 is worth.
    foreach ($tags as $t) {
        if (strncmp($t, 'create-', 7) !== 0) continue;
        $tok = _SWUBotTokenCardID($t);
        if ($tok !== '') $v += _SWUBotCardBody($tok, SWUBotCardTags($tok), $W, $H);
    }
    // SELF-HARM and COSTS are negative — that is what the `cost-` / `self-` prefixes exist for.
    if ($has('self-burn') || $has('self-damage')) {
        $v -= $W['base'] * _SWUBotTextNumber($text, '/deal (\d+) damage/i', 1);
    }
    if ($has('sacrifice') || $has('cost-sacrifice')) {
        $v -= $friendlyVals ? $W['loss'] * min($friendlyVals) : 0.0;   // you would pay the CHEAPEST body
    }
    if ($has('mill-self') || $has('cost-mill-self')) $v -= $W['draw'] * 0.5;
    return $v;
}

// Token tag -> the token's CardID, so a created body is valued as the body it is.
function _SWUBotTokenCardID(string $tag): string {
    static $map = [
        'create-battle-droid-token'  => 'TWI_T01',
        'create-clone-trooper-token' => 'TWI_T02',
        'create-x-wing-token'        => 'JTL_T02',
        'create-tie-fighter-token'   => 'JTL_T01',
    ];
    return $map[$tag] ?? '';
}

// ── THE ENTRY POINT ───────────────────────────────────────────────────────────────────────────────────
// $fromZone: 'hand' | 'deck' | 'discard' | 'resources' — where the card would be played FROM. It changes
// both reachability and what you pay, so it is an input, not an assumption:
//   · hand      — payable now, and the off-aspect surcharge is charged here
//   · deck      — what a search effect is choosing between
//   · discard   — only worth anything if something can play it back
//   · resources — inert where it sits, EXCEPT Smuggle
function SWUBotCardValue(int $player, string $cardID, string $fromZone = 'hand',
                         string $style = 'midrange', ?array $W = null): float {
    if ($cardID === '') return 0.0;
    // ⚠ RE-ENTRANCY GUARD. The ramp term asks _SWUBotCreditUnlockValue what a Credit unlocks, and that
    // prices the unlocked card with _SWUBotPlayValue — which, with this proposal on, lands back here. A
    // hand holding two ramp cards would otherwise recurse until the stack gave out. Beyond the first
    // level the card is worth its FLOOR only: enough to rank an unlock, no deeper board reads.
    static $depth = 0;
    if ($depth > 0) return ($W['develop'] ?? 0.30) * intval(CardCost($cardID));
    global $playerID;
    $saved = $playerID;
    $playerID = intval($player);
    $depth++;
    try {
        // ⚠ $style is a PARAMETER because the engine has no per-seat style lookup — the archetype is the
        // chooser's configuration and reaches the scorer as $ctx['style'], never as board state.
        if ($W === null) $W = SWUBotWeights($style, $player);
        // The horizon rides the WEIGHTS (BotArchetypes.php), so it is correct for whatever
        // archetype produced $W — including the racing shift — without threading the style.
        $H    = max(1, (int)round(($W['horizon'] ?? 6) * _SWUBotCVParam('HORIZON_SCALE', 1.0)));
        $tags = SWUBotCardTags($cardID);

        // The FLOOR the spec insists on keeping: cost as a proxy for what the designers charged.
        $v  = $W['develop'] * intval(CardCost($cardID));
        $v += _SWUBotCardBody($cardID, $tags, $W, $H);
        $v += _SWUBotCardEffect($player, $cardID, $tags, $W, $H);
        if (str_contains(strval(CardType($cardID)), 'Unit')) $v += $W['unitPlay'];

        // Zone reachability. A resource is inert WHERE IT SITS, so the only way it is worth anything is
        // Smuggle — which pays from that zone at its own cost, hence the full value rather than a factor.
        $zone = strtolower(trim($fromZone));
        if ($zone === '') $zone = 'hand';
        if ($zone === 'resources') {
            if (!in_array('smuggle', $tags, true)) return 0.0;
        } else {
            $zf = SWU_BOT_ZONE_FACTOR[$zone] ?? 1.0;
            if ($zone === 'deck')    $zf = _SWUBotCVParam('ZONE_DECK', $zf);
            if ($zone === 'discard') $zf = _SWUBotCVParam('ZONE_DISCARD', $zf);
            $v *= $zf;
        }

        // SELF-COST: the off-aspect surcharge is a real cost and nothing prices it today. Charged only
        // where the card is actually paid for out of hand.
        if ($zone === 'hand') {
            $obj = null;
            foreach (GetHand($player) as $h) {
                if (strval($h->CardID ?? '') === $cardID) { $obj = $h; break; }
            }
            if ($obj !== null && function_exists('SWUComputePlayCost')) {
                $surcharge = intval(SWUComputePlayCost($player, $obj)) - intval(CardCost($cardID));
                if ($surcharge > 0) $v -= $W['develop'] * $surcharge;
            }
        }
        return $v;
    } finally {
        $depth--;
        $playerID = $saved;
    }
}
