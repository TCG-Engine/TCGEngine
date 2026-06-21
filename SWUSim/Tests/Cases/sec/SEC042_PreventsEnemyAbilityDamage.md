# SEC_042 (Ground, 2/2) — If an enemy card ability would deal damage to this unit, prevent 2. SEC_042
#   is on P2's side; P1 plays SEC_152 (When Played: deal 2 to a ready unit) targeting it → the 2 is
#   prevented down to 0 damage.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SEC_042:1:0
WithP1Hand: SEC_152

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
