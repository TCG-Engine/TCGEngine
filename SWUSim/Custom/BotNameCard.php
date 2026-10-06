<?php
// "Name a card" for the bot — Part 21 'namecard' (owner request 2026-10-02: "fix how the bot names cards for stuff like
// Ryder Azadi. today it seems like it names cards from their own deck").
//
// THE BUG. A NAMECARD decision's Param is empty at every emitter, so DevTools/TestAutomationBridge.php synthesises the
// candidates from the deciding seat's OWN hand/discard/deck, sorted — and with no scorer the bot named the first title
// alphabetically. Nine of the ten emitters target the OPPONENT, so the bot was denying its own cards (Krennic Blue with
// ASH_077 Ryder Azadi named "Chimaera", its own finisher). Only the Foresight regroup grant (TWI_068) names a card for
// the seat's own deck.
//
// WHAT THE BOT MAY KNOW (owner ruling 2026-10-02: "shown cards + meta"):
//   · what an opponent has SHOWN this game — their discard pile, their units in play and the upgrades they own on them;
//   · the META: the pre-con lists (BotDeckLabels.json — the same rows the Arenabot pre-cons and the classifier read)
//     whose leader is the opponent's leader, preferring those on the same base. Knowing the meta is what a human brings
//     to the table; the opponent's actual hidden hand, deck and resources are NEVER read.
//
// WHICH CARD, by the card asking (the continuation after the NAMECARD carries its CardID: "ASH_077#0|…"):
//   deny      ASH_077 Ryder Azadi, SOR_062 Regional Governor — "opponents can't play" it: their most impactful card
//             they still have copies of, weighed by how close it is to castable (their resources) and by whether the
//             bot has SEEN it in their hand (Beguile, Garindan, Bodhi Rook, a reveal…);
//   galen     SEC_046 Galen Erso — "loses all abilities, including those not in play": the same, and copies already in
//             play count too;
//   jam       LAW_243 Transmission Jamming — "can't be played THIS PHASE": the most impactful card they can afford now;
//   hand      SOR_185 Chimaera, SEC_186 Garindan, SEC_260 Inspector's Shuttle, SEC_210 Stolen Starpath Unit — the card
//             most likely in their hand: the most copies still hidden;
//   decktop   LOF_204 Zuckuss — the top of their deck: the most copies still hidden;
//   own       TWI_068 Foresight — the top of MY deck: the title with the most copies left in my deck.
// Guard: SWUSim/DevTools/tests/bot_namecard_test.php.

const SWU_BOT_NAMECARD_FAMILY = [
    'ASH_077' => 'deny', 'SOR_062' => 'deny', 'SEC_046' => 'galen', 'LAW_243' => 'jam',
    'SOR_185' => 'hand', 'SEC_186' => 'hand', 'SEC_260' => 'hand', 'SEC_210' => 'hand',
    'LOF_204' => 'decktop', 'TWI_068' => 'own',
];
// A card an opponent has shown that no meta list has: assume they run this many (a shown card is at least 1).
const SWU_BOT_NAMECARD_SHOWN_DEFAULT_COPIES = 2;

// The card asking, from the decision's continuation ("ASH_077#0|<uid>"), or ''.
function SWUBotNameCardSource(array $following): string {
    return preg_match('/^([A-Z0-9]+_[A-Z0-9]+)#/', strval($following[0] ?? ''), $m) ? $m[1] : '';
}

function SWUBotNameCardFamily(string $source): string {
    return SWU_BOT_NAMECARD_FAMILY[$source] ?? 'hand';   // an unlisted emitter names into an opponent's hand by default
}

// A card that is never "played" from hand/deck, so naming it is pointless: tokens and leaders.
function _SWUBotNameCardPlayable(string $cid): bool {
    $type = strval(CardType($cid) ?? '');
    return $cid !== '' && stripos($type, 'Leader') === false && stripos($type, 'Token') === false && stripos($type, 'Base') === false
        && !preg_match('/_T\d+$/', $cid);
}

// The meta lists for $leader (same base first), as [title => ['copies' => average copies, 'cid' => a CardID]].
function SWUBotMetaCopiesByTitle(string $leader, string $base): array {
    static $decks = null;
    if ($decks === null) {
        $raw = is_readable(__DIR__ . '/BotDeckLabels.json') ? json_decode((string)file_get_contents(__DIR__ . '/BotDeckLabels.json'), true) : null;
        $decks = is_array($raw['decks'] ?? null) ? $raw['decks'] : [];
    }
    $same = array_values(array_filter($decks, fn($d) => strval($d['leader'] ?? '') === $leader && strval($d['base'] ?? '') === $base));
    if (empty($same)) $same = array_values(array_filter($decks, fn($d) => strval($d['leader'] ?? '') === $leader));
    if (empty($same)) return [];
    $out = [];
    foreach ($same as $d) {
        foreach ((array)($d['cards'] ?? []) as $cid => $n) {
            $cid = strval($cid);
            if (!_SWUBotNameCardPlayable($cid)) continue;
            $t = strval(CardTitle($cid));
            if ($t === '') continue;
            $out[$t] = ['copies' => ($out[$t]['copies'] ?? 0) + intval($n) / count($same), 'cid' => $out[$t]['cid'] ?? $cid];
        }
    }
    return $out;
}

// What $seat knows of ONE opponent's cards, by TITLE — shown cards, meta, and what $seat has SEEN in their hand:
//   cid, cost; expected (copies in their deck list); shown (copies public now); inPlay; remaining (= expected − shown, still
//   hidden); known (copies SEEN in hand and not gone public since); inHand / inDeck (expected copies in each hidden zone).
// "Seen in hand" (SWUHandSeenSnapshot, GameLogic.php): a looked-at card counts as still held unless more copies of its
// title have gone public since the look. The rest of the hand — cards drawn since, or never seen — is split between hand
// and deck by their public sizes, in proportion to the copies of each title still unaccounted for.
function _SWUBotOpponentModelFor(int $seat, int $opp): array {
    $model = [];
    $entry = function (string $cid) use (&$model) {
        $t = strval(CardTitle($cid));
        if ($t === '' || !_SWUBotNameCardPlayable($cid)) return '';
        $model[$t] = $model[$t] ?? ['cid' => $cid, 'expected' => 0.0, 'shown' => 0, 'inPlay' => 0, 'known' => 0];
        return $t;
    };
    foreach (GetDiscard($opp) as $o) { if ($o !== null && empty($o->removed) && ($t = $entry(strval($o->CardID ?? ''))) !== '') $model[$t]['shown']++; }
    foreach (['Ground', 'Space'] as $arena) {
        foreach (GetUnitsInArena($opp, $arena) as $u) {
            if (intval($u->Owner ?? $opp) === $opp && ($t = $entry(strval($u->CardID ?? ''))) !== '') {
                $model[$t]['shown']++; $model[$t]['inPlay']++;
                if (CardUnique(strval($u->CardID ?? '')) && intval($u->Controller ?? $opp) === $opp) $model[$t]['uniqueInPlay'] = true;
            }
            foreach (GetUpgradesOnUnit($u) as $up) {
                if (intval($up->Owner ?? 0) === $opp && ($t = $entry(strval($up->CardID ?? ''))) !== '') $model[$t]['shown']++;
            }
        }
    }
    $leader = strval((GetLeader($opp)[0] ?? null)->CardID ?? '');
    $base = strval((GetBase($opp)[0] ?? null)->CardID ?? '');
    foreach (SWUBotMetaCopiesByTitle($leader, $base) as $t => $meta) {
        $model[$t] = $model[$t] ?? ['cid' => $meta['cid'], 'expected' => 0.0, 'shown' => 0, 'inPlay' => 0, 'known' => 0];
        $model[$t]['expected'] = max($model[$t]['expected'], $meta['copies']);
    }
    // Seen in hand: copies of a title seen, less those that have gone public since the look.
    $snap = function_exists('SWUHandSeenSnapshot') ? SWUHandSeenSnapshot($seat, $opp) : null;
    if ($snap !== null) {
        $seen = []; $pubThen = [];
        foreach ($snap as [$cid, $pub]) {
            if (($t = $entry($cid)) === '') continue;
            $seen[$t] = ($seen[$t] ?? 0) + 1;
            $pubThen[$t] = $pub;
        }
        foreach ($seen as $t => $n) $model[$t]['known'] = max(0, $n - max(0, $model[$t]['shown'] - $pubThen[$t]));
    }
    $handSize = count(array_filter(GetHand($opp), fn($o) => $o !== null && empty($o->removed)));
    $deckSize = count(array_filter(GetDeck($opp), fn($o) => $o !== null && empty($o->removed)));
    $knownTotal = array_sum(array_column($model, 'known'));
    $unknownSlots = max(0, $handSize - $knownTotal);
    $handShare = $unknownSlots / max(1, $unknownSlots + $deckSize);
    foreach ($model as $t => &$m) {
        if ($m['shown'] + $m['known'] > 0 && $m['expected'] <= 0) $m['expected'] = max(SWU_BOT_NAMECARD_SHOWN_DEFAULT_COPIES, $m['shown'] + $m['known']);
        $m['expected'] = max($m['expected'], $m['shown'] + $m['known']);
        $m['remaining'] = max(0.0, $m['expected'] - $m['shown']);
        $other = max(0.0, $m['remaining'] - $m['known']);            // hidden copies not known to be in hand
        $m['inHand'] = $m['known'] + $other * $handShare;
        $m['inDeck'] = $other * (1.0 - $handShare);
        $m['cost'] = intval(CardCost($m['cid']));
        $m['uniqueInPlay'] = !empty($m['uniqueInPlay']);
    }
    unset($m);
    return $model;
}

// Every opponent's model merged by title (Twin Suns sums them; Premier is the one opponent). 'resources' is the most
// resources any opponent controls — how close their cards are to being cast.
function SWUBotOpponentCardModel(int $seat): array {
    $out = [];
    foreach (SWUBotOpponents($seat) as $opp) {
        foreach (_SWUBotOpponentModelFor($seat, $opp) as $t => $m) {
            if (!isset($out[$t])) { $out[$t] = $m; continue; }
            foreach (['expected', 'shown', 'inPlay', 'known', 'remaining', 'inHand', 'inDeck'] as $k) $out[$t][$k] += $m[$k];
            $out[$t]['uniqueInPlay'] = $out[$t]['uniqueInPlay'] || $m['uniqueInPlay'];
        }
    }
    return $out;
}

function _SWUBotOpponentResources(int $seat): int {
    $r = 0;
    foreach (SWUBotOpponents($seat) as $opp) $r = max($r, count(array_filter(GetResources($opp), fn($o) => $o !== null && empty($o->removed))));
    return $r;
}

// How close a card of $cost is to being cast (owner 2026-10-02: "the bot should also be resource aware … at 6R+, the
// usual bombs are good to name since they are close to being played"): full weight when they can cast it next round
// (cost ≤ resources + 1, they resource one a round), halved per resource beyond that.
function SWUBotCastProximity(int $cost, int $resources): float {
    return $cost <= $resources + 1 ? 1.0 : pow(0.5, $cost - $resources - 1);
}

// How many rounds until an opponent's next leader DEPLOY, for Plot cards (owner 2026-10-03: "Plot cards should be named
// closer to the human's deploy turn"): Plot plays a card from RESOURCES when a leader deploys, so near that turn a Plot
// card is about to be played whether or not it is in their hand. 0 = they can deploy now, 1 = next round (they resource
// one a round); null = no undeployed leader with its Epic Action unused (the Plot window is gone).
function SWUBotOpponentRoundsToDeploy(int $seat): ?int {
    $best = null;
    foreach (SWUBotOpponents($seat) as $opp) {
        $r = count(array_filter(GetResources($opp), fn($o) => $o !== null && empty($o->removed)));
        foreach (GetLeader($opp) as $l) {
            if ($l === null || !empty($l->removed)) continue;
            $truthy = fn($v) => !empty($v) && strval($v) !== 'false';
            if ($truthy($l->Deployed ?? null) || $truthy($l->EpicActionUsed ?? null)) continue;
            $n = max(0, (function_exists('_SWUBotLeaderThreshold') ? _SWUBotLeaderThreshold($opp, $l) : 99) - $r);
            $best = $best === null ? $n : min($best, $n);
        }
    }
    return $best;
}

// The titles worth offering for an opponent-targeting NAMECARD: everything in the model, sorted (deterministic).
function SWUBotOpponentNameCandidates(int $seat): array {
    $titles = array_keys(SWUBotOpponentCardModel($seat));
    sort($titles);
    return $titles;
}

// A candidate title's score. The decline ('-') scores 0, and a name the bot has no reason for scores just below it,
// so with nothing known the bot declines rather than naming a card at random.
//  deny / galen — impact (cost) × how close it is to castable, plus a premium for copies SEEN in their hand;
//  jam          — the same, but only a card castable THIS phase (their payment capacity now);
//  hand         — the expected copies in their hand (seen copies count in full);
//  decktop      — the expected copies in their deck (a card seen in hand is NOT on top of the deck).
const SWU_BOT_NAMECARD_SEEN_PREMIUM = 3.0;
// A UNIQUE unit of that name already on their board (owner 2026-10-03): "they most likely will not play another unique
// unit of the same kind since the human would have to defeat their existing one. so for now, weigh those less to name".
// Applies to the PLAY-denial families (deny, jam). Not to Galen — the copy in play loses its abilities too, so it is a
// fine name — nor to the hand / deck hitters, where a copy they will not play is a copy that stays in their hand.
// Title-level for now: naming is by title, uniqueness by title AND subtitle, so another version may still come.
const SWU_BOT_NAMECARD_UNIQUE_IN_PLAY = 0.25;
// A PLOT card on the turn before their deploy (or the deploy turn): castable then whatever its cost-vs-resources gap, and
// weighted up — it comes out of resources at the deploy, not from a hand the card may never reach.
const SWU_BOT_NAMECARD_PLOT_NEAR_DEPLOY = 1.5;
function SWUBotNameCardScore(int $seat, string $title, array $following): float {
    if ($title === '-' || $title === 'PASS') return 0.0;
    $family = SWUBotNameCardFamily(SWUBotNameCardSource($following));
    if ($family === 'own') {
        $n = 0;
        foreach (GetDeck($seat) as $o) { if ($o !== null && empty($o->removed) && strval(CardTitle(strval($o->CardID ?? ''))) === $title) $n++; }
        return $n > 0 ? 1.0 + $n : -0.01;
    }
    $m = SWUBotOpponentCardModel($seat)[$title] ?? null;
    if ($m === null) return -0.01;
    $known = SWU_BOT_NAMECARD_SEEN_PREMIUM * $m['known'];
    $unique = $m['uniqueInPlay'] ? SWU_BOT_NAMECARD_UNIQUE_IN_PLAY : 1.0;
    $isPlot = in_array('plot', SWUBotCardTags($m['cid']), true);
    $toDeploy = $isPlot ? SWUBotOpponentRoundsToDeploy($seat) : null;
    $plotSoon = $toDeploy !== null && $toDeploy <= 1;      // deny / galen: this round or next
    $plotNow  = $toDeploy !== null && $toDeploy === 0;     // jam: this phase
    $plot = $plotSoon ? SWU_BOT_NAMECARD_PLOT_NEAR_DEPLOY : 1.0;
    $prox = $plotSoon ? 1.0 : SWUBotCastProximity($m['cost'], _SWUBotOpponentResources($seat));
    switch ($family) {
        case 'deny':
            if ($m['remaining'] <= 0) return -0.01;
            return 1.0 + ($m['cost'] + $known) * $prox * $plot * $unique + 0.1 * $m['remaining'];
        case 'galen':
            $live = $m['remaining'] + $m['inPlay'];
            if ($live <= 0) return -0.01;
            return 1.0 + ($m['cost'] + $known) * max($m['inPlay'] > 0 ? 1.0 : 0.0, $prox) * $plot
                 + 0.1 * $live + 0.5 * $m['inPlay'];
        case 'jam':
            $cap = 0;
            foreach (SWUBotOpponents($seat) as $opp) $cap = max($cap, intval(SWUTotalPaymentCapacity($opp)));
            return ($m['remaining'] > 0 && $m['cost'] <= $cap) ? 1.0 + ($m['cost'] + $known) * $unique * ($plotNow ? SWU_BOT_NAMECARD_PLOT_NEAR_DEPLOY : 1.0) + 0.1 * $m['remaining'] : -0.01;
        case 'decktop':
            return $m['inDeck'] > 0 ? 1.0 + $m['inDeck'] + 0.01 * $m['cost'] : -0.01;
        default:   // 'hand'
            return $m['inHand'] > 0 ? 1.0 + $m['inHand'] + 0.01 * $m['cost'] : -0.01;
    }
}
