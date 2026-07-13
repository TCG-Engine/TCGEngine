# TWI_040 A Fine Addition — "from any player's discard pile" includes the OPPONENT's discard. SOR_120 is
# in P2's discard; P1 plays it from there onto its own unit. (The upgrade is still owned by P2 for later
# discard routing, but the play + attach are what matter here.)
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040;theirDiscardCardIds:SOR_120}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:theirDiscard-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1RESAVAILABLE:4
