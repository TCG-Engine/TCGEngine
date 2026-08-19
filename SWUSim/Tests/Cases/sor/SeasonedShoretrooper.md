# FiveResources_NoBuff
#// SOR_081 Seasoned Shoretrooper (2/3) — boundary: with only 5 resources the
#// +2/+0 does NOT apply (threshold is 6). Reads its printed 2/3.
#// (Absence guard — passes pre-implementation; stays meaningful once the buff exists.)

## GIVEN
CommonSetup: grk/grk/{myResources:5}
WithP1GroundArena: SOR_081:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3

---

# SixResources_Buffed
#// SOR_081 Seasoned Shoretrooper (2/3) — "While you control 6 or more resources,
#// this unit gets +2/+0." With 6 resources it reads 4/3.

## GIVEN
CommonSetup: grk/grk/{myResources:6}
WithP1GroundArena: SOR_081:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3


---

# FiveResourcesPlusCredit_NoBuff
#// SOR_081 Seasoned Shoretrooper — a Credit token is NOT a resource (CR 3.13), so 5 real resources plus
#// a Credit is still 5 and the +2/+0 must NOT apply. The unit reads its printed 2/3.
#// The reprint SHD_083 has always counted this correctly (SWUResourceCount skips Credits); this printing
#// counted the raw resource zone, so a Credit silently pushed it over the threshold.
#// ⚠ The two printings are the SAME card — see the passing control in shd/SeasonedShoretrooper.md.

## GIVEN
CommonSetup: grk/grk/{myResources:5}
WithP1Credits: 1
WithP1GroundArena: SOR_081:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3
