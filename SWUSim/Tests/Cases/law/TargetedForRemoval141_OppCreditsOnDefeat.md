# LAW_141 Targeted For Removal (Upgrade, +0/+0) — grants "When Defeated: An opponent creates Credit
# tokens equal to this unit's cost." SEC_080 (cost 2) wears it and attacks the 8/8 SOR_039, dying. Its
# granted When Defeated fires → P2 (the opponent) creates 2 Credit tokens (= SEC_080's cost).

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_141
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2CREDITCOUNT:2
