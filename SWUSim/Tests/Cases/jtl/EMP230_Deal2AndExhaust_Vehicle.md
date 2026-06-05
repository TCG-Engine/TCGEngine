# JTL_230 Electromagnetic Pulse (event) — Deal 2 damage to a Droid or Vehicle unit and exhaust it. The
# only Droid/Vehicle (SOR_237, 2/3) takes 2 and is exhausted; the non-Vehicle SEC_080 is not a legal
# target.

## GIVEN
P1LeaderBase: JTL_016/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_230
WithP1Resources: 1
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:READY
