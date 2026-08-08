# ExhaustedHost_NoAura
#// SEC_104 — the aura is conditional on the attached unit being READY. With the host SEC_041 exhausted,
#//   the other friendly SEC_042 does NOT gain Overwhelm.

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_041:0:0
WithP1GroundArenaUpgrade: 0:SEC_104
WithP1GroundArena: SEC_042:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:NOTKEYWORD:Overwhelm

---

# ReadyHost_AuraGrants
#// SEC_104 The Will of the People (upgrade, +2/+2) — Attached unit gains: "While this unit is READY, each
#//   other friendly unit gains Overwhelm, Raid 1, and Restore 1." Host SEC_041 is ready → the other
#//   friendly SEC_042 gains all three.

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArenaUpgrade: 0:SEC_104
WithP1GroundArena: SEC_042:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
P1GROUNDARENAUNIT:1:HASKEYWORD:Restore

---

# AuraExcludesTheHostItself
#// SEC_104 The Will of the People — "each OTHER friendly unit gains …", so the attached unit is not one
#// of its own beneficiaries. SEC_041 wears the upgrade and is ready, and the ally picks up all three
#// keywords, but the host itself has none of them.

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArenaUpgrade: 0:SEC_104
WithP1GroundArena: SEC_042:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm

---

# AuraDoesNotReachEnemyUnits
#// SEC_104 The Will of the People — "each other FRIENDLY unit", so the opponent's board gains nothing
#// even while the host is ready. P2's SEC_042 stays keyword-free while P1's copy is granted all three.

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArenaUpgrade: 0:SEC_104
WithP1GroundArena: SEC_042:1:0
WithP2GroundArena: SEC_042:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm
P2GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P2GROUNDARENAUNIT:0:NOTKEYWORD:Restore
