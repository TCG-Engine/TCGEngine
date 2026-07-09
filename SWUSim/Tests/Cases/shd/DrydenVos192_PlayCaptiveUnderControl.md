# SHD_192 Dryden Vos (7-cost, Cunning/Villainy ground) — Shielded + "When Played: Choose a captured card
# guarded by a unit you control. You may play it for free under your control." P1's Discerning Veteran
# (SHD_120) captures SOR_128; playing Dryden Vos, P1 plays that captive under its own control (P1 now has
# SHD_120, Dryden, and SOR_128). Shielded + WhenPlayed = dual entry trigger → resolve WhenPlayed first.

## GIVEN
CommonSetup: gyk/gyk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SHD_120
WithP1Hand: SHD_192
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:SOR_128
