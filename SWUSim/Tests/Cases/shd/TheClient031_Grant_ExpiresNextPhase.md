# SHD_031 The Client — the granted Bounty is "for this phase": after the regroup the badge is gone
# (the phase-duration SHD_031 turn-effect token expires centrally at RegroupPhaseStart). The grant
# itself working is proven by TheClient031_Action_GrantsBounty_HealBase.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: SHD_031:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Bounty
