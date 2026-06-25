# SEC_007 Dryden Vos (deployed) — Action [discard a card from your hand]: play a unit from your hand
# (paying its cost). It gains Ambush this phase. Dryden discards SOR_095, plays SOR_128 (3/1) which
# gains Ambush.

## GIVEN
CommonSetup: bgk/brk/{
  myLeader:SEC_007:1:1:1;
  myBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_128
WithP1Resources: 6

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_128
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P1HANDCOUNT:0
P1DISCARDCOUNT:1
