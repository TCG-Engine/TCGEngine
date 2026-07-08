# ASH_195 Helgait (Ground, 6/4) — When Defeated distributes Advantage equal to THIS unit's power (6),
# regardless of how or where it was defeated. Regression for game 2622: Helgait is the DEFENDER at
# P2 ground-0, killed by a SEC_T01 Spy (0/2, Raid 2 → attacks at power 2) into its 2 remaining HP.
#
# The bug: the When-Defeated closure runs under Helgait's controller's frame (P2) but re-resolves the
# defeated unit from the mzID "theirGroundArena-0" captured in the ACTIVE player's (P1) frame. Under
# P2's frame that string points to P1's ground-0 — here LAW_039 Latts Razzi (power 2) — so it offered
# only 2 Advantage instead of Helgait's 6. Assert on the distribute prompt, which embeds the pool.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: LAW_039:1:0          # Latts Razzi (power 2) — survives; P1 ground-0 (the frame-bug decoy)
WithP1GroundArena: SEC_T01:1:0          # Spy (0/2, Raid 2) — the attacker at P1 ground-1
WithP2GroundArena: ASH_195:0:2          # Helgait (6/4) exhausted, 2 damage → 2 HP left (the defender)
WithP2SpaceArena: JTL_039:1:0           # Chimaera — the surviving friendly unit that would receive Advantage
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
- P2>Pass
## EXPECT
P2GROUNDARENACOUNT:0
P2DECISIONTOOLTIP:Distribute_up_to_6_Advantage_among_friendly_units
