# SOR_145 K-2SO — the other branch of the When Defeated choice: P1 chooses Discard, so the opponent
# discards a card from their hand (here their only card, auto-discarded). The base is untouched.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Discard

## EXPECT
P1GROUNDARENACOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P2BASEDMG:0
