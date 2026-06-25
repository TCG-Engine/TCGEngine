# ASH_006 Sabine Wren (deployed) — On Attack: the next unit you play this phase gains Shielded
# for this phase. Sabine attacks the base, then P1 plays an X-Wing → it enters with a Shield.

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_006:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_237
WithP1Resources: 4

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
