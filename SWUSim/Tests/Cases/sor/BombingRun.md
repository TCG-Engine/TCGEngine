# Deals3ToChosenArena
#// SOR_173 Bombing Run (Event, cost 5) — "Choose an arena. Deal 3 damage to each
#// unit in that arena." P1 chooses Ground (YES): both ground units (friendly + enemy
#// Consular Security Force, 3/7) take 3 and survive at 3 damage. The friendly Space
#// unit (Restored ARC-170, 2/3) is in the other arena → untouched (0 damage).
#// COVERAGE: offer=Deals3ToChosenArena + SpaceChoice_DefeatsSpaceUnits (the Ground&Space
#//           option-choice is exercised on both branches; it is an OPTIONCHOOSE, not an
#//           mzID pool, so no SELECTABLEEXACT applies) · reqboundary=Deals3ToChosenArena
#//           (the arena answer arrives in a separate request from the play) · control=
#//           every damage section (the sweep hits BOTH players' units symmetrically) ·
#//           boundary pair=Deals3ToChosenArena (3 < 7 HP → survives) +
#//           SpaceChoice_DefeatsSpaceUnits (3 ≥ HP → defeated) · decline=N/A (no "you may" —
#//           but EmptyChosenArena_NoOp + BothArenasEmpty_StillResolves cover the empty-sweep
#//           no-op branches)

## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:SOR_173}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0    # friendly ground (3/7)
WithP2GroundArena: SOR_046:1:0    # enemy ground (3/7)
WithP1SpaceArena: SOR_044:1:0     # friendly space (2/3) — different arena

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENACOUNT:1

---

# SpaceChoice_DefeatsSpaceUnits
#// Intended: choosing Space sweeps BOTH players' space units — P1's Restored ARC-170 (2/3)
#// and P2's TIE/ln Fighter (2/1) both die to the 3 — while ground units and the deployed
#// leaders on the ground are untouched.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:SOR_173;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0     # friendly space (2/3) — dies
WithP2SpaceArena: SOR_225:1:0     # enemy space (2/1) — dies
WithP1GroundArena: SOR_046:1:0    # friendly ground — untouched (leader unit seats at idx 1)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# GroundChoice_HitsDeployedLeaders
#// Intended: "each unit in that arena" includes deployed LEADER units on both sides — both
#// deployed Darth Vaders (5/8) take 3 alongside the regular ground units; the friendly space
#// unit is untouched.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:SOR_173;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0    # friendly ground (3/7) — survives at 3
WithP2GroundArena: SOR_046:1:0    # enemy ground (3/7) — survives at 3
WithP1SpaceArena: SOR_044:1:0     # friendly space — untouched

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P2GROUNDARENAUNIT:1:DAMAGE:3
P1SPACEARENAUNIT:0:DAMAGE:0

---

# EmptyChosenArena_NoOp
#// Intended: the arena choice is not restricted to occupied arenas — Space can be chosen
#// while only ground units are in play; nothing takes damage and the event still resolves
#// to the discard.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:SOR_173}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# BothArenasEmpty_StillResolves
#// Intended: with no units anywhere the event can still be played — the arena choice
#// resolves, nothing happens, and the card ends in the discard with no damage to any base.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:SOR_173}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1BASEDMG:0
P2BASEDMG:0
