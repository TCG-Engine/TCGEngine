# BuffAnother
#// LAW_151 Profiteering Hunter (1/3) — When Played: another friendly unit gets +1/+1 for this phase.
#// The only other friendly unit (SOR_095) auto-targets -> 4/4.
#// COVERAGE: offer=OfferIsOtherFriendlyUnitsOnly_InclLeaderUnit (pending SELECTABLEEXACT; self + enemy
#//           excluded, deployed leader + space included) · decline=N/A (mandatory pick, no "you may") ·
#//           control=N/A (phase-scoped stat effect, no persistent per-unit marker) · reqboundary=covered
#//           by the phase-cross in BuffSpaceUnit_ExpiresAtEndOfPhase (buff survives serialization until
#//           the phase ends) · boundary pair=BuffSpaceUnitApplies (3/4 mid-phase) +
#//           BuffSpaceUnit_ExpiresAtEndOfPhase (2/3 next phase).

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_151

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4

---

# OfferIsOtherFriendlyUnitsOnly_InclLeaderUnit
#// LAW_151 Profiteering Hunter — "ANOTHER friendly unit": the offer excludes the Hunter itself and every
#//   enemy unit, but INCLUDES a deployed friendly leader unit and friendly space units. Ground order after
#//   the play: SOR_095 (0), deployed leader (1), Hunter (2, excluded). Decision left PENDING to assert
#//   the offer; the pick is mandatory (no decline).

## GIVEN
CommonSetup: ggw/bgw/{myResources:1;myLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: LAW_151
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0
P1HASDECISION

---

# BuffDeployedLeaderUnit
#// LAW_151 Profiteering Hunter — a deployed friendly LEADER unit is a legal pick and gets +1/+1.
#//   Leia (gw leader, deployed 3/6) at ground index 1 becomes 4/7.

## GIVEN
CommonSetup: ggw/bgw/{myResources:1;myLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: LAW_151
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:7
P1GROUNDARENAUNIT:0:POWER:3
P1NODECISION

---

# BuffSpaceUnit_ExpiresAtEndOfPhase
#// LAW_151 Profiteering Hunter — a friendly SPACE unit is a legal pick (the buff is arena-agnostic), and
#//   the +1/+1 lasts "for this phase" only: after the action phase ends and the game crosses regroup into
#//   the next action phase, SOR_237 is back to its printed 2/3. Decks seeded so the regroup draw doesn't
#//   deck anyone. (In-phase application itself is proven by BuffAnother / BuffDeployedLeaderUnit.)

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
P1OnlyActions: true
WithP1Hand: LAW_151
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass

## EXPECT
PHASE:MAIN
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:3

---

# BuffSpaceUnitApplies
#// LAW_151 Profiteering Hunter — the space-arena APPLICATION mid-phase (companion to
#//   BuffSpaceUnit_ExpiresAtEndOfPhase, which only proves the revert): SOR_237 (2/3) is the lone other
#//   friendly unit, auto-targets, and is 3/4 while the phase is still running.

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
P1OnlyActions: true
WithP1Hand: LAW_151
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:4
P1NODECISION
