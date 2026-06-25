# SOR_105 General Krell — the grant is "each OTHER friendly unit", so Krell's OWN defeat draws
# nothing. Krell is the only friendly; he attacks into lethal and dies → no card is drawn.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_105:1:0
WithP2GroundArena: SOR_213:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
