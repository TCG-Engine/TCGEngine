# LAW_129 Mastery — guard: NO discount on a UNIQUE host. With only 3 resources and a unique host
# (SOR_181 Jabba), Mastery costs its full 4 → unaffordable, so the play is rejected and it stays in
# hand (proves the discount is host-conditional, not always-on).

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_181:1:0
WithP1Hand: LAW_129

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
