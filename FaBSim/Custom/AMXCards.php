<?php
// Maxx Armory Deck. Interactive effects are authored in amx_abilities.json.
function FaBAMXWrenches(int $p): string {
    return implode('&', FaBChoiceRefs($p, 'Weapons', ['type'=>'Wrench']));
}
function FaBAMXConstruct(int $p, int $uid, string $wrench, string $drivers): bool {
    $source = FaBFindUID($uid);
    $weapon = FaBIdentityFromMZ($wrench);
    $uids = array_values(array_unique(FaBUPRUIDs($drivers)));
    $valid = $source && $source['player'] === $p && $weapon && $weapon['player'] === $p
        && $weapon['zone'] === 'Weapons' && FaBHasType($weapon['object'], 'Wrench') && count($uids) === 3;
    foreach ($uids as $driverUID) {
        $driver = FaBFindUID($driverUID);
        if (!$driver || $driver['player'] !== $p || !in_array($driver['zone'], ['Arena','Stack'], true)
            || FaBWTRBase($driver['object']->CardID) !== 'hyper_driver') $valid = false;
    }
    if (!$valid) {
        if ($source) FaBMoveUID($uid, 'Graveyard', $p);
        return false;
    }
    $materials = array_merge(FaBEVOUnder($weapon['object']), [$weapon['object']->CardID]);
    foreach ($uids as $driverUID) {
        $driver = FaBFindUID($driverUID)['object'];
        $materials = array_merge($materials, FaBEVOUnder($driver), [$driver->CardID]);
        $driver->removed = true;
    }
    $weapon['object']->removed = true;
    $bank = FaBMoveUID($uid, 'Weapons', $p);
    $bank->CardID = 'bank_breaker';
    $bank->Status = 2;
    $bank->Counters = ['SUBCARDS'=>$materials];
    $bank->TurnEffects = [];
    return true;
}
function FaBAMXBanishMaterial(int $p, int $attackUID, int $weaponUID, string $chosen, string $previews): bool {
    $preview = FaBIdentityFromMZ($chosen);
    $data = $preview ? FaBARCCard(intval($preview['object']->UniqueID), 'evoUnder') : null;
    $weapon = FaBFindUID($weaponUID);
    $paid = false;
    if ($weapon && $weapon['player'] === $p && $weapon['zone'] === 'Weapons' && is_array($data)
        && intval($data[0]) === $weaponUID && in_array($chosen, explode('&', $previews), true)) {
        $cards = FaBEVOUnder($weapon['object']);
        $index = intval($data[1]);
        if (isset($cards[$index])) {
            if (!FaBHasType($cards[$index], 'Token')) AddBanish($p, CardID:$cards[$index], Owner:$p, Controller:$p);
            array_splice($cards, $index, 1);
            FaBEVOCounter($weapon['object'], 'SUBCARDS', $cards);
            FaBTagUID($attackUID, 'OVERPOWER');
            FaBTagUID($attackUID, 'GO_AGAIN');
            $paid = true;
        }
    }
    foreach (explode('&', $previews) as $ref) {
        $f = FaBIdentityFromMZ($ref);
        if ($f && $f['player'] === $p && $f['zone'] === 'Temp') $f['object']->removed = true;
    }
    return $paid;
}
function FaBAMXReady(object $weapon): bool {
    $counters = FaBObjectCounters($weapon);
    return intval($counters['WEAPON_ATTACK_TURN'] ?? -1) !== intval(GetTurnNumber())
        || intval($counters['WEAPON_ATTACKS'] ?? 0) < 2;
}
function FaBAMXEnter(int $p, object $item): void {
    if (FaBWTRBase($item->CardID) === 'hyper_driver' && !FaBHasType($item, 'Token')) {
        foreach (FaBCRUEquipment($p, 'puffer_jacket') as $ref) FaBARCSteam($item, 1);
    }
}
function FaBAMXBoost(int $p, int $banishedUID): void {
    $banished = FaBFindUID($banishedUID);
    if (!$banished || FaBWTRBase($banished['object']->CardID) !== 'hyper_driver') return;
    foreach (FaBCRUEquipment($p, 'drive_brake') as $ref) {
        $o = FaBIdentityFromMZ($ref)['object'];
        FaBSetObjectCounter($o, 'DEFENSE', max(0, intval(FaBObjectCounters($o)['DEFENSE'] ?? 0) - 1));
    }
    foreach (FaBCRUEquipment($p, 'fist_pump') as $ref) {
        FaBRunSourceMacro('ResolveAbility', $p, 'fist_pump', ['mzID'=>$ref]);
    }
}
function FaBAMXPower(int $p, object $attack): int {
    if (!FaBHasType($attack, 'Wrench') || in_array($attack->Role ?? '', ['DEFENSE','DEFENSE_REACTION'], true)) return 0;
    return 2 * count(FaBMONArena($p, 'clamp_press'));
}
function FaBAMXRecycle(int $p, string $ref): void {
    $f = FaBIdentityFromMZ($ref);
    if (!$f || $f['player'] !== $p || $f['zone'] !== 'Graveyard' || FaBWTRBase($f['object']->CardID) !== 'hyper_driver') return;
    FaBMoveUID(intval($f['object']->UniqueID), 'Deck', $p);
    FaBShuffleDeck($p);
    AddResources($p, intval(GetResources($p)) + 1);
}
function FaBAMXHelm(int $p, int $uid, string $ref): void {
    $f = FaBIdentityFromMZ($ref);
    if (!$f || $f['player'] !== $p || $f['zone'] !== 'Hand' || FaBWTRBase($f['object']->CardID) !== 'hyper_driver') return;
    FaBDiscardChoice($p, $ref);
    DoDrawCard($p, 1);
    FaBTagUID($uid, 'WTR_DEFENSE:1');
}
