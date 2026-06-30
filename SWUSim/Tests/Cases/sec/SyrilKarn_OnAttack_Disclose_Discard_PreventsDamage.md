# SEC_133 Syril Karn — the chosen unit's controller discards a card to prevent the 2 damage.
# P2 has exactly 1 card; answers YES → that card is discarded (auto, single card) → SOR_046 takes no damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_133:1:0
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:YES

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1NODECISION
