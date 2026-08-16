# Upgraded_GainsOverwhelm
#// SHD_169 Clan Challengers (5-cost 3/6 ground) — Raid 3 + "While this unit is upgraded, it gains
#// Overwhelm." Regression guard for the hand-maintained conditional-keyword switch: with an upgrade attached
#// it has Overwhelm; without one it does not.
#// COVERAGE: offer=N/A (a conditional self-keyword has no target pick) · decline=N/A (not a "you may") ·
#//           control=N/A (the grant is self-only — no other unit and no controller dimension) ·
#//           boundary=Upgraded_GainsOverwhelm (upgraded index 0 HAS it vs bare index 1 does NOT) ·
#//           reqboundary=N/A (constant ability recomputed from the attached-upgrade count on every read)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_169:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArena: SHD_169:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:NOTKEYWORD:Overwhelm

---

# Upgraded_OverwhelmDealsExcessToBase
#// SHD_169 — the granted Overwhelm actually routes excess combat damage, and it stacks with Raid 3.
#// SHD_169 (3/6) + SOR_120 Academy Training (+2/+2) = 5/8; Raid 3 adds +3/+0 while attacking = 8 power.
#// It attacks SOR_164 Wampa (4/5): 5 damage defeats the Wampa and the 3 excess hits P2's base.
#// SHD_169 takes the Wampa's 4 back (8 HP, survives) and is left exhausted.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_169:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:4
