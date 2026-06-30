# LOF_262 Go Into Hiding — Choose a unit; it can't be attacked this phase. P1 protects Plo Koon, so P2's
# attempt to attack him deals no damage.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_262}
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
