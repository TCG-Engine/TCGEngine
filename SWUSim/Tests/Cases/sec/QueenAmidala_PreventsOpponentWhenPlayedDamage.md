# SEC_101 Queen Amidala — OPPONENT WHEN-PLAYED (ability damage). P1 plays Death Trooper (SEC_030, "When
#   Played: deal 2 to a friendly ground unit and 2 to an enemy ground unit"); it deals 2 to itself, then
#   2 to P2's Amidala. The enemy-damage funnel defers and offers P2 the prevention; P2 defeats its
#   Official SEC_118 → Amidala takes 0. Same SWUDealDamageToUnit path as the event case.

## GIVEN
CommonSetup: brk/ggw/{myResources:7;handCardIds:SEC_030}
P1OnlyActions: true
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_118:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
