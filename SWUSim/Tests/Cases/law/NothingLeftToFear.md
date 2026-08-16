# BuffThenDefeat
#// LAW_041 Nothing Left to Fear (Vigilance,Command event, cost 5) — "Choose a friendly unit and give it
#// +2/+2 for this phase. Then, you may defeat a non-leader unit with power equal to or less than the
#// chosen unit." Buff SOR_095 (3/3 -> 5/5), then defeat enemy SEC_080 (power 3 <= 5).

## GIVEN
CommonSetup: bgw/rrk/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_041

## WHEN
#// Only one friendly unit, so the "choose a friendly unit" step auto-resolves (PASSPARAMETER) and
#// buffs SOR_095; the single AnswerDecision feeds the "you may defeat" MZMAYCHOOSE.
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# DeclineDefeat
#// LAW_041 Nothing Left to Fear — the defeat is a "may"; decline it. Buff still applies; nothing dies.

## GIVEN
CommonSetup: bgw/rrk/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_041

## WHEN
#// Single friendly unit -> buff auto-applies; decline the optional defeat.
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P1DISCARDCOUNT:1

---

# ChooseAmongFriendliesAndPowerRestrictedDefeat
#// LAW_041 Nothing Left to Fear — with two friendly units the buff target is chosen (not auto). Buff
#// SOR_095 (3/3 -> 5/5), then the "you may defeat" is restricted to non-leader units with power <= 5:
#// SEC_080 (power 3) can be defeated, but LOF_236 Army of the Dead (power 7) is not a legal target and
#// survives.

## GIVEN
CommonSetup: bgw/rrk/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LOF_236:1:0
WithP1Hand: LAW_041

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_236
P1DISCARDCOUNT:1

---

# BuffExpiresNextPhase
#// LAW_041 Nothing Left to Fear — the +2/+2 lasts only "this phase". Buff SOR_095 (3/3 -> 5/5), decline
#// the optional defeat, then advance to the next action phase: the bonus is gone (back to 3/3).

## GIVEN
CommonSetup: bgw/rrk/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_041
WithP1Deck: SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# PowerRestrictedDefeat_SurvivesTheRequestBoundary
#// LAW_041 Nothing Left to Fear — request-boundary guard. The chosen unit's buffed power is the gate on the
#// second step ("defeat a non-leader unit with power equal to or less than the chosen unit"), so that
#// snapshot must survive the answer, which in production arrives in a fresh process. Same flow as
#// ChooseAmongFriendliesAndPowerRestrictedDefeat with a serialize round-trip inserted before the defeat
#// answer; the decision is real (MZMAYCHOOSE over myGroundArena-0 & myGroundArena-1 & theirGroundArena-0 —
#// LOF_236 at power 7 correctly still excluded). SOR_095 keeps its +2/+2 and SEC_080 still dies.

## GIVEN
CommonSetup: bgw/rrk/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LOF_236:1:0
WithP1Hand: LAW_041

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_236
P1DISCARDCOUNT:1

---

# OfferPool_BuffTargetIsFriendlyOnly
#// LAW_041 Nothing Left to Fear — offer assertion for step 1, "choose a FRIENDLY unit". Both arenas are
#// seeded on both sides plus a deployed enemy leader unit, so the board carries four possible violators;
#// the pool must be exactly P1's two units. Nothing enemy may appear even though step 2 reaches across the
#// table — the controller scope belongs to step 1 alone, and a pool shared between the two steps would
#// show up here as enemy bodies leaking into the buff choice.
#// COVERAGE: offer=OfferPool_BuffTargetIsFriendlyOnly (step 1: friendly-only, both arenas) +
#//           OfferPool_DefeatExcludesLeaderUnitAndHigherPower (step 2: non-leader AND power-gated, both
#//           sides) · decline=DeclineDefeat (the "you may defeat" declined with '-') · boundary
#//           pair=BuffThenDefeat (power 3 <= 5 dies) vs ChooseAmongFriendliesAndPowerRestrictedDefeat
#//           (power 7 survives) + BuffExpiresNextPhase (phase-scoped buff gone next phase) ·
#//           reqboundary=PowerRestrictedDefeat_SurvivesTheRequestBoundary · control=N/A (a phase-scoped
#//           +2/+2 on the chosen unit and a one-shot defeat; no seat-bound per-unit marker)

## GIVEN
CommonSetup: bgw/rrk/{myResources:5; theirLeader:ASH_011:1:1:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: [SEC_080:1:0 LOF_236:1:0]
WithP1Hand: LAW_041

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# OfferPool_DefeatExcludesLeaderUnitAndHigherPower
#// LAW_041 Nothing Left to Fear — offer assertion for step 2, "defeat a NON-LEADER unit with POWER EQUAL
#// TO OR LESS THAN the chosen unit". SOR_095 is buffed to 5/5, so the gate is power <= 5, and the board
#// carries a violator for each half INDEPENDENTLY: LOF_236 Army of the Dead (power 7) is out on power
#// alone, and the deployed Cad Bane leader unit is out on "non-leader" alone — his power 4 clears the
#// gate, so a board with only a high-power leader could not tell the two exclusions apart. The chosen unit
#// itself is in at exactly equal power (5), as is the friendly space unit (power 2) and SEC_080 (power 3).
#// ChooseAmongFriendliesAndPowerRestrictedDefeat only proved the power-7 unit survived being unpicked;
#// this proves it was never offered.

## GIVEN
CommonSetup: bgw/rrk/{myResources:5; theirLeader:ASH_011:1:1:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: [SEC_080:1:0 LOF_236:1:0]
WithP1Hand: LAW_041

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0
