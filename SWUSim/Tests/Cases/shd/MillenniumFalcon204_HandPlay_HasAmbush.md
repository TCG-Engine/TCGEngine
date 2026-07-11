# SHD_204 Millennium Falcon (6-cost 5/5 space) — "If you play this unit from your hand, it gains
# Ambush." Played from hand with an enemy space unit present: the Ambush entry trigger fires
# (YESNO → YES) and she readies + attacks, defeating the TIE/ln (5 ≥ 1; counter 2).

## GIVEN
CommonSetup: gyw/gyw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_204
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:EXHAUSTED
