<?php
// LEVER 'shieldsaveonly' (theme 'pricing', G2-31 mandalorian-pops-his-shield-only-to-save-a-unit). ASH_062 The Mandalorian ("If damage would be
// dealt to another friendly unit, you may defeat a Shield token on this unit. If you do, prevent that damage") — Leia 92% of lists, Luke ASH 67%,
// Hera 43%: "Defeat_a_Shield_on_The_Mandalorian_to_prevent_…" was answered YES 672/672 (combat) and 278/278 (non-combat) — YES is listed first.
// decision (YESNO, continuations ASH062_PREVENT_COMBAT|uid and ASH062_PREVENT_ABILITY|uid|amount|…) — YES only when the damage would defeat
//        the protected unit, is 3 or more, or brings it into a ready enemy attacker's reach in its arena (and its own Shield does not already
//        stop it); otherwise NO keeps the Shield on Mando.

function _SWUBotLvShieldsaveonlyCombatDamage(int $uid): int {
    if (!function_exists('GetSWUVar')) return 0;
    $atk = intval(GetSWUVar('SWU_CURRENT_ATTACKER_UID', '0'));
    if ($uid > 0 && $uid === $atk) {
        $d = 0;
        foreach (function_exists('SWUCurrentDefenderMzIDs') ? SWUCurrentDefenderMzIDs() : [] as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $d += max(0, intval(ObjectCurrentPower($o)));
        }
        return $d;
    }
    if ($atk <= 0 || ($mz = SWUFindMzByUID($atk)) === null) return 0;
    $a = GetZoneObject($mz);
    return ($a === null || !empty($a->removed)) ? 0 : max(0, intval(function_exists('ObjectCurrentPowerInAttack') ? ObjectCurrentPowerInAttack($a) : ObjectCurrentPower($a)));
}

SWUBotLeverRegister('pricing', 'shieldsaveonly', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if ($type !== 'YESNO' || ($head !== 'ASH062_PREVENT_COMBAT' && $head !== 'ASH062_PREVENT_ABILITY')) return null;
        if ($c !== 'YES' && $c !== 'NO') return null;
        $parts = explode('|', strval(($ctx['following'] ?? [])[0] ?? ''));
        $uid = intval($parts[1] ?? 0);
        $prot = null;
        foreach (SWUBotFriendlySeats($seat) as $f) foreach (SWUBotUnits($f) as $u) if ($u['uid'] === $uid) { $prot = $u; break 2; }
        if ($prot === null) return null;
        $amt = $head === 'ASH062_PREVENT_ABILITY' ? intval($parts[2] ?? 0) : _SWUBotLvShieldsaveonlyCombatDamage($uid);
        $rem = intval($prot['remaining']);
        $reach = 0;
        foreach (SWUBotEnemyUnits($seat) as $e) if (!empty($e['ready']) && $e['arena'] === $prot['arena']) $reach = max($reach, intval($e['attackPower']));
        $worth = intval($prot['shields']) === 0
            && ($amt >= $rem || $amt >= 3 || ($rem > $reach && $rem - $amt <= $reach));
        if ($c === 'NO') return 0.0;
        return $worth ? 1.0 : -1.0;
    },
]);
