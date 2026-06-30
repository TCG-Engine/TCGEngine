# JTL_103 Chewbacca — the immunity is to ENEMY CARD ABILITIES only; it does NOT prevent a combat defeat.
# A pre-damaged Chewbacca (5 of 6 HP) attacks SEC_080, takes 3 counter damage, and is defeated by having
# no remaining HP.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_103:1:5
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
