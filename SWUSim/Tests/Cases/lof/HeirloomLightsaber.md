# ForceRestore
#// LOF_053 Heirloom Lightsaber (+2/+2) — Attach to a non-Vehicle unit. If the attached unit is a Force
#// unit, it gains Restore 1. On Plo Koon (Force) he is 8/10 and has Restore.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_053

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore

---

# NonForce_NoRestore
#// LOF_053 Heirloom Lightsaber (+2/+2) — attaches to a non-Vehicle, non-Force unit (Imperial Dark Trooper
#// SEC_080, 3/3). It does NOT gain Restore (only Force units do), and gets +2/+2 → 5/5.
#// Ref: "does not give Restore to non-Force units".

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LOF_053

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:NOTKEYWORD:Restore
