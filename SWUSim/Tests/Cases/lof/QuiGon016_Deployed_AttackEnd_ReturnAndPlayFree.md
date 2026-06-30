# LOF_016 Qui-Gon Jinn (deployed) — When this unit completes an attack (and survives): you may return
# a friendly non-leader unit to its owner's hand, then play a non-Villainy unit costing less than the
# returned unit for free. Qui-Gon attacks the base (survives), returns the SOR_046 wall (cost 4), and
# plays the X-Wing (SOR_237, cost 2 < 4, Heroism) from hand for free.

## GIVEN
CommonSetup: gyw/brk/{
  myLeader:LOF_016:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_237

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
