<?php
// Exact Armory Deck list. Decisions use our hand, public zones and offered choices.
function FaBIsMaxxBot(int $p): bool { return (FaBGetState()['botProfiles'][(string)$p] ?? '') === 'maxx'; }
function FaBMaxxDrivers(int $p): int { return count(FaBMONArena($p, 'hyper_driver')); }
function FaBMaxxFollowup(int $p, int $exclude = 0): bool {
    foreach (FaBChoiceRefs($p, 'Hand') as $ref) {
        $o = FaBIdentityFromMZ($ref)['object'];
        if (intval($o->UniqueID) !== $exclude && FaBHasKeyword($o, 'Boost')
            && FaBCardCost($o, $p) <= FaBAvailablePitch($p) - intval(CardPitch($o->CardID))) return true;
    }
    return false;
}
function FaBMaxxKeepValue(object $o, int $p): float {
    $base = FaBWTRBase($o->CardID);
    if ($base === 'construct_bank_breaker') return FaBMaxxDrivers($p) >= 2 ? 13 : 4;
    if ($base === 'hyper_driver') return 7 + (4 - intval(CardPitch($o->CardID)));
    if ($base === 'clamp_press') return 9;
    if ($base === 'twintek_charging_station') return 6;
    if (FaBHasType($o, 'Equipment')) return $o->CardID === 'puffer_jacket' ? 8 : 4;
    return floatval(CardPower($o->CardID)) - FaBCardCost($o, $p) + (FaBHasKeyword($o, 'Boost') ? 3 : 0);
}
function FaBMaxxPlayScore(int $p, object $o, string $zone): float {
    if ($p !== intval(GetTurnPlayer())) return -100;
    $base = FaBWTRBase($o->CardID);
    if ($base === 'construct_bank_breaker') {
        if (FaBMaxxDrivers($p) < 3 || FaBAMXWrenches($p) === '') return -100;
        foreach (FaBChoiceRefs($p, 'Weapons', ['base'=>'bank_breaker']) as $ref)
            if (count(FaBEVOUnder(FaBIdentityFromMZ($ref)['object'])) >= 2) return -100;
        return 90;
    }
    if ($base === 'hyper_driver') return FaBMaxxDrivers($p) < 3 ? 55 - intval(CardPitch($o->CardID)) : 18;
    if ($base === 'clamp_press') return FaBAMXWrenches($p) !== '' ? 48 : -100;
    if ($base === 'twintek_charging_station') return FaBMaxxFollowup($p, intval($o->UniqueID)) ? 65 : -100;
    if (FaBWTRIsAttackAction($o)) return 30 + FaBMaxxKeepValue($o, $p) + ($base === 'expedite' && FaBMaxxDrivers($p) < 3 ? 3 : 0);
    return -100;
}
function FaBMaxxAbilityScore(int $p, object $o): float {
    if ($p !== intval(GetTurnPlayer())) return -100;
    if (in_array($o->CardID, ['maxx_nitro','maxx_the_hype_nitro'], true)) return FaBMaxxDrivers($p) < 3 || !FaBEVOEffect($p, 'CRANKED') ? 70 : 15;
    if ($o->CardID === 'bank_breaker') return FaBEVOUnder($o) ? 80 : (FaBMaxxFollowup($p) ? 5 : 40);
    if ($o->CardID === 'banksy') return FaBMaxxFollowup($p) && intval(GetActionPoints($p)) < 2 ? 5 : 40;
    return -100;
}
function FaBMaxxBlockScore(int $p, object $o, string $zone): float {
    $s = FaBGetState();
    $damage = max(0, FaBAttackPower($s) - FaBDefenseValue($s, $p));
    $defense = FaBCurrentDefense($o, $p);
    if (!$damage || $defense <= 0) return -100;
    $lethal = $damage >= intval(GetHealth($p));
    // Small hits are cheaper than giving up the boost/driver hand for our turn.
    if ($zone === 'Hand' && !$lethal && $damage <= 5 && GetHealth($p) > 12) return -100;
    $score = min($damage, $defense) * ($lethal ? 20 : 3) - FaBMaxxKeepValue($o, $p);
    if ($o->CardID === 'drive_brake') $score += 3;
    if ($o->CardID === 'puffer_jacket' && !$lethal) $score -= 6;
    return $score + (GetHealth($p) < 12 ? 3 : 0);
}
function FaBMaxxWantsBoost(int $p): bool {
    if (count(FaBChoiceRefs($p, 'Deck')) <= 3) return false;
    $s = FaBGetState();
    $budget = FaBAvailablePitch($p) - intval($s['pendingPayment']['cost'] ?? 0);
    foreach (FaBMONArena($p, 'hyper_driver') as $ref) {
        $o = FaBIdentityFromMZ($ref)['object'];
        if (intval(FaBObjectCounters($o)['ARC_DRIVER_TURN'] ?? -1) !== intval(GetTurnNumber())) ++$budget;
    }
    foreach (FaBChoiceRefs($p, 'Hand') as $ref) {
        $o = FaBIdentityFromMZ($ref)['object'];
        if (FaBHasType($o, 'Action') && FaBMaxxPlayScore($p, $o, 'Hand') > 0
            && FaBCardCost($o, $p) <= $budget - intval(CardPitch($o->CardID))) return true;
    }
    $hero = GetHero($p)[0] ?? null;
    if ($hero && !HasNoAbilities($hero) && $budget >= 2
        && intval(FaBObjectCounters($hero)['ARC_USED_0'] ?? -1) !== intval(GetTurnNumber())) return true;
    if ($budget >= 1 && FaBEVOEffect($p, 'CRANKED')) {
        foreach (FaBChoiceRefs($p, 'Weapons') as $ref) if (FaBCRUWeaponReady(FaBIdentityFromMZ($ref)['object'])) return true;
    }
    return false;
}
function FaBMaxxChoice(int $p, object $d): ?string {
    $tip = (string)$d->Tooltip;
    if ($d->Type === 'MZMODAL') {
        if ($tip === 'Banish_top_card_to_boost') {
            // Never inspect deck order, even though it is present in server memory.
            return FaBMaxxWantsBoost($p) ? '0' : '1';
        }
        if ($tip === 'Maintain_item') return '0';
        if ($tip === 'Remove_steam_to_crank') return '0';
    }
    $multi = $d->Type === 'MZMULTICHOOSE';
    if (!$multi && !in_array($d->Type, ['MZCHOOSE','MZMAYCHOOSE'], true)) return null;
    $parts = $multi ? explode('|', $d->Param, 3) : [];
    $refs = array_values(array_filter(explode('&', $multi ? ($parts[2] ?? '') : $d->Param), fn($ref)=>FaBIdentityFromMZ($ref) !== null));
    if (!$refs) return null;
    if ($tip === 'Discard_Hyper_Driver_for_defense') {
        $s = FaBGetState();
        if (FaBAttackPower($s) <= FaBDefenseValue($s, $p)) return 'PASS';
    }
    $score = function(string $ref) use ($p, $tip): float {
        $f = FaBIdentityFromMZ($ref); $o = $f['object'];
        if (str_contains(strtolower($tip), 'pitch')) return 6 * intval(CardPitch($o->CardID)) - FaBMaxxKeepValue($o, $p);
        if ($tip === 'Transform_three_Hyper_Drivers') return -intval(FaBObjectCounters($o)['STEAM'] ?? 0);
        if ($tip === 'Banish_Bank_Breaker_material') return FaBHasType($o, 'Token') ? 100 : (FaBHasType($o, 'Weapon') ? 90 : intval(CardPitch($o->CardID)));
        if (str_contains($tip, 'steam') || str_contains($tip, 'Steam')) return -intval(FaBObjectCounters($o)['STEAM'] ?? 0) + (FaBWTRBase($o->CardID) === 'clamp_press' ? 4 : 0);
        if ($tip === 'Discard_Hyper_Driver_for_defense') return intval(CardPitch($o->CardID));
        if ($tip === 'Empower_wrench' || $tip === 'Choose_wrench_to_transform') return $o->CardID === 'bank_breaker' ? 10 : 0;
        return FaBMaxxKeepValue($o, $p);
    };
    if (in_array($tip, ['Transform_three_Hyper_Drivers','Banish_Bank_Breaker_material','Discard_Hyper_Driver_for_defense','Empower_wrench','Choose_wrench_to_transform','Recycle_Hyper_Driver'], true)
        || str_contains(strtolower($tip), 'pitch') || str_contains(strtolower($tip), 'steam') || str_contains(strtolower($tip), 'item')) {
        usort($refs, fn($a,$b)=>$score($b)<=>$score($a));
        return $multi ? implode('&', array_slice($refs, 0, intval($parts[1]))) : $refs[0];
    }
    return null;
}
