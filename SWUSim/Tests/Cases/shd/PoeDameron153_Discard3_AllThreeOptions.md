# SHD_153 Poe Dameron — discard 3 → resolve all 3 distinct options. Poe attacks the base (6 combat).
# On Attack, P1 discards 3 and picks Deal2 (→ SEC_080), DefeatUpgrade (SOR_120 on SEC_080, lone upgrade
# auto-defeats), OppDiscard. SEC_080 ends at DAMAGE:2 with its upgrade gone (UPGRADECOUNT:0); the base
# took only the 6 combat; P2 loses its hand card + the upgrade to discard (2); P1 discards all 3.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_153:1:0
WithP1Hand: SOR_095
WithP1Hand: SEC_080
WithP1Hand: SOR_128
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2
- P1>AnswerDecision:Deal2
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:DefeatUpgrade
- P1>AnswerDecision:OppDiscard

## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:3
