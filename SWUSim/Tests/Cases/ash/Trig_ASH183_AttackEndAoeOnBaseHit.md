# ASH_183 Whistling Birds (Upgrade, non-Vehicle) — Attached unit gains "When Attack Ends: if this unit
# dealt combat damage to an opponent's base, deal 2 to each unit that opponent controls in this unit's
# arena." SOR_095 (3/3 + Whistling Birds +2/+2 → 5 power) attacks P2's base for 5; afterward the enemy
# SEC_080 (in the ground arena) takes 2.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_183
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:0
P2BASEDMG:5
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
