<?php
// TWI_069
// Roger Roger - [Vigilance] - Upgrade
// Text: When Defeated: Attach this upgrade to a friendly Battle Droid token.

// TWI_069 Roger Roger — "When Defeated: Attach this upgrade to a friendly Battle Droid token."
//
// Fired from _SWUOnUpgradeDefeated — the one point every upgrade defeat is announced, whatever caused it: the
// upgrade defeated directly (Confiscate), or its host leaving play by defeat, bounce, capture, a token ceasing,
// or being put into its owner's deck (all of those defeat the host's upgrades, CR 9.3 / 8.34.1). By then Roger
// Roger is in its owner's discard; the ability attaches it FROM there.
//
//   • MANDATORY — there is no "may". With no friendly Battle Droid token it simply stays in the discard.
//   • "a friendly Battle Droid token" is ANY printing (SWUIsBattleDroidToken — TWI_T01 and Twin Suns' TS26_T01),
//     and "friendly" is the controller's team in Team Suns.
//   • The leaving host is excluded by UniqueID (it may be a Battle Droid itself, same CardID as the receiver).
//   • With more than one candidate the controller CHOOSES which. That choice is offered from a queued step, not
//     here: this runs while the host is still in its arena, and an mzID minted now would point one slot too far
//     once the host is compacted out — the prompt would ring the wrong droids.

// Friendly Battle Droid tokens (by UniqueID) that can receive Roger Roger.
function _TWI069Droids(int $controller, int $excludeUID): array {
    $uids = [];
    foreach (array_merge([$controller], SWUTeammatesOf($controller)) as $seat) {
        foreach (array_merge(GetGroundArena($seat), GetSpaceArena($seat)) as $u) {
            if (SWUObjGone($u) || !SWUIsBattleDroidToken(strval($u->CardID ?? ''))) continue;
            $uid = intval($u->UniqueID ?? 0);
            if ($uid > 0 && $uid !== $excludeUID) $uids[] = $uid;
        }
    }
    return $uids;
}

// Move Roger Roger from its owner's discard onto the droid with this UniqueID. A no-op if it has already left
// the discard, or the droid is gone — the ability can only move the card from where it is.
function _TWI069Attach(int $controller, int $owner, int $droidUID): void {
    global $playerID;
    $saved = $playerID;
    $playerID = $controller;
    $mz = SWUFindMzByUID($droidUID);
    $droid = $mz !== null ? GetZoneObject($mz) : null;
    if ($droid === null || SWUObjGone($droid)) { $playerID = $saved; return; }
    $disc = &GetDiscard($owner);
    $at = -1;
    for ($i = count($disc) - 1; $i >= 0; $i--) {          // the copy that was just defeated is the latest
        if (empty($disc[$i]->removed) && ($disc[$i]->CardID ?? '') === 'TWI_069') { $at = $i; break; }
    }
    if ($at < 0) { unset($disc); $playerID = $saved; return; }
    array_splice($disc, $at, 1);
    unset($disc);
    if (!is_array($droid->Subcards ?? null)) $droid->Subcards = [];
    $droid->Subcards[] = (object)['CardID' => 'TWI_069', 'Owner' => $owner, 'Controller' => $controller,
        'TurnEffects' => [], 'IsPilot' => false, 'IsCaptive' => false];
    if (function_exists('AddGameLogEntry') && function_exists('SWULogObjRef')) {
        SWULogWithoutSource(fn() => AddGameLogEntry('EFFECT',
            "P{$controller}'s [[TWI_069|Roger Roger]] was attached to " . SWULogObjRef($droid)));
    }
    $playerID = $saved;
}

$upgradeWhenDefeatedAbilities['TWI_069'] = function (int $controller, $hostObj, int $owner): void {
    $hostUID = intval($hostObj->UniqueID ?? 0);
    $droids  = _TWI069Droids($controller, $hostUID);
    if (count($droids) === 0) return;                                   // stays in its owner's discard
    if (count($droids) === 1) { _TWI069Attach($controller, $owner, $droids[0]); return; }   // no choice to make
    DecisionQueueController::AddDecision($controller, 'CUSTOM', "TWI_069#offer|{$owner}|{$hostUID}", 1);
};

// Runs once the host has left play: rebuild the candidates at their CURRENT positions and make the controller
// pick one. Mandatory MZCHOOSE — the server refuses a pass on it (SWUValidateDecisionAnswer).
$customDQHandlers['TWI_069#offer'] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $owner   = intval($parts[0] ?? $player);
    $droids  = _TWI069Droids(intval($player), intval($parts[1] ?? 0));
    if (count($droids) === 0) return;
    if (count($droids) === 1) { _TWI069Attach(intval($player), $owner, $droids[0]); return; }
    $mzs = [];
    foreach ($droids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null) $mzs[] = $mz; }
    SWUQueueChooseTarget(intval($player), $mzs, 'Choose_a_friendly_Battle_Droid_token_to_attach_Roger_Roger_to',
        "TWI_069#attach|{$owner}");
};

$customDQHandlers['TWI_069#attach'] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $droid = GetZoneObject((string)$lastDecision);
    if ($droid === null || SWUObjGone($droid)) return;
    _TWI069Attach(intval($player), intval($parts[0] ?? $player), intval($droid->UniqueID ?? 0));
};
