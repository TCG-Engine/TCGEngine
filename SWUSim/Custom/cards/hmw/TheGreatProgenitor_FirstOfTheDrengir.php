<?php
// HMW_067
// Cost 5 - The Great Progenitor, First of the Drengir - [Vigilance][Villainy] - Unit (Ground) 4/7 - Unique
// Trait: Creature
// Text: When Attack Ends: You may give a Weakness token to this unit. If you do, create a Beast token for
//       each Weakness token on this unit.

// "When Attack Ends" fires even when the unit died in its own attack (CR 7.6.16.c) — that trigger arrives
// with an EMPTY mzID (CollectAfterAttackTriggers' dead-attacker path). With no unit there is nothing to give
// a token to, so the "you may" could only fizzle and is not offered at all.
// The recipient is fixed ("this unit"), so the offer is a YESNO, and the unit rides the continuation by
// UniqueID (serialized in the CUSTOM param, so it survives the request boundary).
$onAttackEndAbilities["HMW_067:0"] = function ($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $self = ($mzID !== '' && $mzID !== null) ? GetZoneObject($mzID) : null;
    if (SWUObjGone($self) || ($self->CardID ?? '') !== 'HMW_067') return;
    $uid = intval($self->UniqueID ?? 0);
    if ($uid <= 0) return;
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1,
        tooltip: 'Give_a_Weakness_token_to_The_Great_Progenitor_and_create_a_Beast_token_for_each_Weakness_token_on_it?');
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "HMW_067#0|{$uid}", 1);
};

// YES → give the Weakness, then create one Beast per Weakness token on the unit.
// ⚠ COUNT BEFORE THE STATE CHECK. The new token can drop the Progenitor to 0 remaining HP, and the sweep
// then defeats it. The token WAS given ("If you do" is met), and "each Weakness token on this unit" is read
// as it stood the instant it left play (CR 11, Last Known Information) — PREVIEW-SET ASSUMPTION, pinned by
// WeaknessDefeatsIt_BeastsStillCreated_LastKnown.
// Weakness is a token KIND: matched by title, so a future reprint of the token counts too.
// The Beasts are ONE create instruction of N (SWUCreateUnitTokens), so ASH_094 Moff Jerjerrod doubles the
// whole batch rather than being offered once per token.
$customDQHandlers["HMW_067#0"] = function ($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    $uid = intval($parts[0] ?? 0);
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    global $playerID; $playerID = intval($player);
    if (DoGiveTokenUpgrade(intval($player), $mz, 'HMW_T02') === '-') return;   // not given → "If you do" fails
    $weak = _SWUHmw067CountWeakness(GetZoneObject($mz));
    // ASH_094 Moff Jerjerrod: the Weakness give is itself a token creation, and if it raised a doubling offer
    // the count must include the doubled token — so count (and create) only once that offer resolves. The
    // count taken here rides along as the last-known value in case the Progenitor is gone by then.
    if (_SWUJerjerrodUpgradeOfferPending(intval($player), 'HMW_T02')) {
        DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "HMW_067#1|{$uid}|{$weak}", 1);
        return;
    }
    // A lethal Weakness defeats the unit as it lands — BEFORE the Beasts exist. This line fixes that ORDER
    // only: SWUCreateUnitTokens runs the same state check after creating (_SWUAfterTokensCreated), so
    // deleting this is measured GREEN — no current board observes the order. Not a load-bearing guard.
    SWUCheckShrinkDefeats();
    if ($weak > 0) SWUCreateUnitTokens(intval($player), 'HMW_T03', $weak);
};

function _SWUHmw067CountWeakness($self): int {
    $weak = 0;
    if (!SWUObjGone($self)) {
        foreach (GetUpgradesOnUnit($self) as $u) {
            if (empty($u->removed) && CardTitle($u->CardID ?? '') === 'Weakness') $weak++;
        }
    }
    return $weak;
}

// The count + Beasts deferred past an ASH_094 Moff Jerjerrod offer on the Weakness give (see HMW_067#0).
// $parts = [progenitorUID, weaknessCountAtTheGive]. Accepted: the doubled Weakness is on the unit now and is
// counted. Progenitor gone meanwhile: fall back to the count at the give (last known).
$customDQHandlers["HMW_067#1"] = function ($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    $weak = $mz !== null ? _SWUHmw067CountWeakness(GetZoneObject($mz)) : intval($parts[1] ?? 0);
    SWUCheckShrinkDefeats();
    if ($weak > 0) SWUCreateUnitTokens(intval($player), 'HMW_T03', $weak);
};
