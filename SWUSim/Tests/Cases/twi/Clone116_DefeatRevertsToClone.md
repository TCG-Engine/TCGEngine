# TWI_116 Clone — a Clone copy leaves play as the REAL card (TWI_116), not the card it copied (the
# printed copy only exists while in play). Clone copies an enemy SOR_095 (3/3); then P1's Open Fire
# (SOR_172, "Deal 4 damage to a unit") targets the Clone → 4 ≥ 3 HP → defeated. It goes to P1's discard
# as TWI_116 (Clone), NOT as SOR_095. The enemy's original SOR_095 is untouched.
## GIVEN
CommonSetup: rrk/bbw/{myResources:16;handCardIds:TWI_116,SOR_172}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:SOR_172
P1DISCARDUNIT:1:CARDID:TWI_116
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
