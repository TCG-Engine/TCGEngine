<?php
// HMW_077
// Cost 4 - Boss Nass, Otoh Gunga Boss - [Vigilance][Heroism] - Unit (Ground) 4/6 - unique
// Traits: Gungan, Official
// Text: When Played/On Attack: You may defeat a Shield token on a friendly Gungan unit.
//       If you do, create a Beast token and give a Shield token to it.
//
// TWO trigger windows on one shared closure so they cannot drift apart.
//
// TARGET POOL — three restrictions, each independently guarded in the test file:
//   • FRIENDLY  → side 'my' (an enemy shielded Gungan is not a legal target)
//   • GUNGAN    → traits ['Gungan'], read through TraitContains so an upgrade- or effect-granted
//                 Gungan trait counts and a per-instance trait LOSS is honoured
//   • has a Shield token to defeat → extraFilter. Without this the ability could be offered when it
//                 can only fizzle; with it, an unshielded board simply raises no prompt at all.
// There is no "another", so Boss Nass — himself a Gungan — is a legal target for his own ability.
//
// ⚠ MZMAYCHOOSE (may => true) is also what makes the ON ATTACK half safe: a MANDATORY multi-target
// SWUQueueChooseTarget queued directly in an OnAttack closure auto-resolves to nothing, because
// OnAttackTrigger restores $playerID before MZCountChoices runs. A may-choose is the proven
// in-combat form.
$whenPlayedAbilities["HMW_077:0"] = $onAttackAbilities["HMW_077:0"] = function ($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), (string)$mzID, [
        'continuation' => 'HMW_077#0',
        'may'          => true,
        'side'         => 'my',          // "a FRIENDLY Gungan unit"
        'traits'       => ['Gungan'],
        'extraFilter'  => fn($o) => _SWUCountShieldSubcards($o) > 0,
        'prompt'       => 'Defeat_a_Shield_token_on_a_friendly_Gungan_to_create_a_shielded_Beast',
    ]);
};

// "If you do" — measure the OUTCOME, never assume the attempt worked. SWUConsumeShieldToken returns
// whether a token was actually removed, so a shield that vanished between the offer and this
// continuation correctly produces no Beast.
//
// $forPrevention = false: this DEFEATS the token as an effect rather than consuming it to prevent
// damage, so SEC_046 Galen Erso naming "Shield" (which blanks the prevention ability, not the token's
// existence) must not block it. See the note on SWUConsumeShieldToken.
//
// ⚠ The Shield rider on the Beast goes through the BATCH create API's $upgradeToken parameter, NOT a
// DoGiveShieldToken stamped on the returned UID. ASH_094 Moff Jerjerrod's "create twice that number
// instead" makes its extra token later, inside its own decision handler; a rider applied at this call
// site would miss it and the doubled Beast would arrive bare (the TS26_14 / TS26_55 bug shape).
$customDQHandlers["HMW_077#0"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $unit = GetZoneObject($lastDecision);
    if (SWUObjGone($unit)) return;
    if (!SWUConsumeShieldToken($unit, false)) return;      // "If you do" — nothing defeated, no Beast
    SWUCreateUnitTokens(intval($player), 'HMW_T03', 1, false, '', 'SHIELD');
};
