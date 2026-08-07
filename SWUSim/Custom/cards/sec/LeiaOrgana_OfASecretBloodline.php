<?php
// SEC_004
// Cost 6 - Leia Organa - Of A Secret Bloodline - [Vigilance,Heroism] - Power 4 - HP 7
// Text: Action [1 resource, Exhaust]: Disclose Vigilance, Command, Aggression, Cunning, or Heroism (reveal a card from your hand with this aspect icon). If you do, give an Experience token to a unit that doesn't share an aspect with the disclosed card.
// DeployText: On Attack: You may disclose Vigilance, Command, Aggression, Cunning, or Heroism. If you do, give an Experience token to a unit that doesn't share an aspect with the disclosed card.
// Epic Action: If you control 6 or more resources, deploy this leader.

// SEC_004 Leia Organa (deployed) — On Attack: You may disclose an aspect → give an Experience token to a
// unit that doesn't share an aspect with the disclosed card. MZMAYCHOOSE is safe in OnAttack; the Exp
// pick runs from a CUSTOM continuation. Combat owns the After Action (closeAction=0).
$onAttackAbilities["SEC_004:0"] = function($player, $mzID) {
    LeiaOrganaOfASecretBloodlineOffer(intval($player), true, 0);
};

$leaderAbilities["SEC_004"] = function(int $player): void {
    global $playerID; $playerID = $player;
    LeiaOrganaOfASecretBloodlineOffer($player, false, 1);   // mandatory disclose; leader owns the close
};

// Disclosed-card chosen → give an Experience token to a unit not sharing an aspect with it.
$customDQHandlers["SEC_004#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $closeAction = intval($parts[0] ?? 0);
    $mz = $lastDecision ?? '';
    if ($mz === '' || $mz === '-' || $mz === 'PASS') { if ($closeAction) SWUAfterAction(intval($player)); return; }
    $c = str_contains($mz, '-') ? GetZoneObject($mz) : null;
    if (SWUObjGone($c)) { if ($closeAction) SWUAfterAction(intval($player)); return; }
    AddGameLogEntry('DISCLOSE', 'P' . intval($player) . ' discloses ' . GameLogCardRef($c->CardID));
    $disclosedAspects = SWUCardAspectIcons($c->CardID);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $umz) {
            $o = GetZoneObject($umz);
            if (SWUObjGone($o)) continue;
            if (!empty(array_intersect(SWUCardAspectIcons($o->CardID), $disclosedAspects))) continue; // shares → out
            $targets[] = $umz;
        }
    }
    if (empty($targets)) { if ($closeAction) SWUAfterAction(intval($player)); return; }  // no valid recipient
    SWUQueueChooseTarget(intval($player), $targets,
        "Give_an_Experience_token_to_a_unit_that_doesn't_share_an_aspect", "GIVE_EXPERIENCE|1");
    if ($closeAction) SWUQueueAfterAction(intval($player));
};

// Shared disclose→Experience offer for both the leader Action and the deployed On Attack.
// $may = "you may disclose" (deploy) vs mandatory (leader); $closeAction = call SWUAfterAction when done
// (leader owns the close; the deployed On Attack rides combat).
function LeiaOrganaOfASecretBloodlineOffer(int $player, bool $may, int $closeAction): void {
    global $playerID; $playerID = $player;
    $cards = _SWUSec004DiscloseableHand($player);
    if (empty($cards)) { if ($closeAction) SWUAfterAction($player); return; }
    $h = "SEC_004#0|{$closeAction}";
    if ($may) {
        SWUQueueMayChooseTarget($player, $cards, "Disclose_an_aspect?", "Choose_a_card_to_disclose", $h);
    } else {
        SWUQueueChooseTarget($player, $cards, "Disclose_an_aspect", $h);
    }
}
