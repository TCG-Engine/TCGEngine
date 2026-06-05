# JTL_140 IG-2000 — Overwhelm + When Played: Deal 1 damage to each of up to 3 units. P1 picks all three
# enemy units (SOR_095, SEC_080, SOR_237), each taking 1. Also confirms Overwhelm is auto-wired.

## GIVEN
P1LeaderBase: JTL_010/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_140
WithP1Resources: 4
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:CARDID:JTL_140
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm
