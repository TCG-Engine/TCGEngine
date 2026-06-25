# SOR_058 Vigilance — the other two modes. P1 chooses Heal5 (heal 5 from a base — picks its own base,
# which was at 5 damage → 0) then Defeat (defeat a unit with ≤3 remaining HP — SOR_128 is a 3/1, the
# only qualifying unit, auto-defeated).

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  myBaseDamage:5;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_058
WithP1Resources: 4
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Heal5
- P1>AnswerDecision:myBase-0
- P1>AnswerDecision:Defeat

## EXPECT
P1BASEDMG:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
