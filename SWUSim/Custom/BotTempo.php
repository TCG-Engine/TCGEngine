<?php
// TEMPO DETECTION (spec docs/superpowers/specs/2026-10-20-swusim-tempo-flavour-design.md §1, owner decisions 2026-10-20).
// "Tempo" is a FLAVOUR on top of a deck's style: it changes which plays the bot takes and when, never the style. It used to be a
// hand-kept per-leader tag in SWU_BOT_FLAVOURS with no behaviour; the deck's own cards (+ leader) now decide it, for any list.
//
// A tempo PIECE is a card whose effect is aimed at the OPPONENT ("an enemy unit", "a unit the opponent controls", or a free choice
// "a unit") of one of the owner's kinds. Self-aimed versions never count (owner rulings: Kylo Ren SHD / Maz Kanata HMW balance a cheat,
// DJ self-captures, Qui-Gon LOF tucks his own unit, Bail Organa / Hunter bounce their own resources, Gar Saxon his own upgrades). Take
// control (No Glory, Only Results) is removal; extra attacks / Saboteur / Shoot First are aggro tools; upgrade hate is left out for now.
//
// SCORE = Σ pieces (cost <= 5 counts 1.25 — "priced a little higher", big tempo cards like The Blade Wing are useful in any deck —
// cost >= 6 counts 1.0) + 5 for a tempo LEADER (a leader alone never makes a deck tempo, but "scores a good amount"), over the deck
// as the bot sees it. Tempo iff > 28% (owner: "anything above 28"). The RULE wins over the calibration list: Vader v9.4 (30%) and
// Maul Blue (29%) were read "barely" tempo, Lando Blue (24%) "more hard control".
// Premier (2 players) only: a 3-4 seat game is never tempo, so every tempo lever is inert in Twin Suns.
require_once __DIR__ . '/../Rl/CardTags.php';

const SWU_BOT_TEMPO_LEADERS = ['SOR_016', 'JTL_016', 'ASH_016', 'LOF_015', 'SEC_015', 'LAW_016', 'SHD_018', 'TWI_016', 'LOF_002',
                               'LOF_014', 'SOR_018', 'ASH_018', 'HMW_003', 'HMW_015', 'SEC_017', 'SHD_006', 'HMW_018'];
const SWU_BOT_TEMPO_THRESHOLD = 0.28;
const SWU_BOT_TEMPO_LEADER_WEIGHT = 5;

// Does this text aim its effect at the opponent? The opponent's words, or a free "a unit" choice (give / return / exhaust / capture).
function _SWUBotTempoEnemyAimed(string $t): bool {
    if (preg_match('/enemy|opponent|that player|defending player/', $t)) return true;
    // "…to a unit and give a Weakness token to it" (Inferno Squad HMW_202) is the same free choice as "give a unit".
    if (preg_match('/\bto (a|an|another) (non-leader |ground |space |non-vehicle )*unit\b/', $t)) return true;
    return (bool)preg_match('/\b(give|return|exhaust|capture)s? (a|an|another|up to \w+) (non-leader |ground |space |upgraded |non-unique )*units?\b/', $t);
}

// The tempo kind of a card — 'ambush','exhaust','bounce','handhate','debuff','weakness','capture','lock','handlook' — or null.
function SWUBotTempoPieceKind(string $cardID): ?string {
    $tags = SWUBotCardTags($cardID);
    $t = strtolower(strip_tags(strval(CardText($cardID) ?? '')));
    $has = fn(string $tag) => in_array($tag, $tags, true);
    if ($has('ambush')) return 'ambush';
    // A unit that gives ITSELF Ambush (Darth Tyranus LOF_231: "While the Force is with you, this unit gains Ambush").
    if ($has('gives-ambush') && preg_match('/(this unit|he|she) gains ambush/', $t) && !preg_match('/another|friendly unit gains/', $t)) return 'ambush';
    if ($has('exhaust-enemy-unit')) return 'exhaust';
    if ($has('discard-opponent-hand')) return 'handhate';
    foreach (['bounce' => 'bounce', 'debuff' => 'debuff', 'gives-weakness' => 'weakness', 'capture' => 'capture'] as $tag => $k) {
        if ($has($tag) && _SWUBotTempoEnemyAimed($t)) return $k;
    }
    // A stat reduction for the phase (Karis, Talzin's Assassin — tagged 'removal', so read from the text).
    if (preg_match('/-\d+\/-\d+ for this phase/', $t) && _SWUBotTempoEnemyAimed($t)) return 'debuff';
    if (preg_match('/(doesn\'?t|does not|can\'?t|cannot) ready/', $t) && !preg_match('/this unit doesn\'?t ready/', $t)) return 'lock';
    if (preg_match('/look at an opponent\'?s hand|reveals? (their|his|her|an opponent\'?s) hand/', $t)) return 'handlook';
    return null;
}

// The deck's tempo score as a share of its cards. $cards = [CardID => count]. 0.0 for an empty deck.
function SWUBotTempoScore(string $leaderID, array $cards): float {
    $n = 0; $k = 0.0;
    foreach ($cards as $id => $count) {
        $n += intval($count);
        if (SWUBotTempoPieceKind(strval($id)) !== null) $k += intval($count) * (intval(CardCost(strval($id))) <= 5 ? 1.25 : 1.0);
    }
    if ($n === 0) return 0.0;
    if (in_array($leaderID, SWU_BOT_TEMPO_LEADERS, true)) $k += SWU_BOT_TEMPO_LEADER_WEIGHT;
    return $k / $n;
}

function SWUBotIsTempoDeck(string $leaderID, array $cards): bool {
    return SWUBotTempoScore($leaderID, $cards) > SWU_BOT_TEMPO_THRESHOLD;
}

// This seat's own deck (every card it owns and can see, SWUBotOwnCardIDs) + leader. Cached per game: the self-play sweep runs each game
// in its own process; a test that builds several boards in one process calls SWUBotTempoCacheReset() between them.
function SWUBotSeatIsTempo(int $seat): bool {
    if (function_exists('SeatCountForGame') && SeatCountForGame() > 2) return false;
    $leader = strval((GetLeader($seat)[0] ?? null)->CardID ?? '');
    $key = "$seat|$leader|" . strval($GLOBALS['gameName'] ?? '');
    $cache = &_SWUBotTempoCache();
    if (!isset($cache[$key])) $cache[$key] = SWUBotIsTempoDeck($leader, array_count_values(SWUBotOwnCardIDs($seat)));
    return $cache[$key];
}

// The focus opponent's tempo flag — only from meta knowledge the bot already uses (their leader + base's labelled lists,
// SWUBotMetaCopiesByTitle), never from their real hidden deck. False without a meta list.
function SWUBotOpponentIsTempo(int $seat): bool {
    if (!function_exists('SWUBotMetaCopiesByTitle') || (function_exists('SeatCountForGame') && SeatCountForGame() > 2)) return false;
    $opp = SWUBotOpponent($seat);
    $leader = strval((GetLeader($opp)[0] ?? null)->CardID ?? '');
    $base = strval((GetBase($opp)[0] ?? null)->CardID ?? '');
    $cards = [];
    foreach (SWUBotMetaCopiesByTitle($leader, $base) as $row) {
        $c = intval(round(floatval($row['copies'] ?? 0)));
        if ($c > 0) $cards[strval($row['cid'])] = $c;
    }
    return SWUBotIsTempoDeck($leader, $cards);
}

function &_SWUBotTempoCache(): array {
    static $cache = [];
    return $cache;
}
function SWUBotTempoCacheReset(): void {
    $cache = &_SWUBotTempoCache();
    $cache = [];
}
