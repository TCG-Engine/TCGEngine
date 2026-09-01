# FiveResources_NoBuff
#// COVERAGE: offer=N/A (a static while-clause on the unit itself — it names no target and raises no
#//           decision at any point) · decline=N/A (no "you may"; the buff is continuous and automatic)
#//           · control=ControlTaken_ReadsTheNEWControllersResources ("you" = the CONTROLLER: a
#//           P1-owned trooper controlled by P2 reads P2's 6 resources, not its owner's 0) ·
#//           boundary=FiveResources_NoBuff (5) vs SixResources_Buffed (exactly 6), with
#//           FiveResourcesPlusCredit_NoBuff proving a Credit is not a resource and
#//           OpponentResourcesDoNotCount proving the count is scoped to one seat ·
#//           reqboundary=N/A (nothing is queued or carried — the value is recomputed from the resource
#//           zone on every read, so no state spans a request)
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

---

# OpponentResourcesDoNotCount
#// SOR_081 Seasoned Shoretrooper — "While YOU control 6 or more resources". Every existing section
#// varies only P1's own resource count, so a check that summed the TABLE's resources (or read the wrong
#// seat) would pass all three. Here P1 is one short at 5 while P2 sits on 8: the threshold is scoped to
#// the trooper's controller alone, so it is NOT met and the unit reads its printed 2/3.

## GIVEN
CommonSetup: grk/grk/{myResources:5;theirResources:8}
WithP1GroundArena: SOR_081:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3

---

# ControlTaken_ReadsTheNEWControllersResources
#// SOR_081 Seasoned Shoretrooper — "you" in a while-clause on a unit means its CONTROLLER, not its
#// owner (CR: an ability on an object is controlled by that object's controller). Under a take-control
#// effect the two come apart, and that is the only situation that can tell a controller-scoped read
#// from an owner-scoped one. Here the trooper is OWNED by P1 — who controls zero resources — but
#// CONTROLLED by P2, who controls 6. The threshold is met for the controller, so it reads 4/3 in P2's
#// arena. An owner-scoped implementation would leave it at 2/3.
#// (WithP{n}{Arena}Controlled seats the unit in seat n's arena as its CONTROLLER; the ':N' argument is
#// the OWNER seat.)

## GIVEN
CommonSetup: grk/grk/{myResources:0;theirResources:6}
WithP2GroundArenaControlled: SOR_081:1

## WHEN

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_081
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:3
