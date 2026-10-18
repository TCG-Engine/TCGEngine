<?php
// LEVER 'hostkeep' (theme 'resourcing'; ASH lever hunt G3-11) — an upgrade-built deck keeps the unique hosts its upgrades go on.
// Evidence (Chewbacca Outpost): Mastery resourced 61% / played 8%, Red Five 59/13, Hera 60/8, Arvel 46/24, Ryder 35 — the unique 3-drops that
// carry Moral Authority (capture), Mastery (+3/+3) and Durasteel are banked first, while Moral Authority itself is never banked and then played
// with no host worth it (no effect 30%). Change: in a deck with >= 10 upgrades or >= 6 "attach to a unique unit" cards, a unique unit in hand
// keeps a host bonus equal to the seat cost of the best upgrade in hand it can carry; when the hand holds more upgrades than hosts (units in
// hand + my units in play), the cheapest surplus upgrades keep -10 (resourced first). Downgrades are neither. Hook: 'keep'.

const SWU_BOT_LV_HOSTKEEP_UPGRADES = 10;
const SWU_BOT_LV_HOSTKEEP_UNIQUE_ATTACH = 6;
const SWU_BOT_LV_HOSTKEEP_SURPLUS = 10.0;

function _SWUBotLvHostkeepIsUpgrade(string $cid): bool {
    return str_contains(strval(CardType($cid)), 'Upgrade') && !SWUBotIsDowngrade($cid);
}

function _SWUBotLvHostkeepDeck(int $seat): bool {
    static $memo = [];
    $key = $seat . '|' . strval((GetLeader($seat)[0] ?? null)->CardID ?? '');
    if (!isset($memo[$key])) {
        $up = 0; $uq = 0;
        foreach (SWUBotOwnCardIDs($seat) as $cid) {
            if (!str_contains(strval(CardType($cid)), 'Upgrade')) continue;
            $up++;
            if (preg_match('/Attach to an? [^.]*(<uq>|unique)/i', strval(CardText($cid)))) $uq++;
        }
        $memo[$key] = $up >= SWU_BOT_LV_HOSTKEEP_UPGRADES || $uq >= SWU_BOT_LV_HOSTKEEP_UNIQUE_ATTACH;
    }
    return $memo[$key];
}

// Can unit card $u carry upgrade $g, by the upgrade's "Attach to a … unit" clause (none = any unit)?
function _SWUBotLvHostkeepCarries(string $u, string $g): bool {
    if (!preg_match('/Attach to an? ([^.]*?)\bunit/i', strval(CardText($g)), $m)) return true;
    $clause = $m[1]; $traits = strval(CardTrait($u) ?? '');
    if (preg_match('/non-Vehicle/i', $clause) && str_contains($traits, 'Vehicle')) return false;
    if (preg_match('/(^|\s)Vehicle/', $clause) && !preg_match('/non-Vehicle/i', $clause) && !str_contains($traits, 'Vehicle')) return false;
    if (preg_match('/<uq>|unique/i', $clause) && !CardUnique($u)) return false;
    if (preg_match('/\bground\b/i', $clause) && _SWUBotPlayArena($u) !== 'Ground') return false;
    if (preg_match('/\bspace\b/i', $clause) && _SWUBotPlayArena($u) !== 'Space') return false;
    $words = preg_split('/\s+/', trim(preg_replace('/<uq>|\(unique\)|non-Vehicle|\bVehicle\b|friendly|enemy|unique|ground|space|non-leader|leader/i', ' ', $clause)));
    foreach ($words as $w) if ($w !== '' && ctype_upper($w[0]) && !str_contains($traits, $w)) return false;   // a trait word: Jedi, Mandalorian…
    return true;
}

SWUBotLeverRegister('resourcing', 'hostkeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (!_SWUBotLvHostkeepDeck($seat)) return null;
        $hand = _SWUBotHandCards($seat);
        if (str_contains(strval(CardType($cid)), 'Unit') && CardUnique($cid)) {
            $best = 0;
            foreach ($hand as $g) if (_SWUBotLvHostkeepIsUpgrade($g) && _SWUBotLvHostkeepCarries($cid, $g)) $best = max($best, _SWUBotSeatCost($seat, $g));
            return $best > 0 ? $keep + $best : null;
        }
        if (!_SWUBotLvHostkeepIsUpgrade($cid)) return null;
        $ups = [];
        $hosts = count(SWUBotUnits($seat));
        foreach ($hand as $j => $h) {
            if (str_contains(strval(CardType($h)), 'Unit')) $hosts++;
            elseif (_SWUBotLvHostkeepIsUpgrade($h)) $ups[$j] = _SWUBotSeatCost($seat, $h);
        }
        $surplus = count($ups) - $hosts;
        if ($surplus <= 0) return null;
        asort($ups);
        return in_array($i, array_slice(array_keys($ups), 0, $surplus), true) ? $keep - SWU_BOT_LV_HOSTKEEP_SURPLUS : null;
    },
]);
