# ASH_116 Ant Droid (Ground, 1/2) — When Defeated: draw a card. The Ant Droid attacks SOR_046 and dies to
# the counter; its WhenDefeated draws a card.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_116:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
