# SEC_158 Oppression Breeds Rebellion (event, cost 3) — If a friendly unit was defeated WHILE ATTACKING
#   this phase, draw 3 cards. SEC_042 (2/2) attacks SOR_046 (3/7) and dies to the counter; then P1 plays
#   SEC_158 → draws 3.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_042:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_158
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:3
