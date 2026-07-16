# UpgradeGrantsWhenDefeated
#// ASH_134 Warrior's Legacy (Upgrade, +2/+1) — "Attached unit gains: When Defeated: create a Mandalorian
#// token." Attached to a Stormtrooper (3/1 → 5/2); it attacks SEC_080 (3/3): deals 5 (SEC_080 dies), takes
#// 3 counter and dies (2 HP) → the granted When Defeated creates a Mandalorian token.
## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:ASH_134
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P2GROUNDARENACOUNT:0
