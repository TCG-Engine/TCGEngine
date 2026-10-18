<?php
// LEVER 'darksaberaspects' (theme 'engines', ASH lever hunt G3-24). Off by default: '@try-darksaberaspects' / '@try-engines'.
// ASH_135 The Darksaber: "Attached unit gains: 'While you are paying costs, this unit provides its aspect icons.'" Leia Splash runs it
// in 100% of lists (2.9 copies) yet resourced it 52% / played 29%, mostly onto mono-aspect hosts; the off-aspect cards it would unlock
// (Zeb 5→7, Hera 3→5, Kanan 4→6 for Leia) sat at 44-51% resourced. Valued as 'aspectwaiver' values a waiver: the aspect penalty it
// removes from the cards in hand (castable within two rounds once cheaper), W['develop'] per resource, on the host whose icons remove
// the most (a unique non-Vehicle unit of mine; a deployed leader's icons already count). The same figure keeps it out of the resource
// pile (+50, the key-card magnitude) while such cards are held, and the host pick takes that unit.

// Greedy pip match, as SWUAspectPenalty does it: 2 per pip of $need that $provided cannot cover.
function _SWUBotLvDarksaberaspectsPenalty(array $need, array $provided): int {
    $pen = 0;
    foreach ($need as $a) {
        $k = array_search($a, $provided, true);
        if ($k === false) $pen += 2; else array_splice($provided, $k, 1);
    }
    return $pen;
}
function _SWUBotLvDarksaberaspectsIcons(string $cid): array {
    return array_values(array_filter(array_map('trim', explode(',', strval(CardAspect($cid) ?? ''))), fn($a) => $a !== ''));
}
// The resources of penalty the icons of $hostCid would take off the hand (ASH_135 copies excluded).
function _SWUBotLvDarksaberaspectsUnlock(int $seat, string $hostCid): int {
    $provided = PlayerAspects($seat);
    $host = _SWUBotLvDarksaberaspectsIcons($hostCid);
    $horizon = SWUResourceCount($seat) + 2;
    $total = 0;
    foreach (_SWUBotHandCards($seat) as $cid) {
        if ($cid === 'ASH_135') continue;
        $now = intval(SWUAspectPenalty($seat, $cid));
        if ($now <= 0) continue;
        $need = _SWUBotLvDarksaberaspectsIcons($cid);
        $cut = min($now, _SWUBotLvDarksaberaspectsPenalty($need, $provided) - _SWUBotLvDarksaberaspectsPenalty($need, array_merge($provided, $host)));
        if ($cut > 0 && intval(CardCost($cid)) + $now - $cut <= $horizon) $total += $cut;
    }
    return $total;
}
function _SWUBotLvDarksaberaspectsHostOk(array $u): bool {
    return empty($u['isLeader']) && isset($u['obj']) && !TraitContains($u['obj'], 'Vehicle') && CardUnique($u['cardID'])
        && !_SWUUnitHasUpgrade($u['obj'], 'ASH_135');
}
// The best host's unlock now; with no host on board, the best unique non-Vehicle unit in hand stands in (for the keep).
function _SWUBotLvDarksaberaspectsBest(int $seat, bool $handHosts): int {
    $best = 0;
    foreach (SWUBotUnits($seat) as $u) if (_SWUBotLvDarksaberaspectsHostOk($u)) $best = max($best, _SWUBotLvDarksaberaspectsUnlock($seat, $u['cardID']));
    if ($handHosts) foreach (_SWUBotHandCards($seat) as $cid) {
        if (str_contains(strval(CardType($cid)), 'Unit') && CardUnique($cid) && !HasTrait($cid, 'Vehicle')) $best = max($best, _SWUBotLvDarksaberaspectsUnlock($seat, $cid));
    }
    return $best;
}

SWUBotLeverRegister('engines', 'darksaberaspects', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($cid !== 'ASH_135') return null;
        $n = _SWUBotLvDarksaberaspectsBest($seat, false);
        return $n > 0 ? $v + floatval($W['develop']) * $n : null;
    },
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if ($cid !== 'ASH_135' || _SWUBotLvDarksaberaspectsBest($seat, true) < 2) return null;
        return $keep + 50.0;
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (!str_starts_with($tip, 'Choose_a_unit_to_attach_The_Darksaber') || !preg_match('/^(my|their|p\d+|team)\w*Arena-\d+$/', $c)) return null;
        if (!str_starts_with($c, 'my')) return -1.0;
        $u = SWUBotViewForMz($seat, $c);
        if ($u === null) return null;
        $n = (_SWUBotLvDarksaberaspectsHostOk($u) && CardUnique($u['cardID'])) ? _SWUBotLvDarksaberaspectsUnlock($seat, $u['cardID']) : 0;
        return ($n > 0 ? 10.0 + $n : 0.5) + 0.01 * intval($u['power']);
    },
]);
