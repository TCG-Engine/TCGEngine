# TWI_256 Hold-Out Blaster — the "may" is optional: declining (AnswerDecision:-) attaches the upgrade but
# deals no damage.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:TWI_256}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
