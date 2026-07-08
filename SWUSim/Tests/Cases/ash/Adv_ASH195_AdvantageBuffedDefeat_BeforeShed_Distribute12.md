# ASH_195 Helgait — When Defeated resolves BEFORE the defender's Advantage tokens shed at "defense end",
# so its power still counts those tokens. Their Helgait carries 6 Advantage tokens (ASH_T02, +1/+0 each →
# 12/4) and 2 damage (2 HP left); my Spy (SEC_T01, 0/2 Raid 2) defeats it. In CollectCombatStep3Triggers
# the When Defeated snapshot is taken before _SWUDefeatAllAdvantageTokens sheds the defender's tokens, so
# the distribute pool is 12, not 6.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SEC_T01:1:0          # my Spy (0/2, Raid 2) — the attacker
WithP2GroundArena: ASH_195:1:2          # their Helgait (6/4) + 6 Advantage below → 12/4, 2 damage (2 HP left)
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2SpaceArena: JTL_039:1:0           # their surviving unit that receives the Advantage
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
## EXPECT
P2GROUNDARENACOUNT:0
P2DECISIONTOOLTIP:Distribute_up_to_12_Advantage_among_friendly_units
