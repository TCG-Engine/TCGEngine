<?php
// HMW_057
// Cost 5 - Boss Lyonie, Hypnotized - [Cunning][Vigilance] - Unit (Ground) 5/5 - Traits: Gungan, Official
// unique
// Text: When Played/On Attack: You may choose a token upgrade attached to another unit. Give another one of
//       those tokens to that unit.
//
// One closure on both windows. The pick is the TOKEN itself, offered on its host as "<host>.u<sub>" (the
// subcard address JTL_242 Shuttle ST-149 uses), as a MAY-choose — so a lone candidate still prompts.
//   • "ANOTHER unit" — every unit on the table but Lyonie, excluded by UniqueID. Unqualified, so enemy and
//     teammate units count: built from SWUAllUnits() (team + their), NOT JTL_242's my + their scan, which in
//     a team game skips the teammate.
//   • "A TOKEN UPGRADE" — _SWUUpgradeMatchesMoveFilter($sub, 'token'): the Token Upgrade card type, never a
//     real upgrade, a pilot or a captive.
//   • "ANOTHER ONE OF THOSE TOKENS" — the same KIND, dispatched by title to the primitive that gives it
//     (Shield / Experience / Advantage have their own; anything else, e.g. HMW_T02 Weakness, goes through
//     DoGiveTokenUpgrade). By title, not CardID, because every set reprints the tokens (SHD_T02, LOF_T02, …
//     are all Shields). Each primitive stamps SWU_GAVE_TOKEN_UPGRADE (HMW_005 Jar Jar's condition).
//   • A Weakness can take its host to 0 HP, so the give is followed by the shrink sweep (as GIVE_WEAKNESS).
if (!function_exists('_SWUHmw057TokenPool')) {
    function _SWUHmw057TokenPool(int $player, int $selfUID): array {
        global $playerID;
        $playerID = $player;
        $out = [];
        foreach (SWUAllUnits() as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o) || !is_array($o->Subcards ?? null)) continue;
            if ($selfUID > 0 && intval($o->UniqueID ?? 0) === $selfUID) continue;
            foreach ($o->Subcards as $i => $sub) {
                if (_SWUUpgradeMatchesMoveFilter($sub, 'token')) $out[] = $mz . '.u' . $i;
            }
        }
        return $out;
    }
}

if (!function_exists('_SWUGiveTokenOfKind')) {
    // Give $hostMz one more token of the same KIND as $tokenCardID.
    function _SWUGiveTokenOfKind(int $player, string $hostMz, string $tokenCardID): void {
        switch ((string)CardTitle($tokenCardID)) {
            case 'Shield':     DoGiveShieldToken($player, $hostMz);     break;
            case 'Experience': DoGiveExperienceToken($player, $hostMz); break;
            case 'Advantage':  DoGiveAdvantageToken($player, $hostMz);  break;
            default:           DoGiveTokenUpgrade($player, $hostMz, $tokenCardID); break;
        }
    }
}

$whenPlayedAbilities["HMW_057:0"] = $onAttackAbilities["HMW_057:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = ($mzID !== '' && $mzID !== null) ? GetZoneObject($mzID) : null;
    $selfUID = SWUObjGone($self) ? 0 : intval($self->UniqueID ?? 0);
    $pool = _SWUHmw057TokenPool(intval($player), $selfUID);
    if (empty($pool)) return;
    DecisionQueueController::AddDecision(intval($player), "MZMAYCHOOSE", implode('&', $pool), 1,
        tooltip: "Choose_a_token_upgrade_on_another_unit_(that_unit_gets_another_one)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_057#0", 1);
    // Leave $playerID set: MZCountChoices validates the relative-frame specs next, under it.
};

$customDQHandlers["HMW_057#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $sub = MZParseSubcardID((string)$lastDecision);
    if ($sub === null) return;
    $host = GetZoneObject($sub['host']);
    if (SWUObjGone($host) || !is_array($host->Subcards ?? null)) return;
    $sc = $host->Subcards[intval($sub['subIndex'])] ?? null;
    if ($sc === null || !_SWUUpgradeMatchesMoveFilter($sc, 'token')) return;
    $cid = is_array($sc) ? ($sc['CardID'] ?? '') : ($sc->CardID ?? '');
    _SWUGiveTokenOfKind(intval($player), $sub['host'], (string)$cid);
    SWUCheckShrinkDefeats();
};
