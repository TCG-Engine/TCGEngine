# ExpToUnderworld
#// LAW_249 Black Sun Cabalist (Villainy, cost 2) — When Played: give an Experience token to another
#// friendly Underworld unit. LAW_124 (Underworld) is the only other -> auto-target.

## GIVEN
CommonSetup: rrk/bgw/{myResources:2}
WithP1GroundArena: LAW_124:1:0
WithP1Hand: LAW_249

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# NoTriggerWithoutOtherUnderworld
#// LAW_249 Black Sun Cabalist — When Played with NO other friendly Underworld unit (only a non-Underworld
#// friendly), the ability has no legal target and grants no Experience.

## GIVEN
CommonSetup: rrk/bgw/{myResources:2}
WithP1GroundArena: SOR_046:1:0
WithP1Hand: LAW_249

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# ExpPool_AnotherFriendlyUnderworldUnit
#// COVERAGE: offer=ExpPool_AnotherFriendlyUnderworldUnit (the pool asserted exactly: "another" excludes
#//           the Cabalist itself, "friendly" excludes an enemy Underworld unit, the Underworld trait
#//           excludes a friendly non-Underworld unit and the non-Underworld deployed leader, and a
#//           friendly Underworld SPACE unit is included); offer-absence = NoTriggerWithoutOtherUnderworld ·
#//           decline=N/A (no "you may"; the grant is mandatory when a legal target exists) · control=N/A
#//           (no control-change text) · boundary=ExpToUnderworld (a legal target exists) vs
#//           NoTriggerWithoutOtherUnderworld (none does) · reqboundary=ExpPool_AnotherFriendlyUnderworld
#//           Unit (the When Played pick is left pending in a request after the play).
#// LAW_249 Black Sun Cabalist — "Give an Experience token to ANOTHER FRIENDLY UNDERWORLD unit." Three
#// restrictions, each with a violator seated on the board: the Cabalist itself (played into the ground
#// arena) must be OUT on "another"; P2's SHD_029 Pyke Sentinel is an Underworld unit and must be OUT on
#// "friendly"; P1's own SOR_046 Consular Security Force (Rebel/Trooper) must be OUT on the Underworld
#// trait, as must P1's deployed Darth Vader leader unit; and P1's Underworld SPACE unit SOR_178 must be IN,
#// because the text names no arena. The two friendly Underworld GROUND units keep the pick pending so the
#// pool is readable instead of auto-resolving as it does in ExpToUnderworld.

## GIVEN
CommonSetup: rrk/bgw/{myResources:2;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: [LAW_124:1:0 SHD_029:1:0 SOR_046:1:0]
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SHD_029:1:0
WithP1Hand: LAW_249

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1GROUNDARENAUNIT:3:ISLEADERUNIT
P1GROUNDARENAUNIT:4:CARDID:LAW_249
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0
