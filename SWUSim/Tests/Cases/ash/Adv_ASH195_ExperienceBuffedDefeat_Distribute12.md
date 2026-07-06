# ASH_195 Helgait — When Defeated distributes Advantage equal to THIS unit's power, INCLUDING buffs from
# subcards, snapshotted at defeat time. Their Helgait carries 6 Experience tokens (SOR_T01, +1/+1 each →
# 12/10) and 8 damage (2 HP left). My measly Spy (SEC_T01, 0/2 Raid 2 → attacks at 2) finishes it. The
# distribute pool must be 12 (Helgait's buffed power), assigned among their surviving units.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SEC_T01:1:0          # my Spy (0/2, Raid 2) — the attacker
WithP2GroundArena: ASH_195:1:8          # their Helgait (6/4) + 6 Experience below → 12/10, 8 damage (2 HP left)
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2SpaceArena: JTL_039:1:0           # their surviving unit that receives the Advantage
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
## EXPECT
P2GROUNDARENACOUNT:0
P2DECISIONTOOLTIP:Distribute_up_to_12_Advantage_among_friendly_units
