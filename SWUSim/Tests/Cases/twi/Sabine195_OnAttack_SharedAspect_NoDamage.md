# TWI_195 Sabine Wren — when the discarded card SHARES an aspect with the base (base Cunning; top card
# TWI_205 is Cunning), no damage is dealt.

## GIVEN
CommonSetup: yyw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_195:1:0
WithP1Deck: TWI_205
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1
