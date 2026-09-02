# Deployed_OnAttack_ExpToImperial
#// COVERAGE: offer=LeaderAction_Offer_ImperialUnitsOnly_NonImperialFriendlyExcluded +
#//           LeaderAction_Offer_UnqualifiedImperialSpansBothSides +
#//           Deployed_OnAttack_Offer_AnotherImperialUnit_TarkinAndNonImperialExcluded
#//           decline=Deployed_OnAttack_Declined_NoExperienceIsGiven
#//           boundary=EpicAction_ExactlyFiveResources_Deploys / EpicAction_FourResources_DoesNotDeploy
#//           control=ControlChange_AStolenImperialUnitIsAValidRecipient
#//           reqboundary=LeaderAction_RequestBoundary_ExperienceLandsAcrossTheBoundary
#//           modes=2P only - "an Imperial unit" is a trait pool with no controller word and no player
#//           reference, so all three formats share one path.
#// SOR_007 Grand Moff Tarkin — deployed leader unit (2/7) On Attack: You may give an Experience
#// token to ANOTHER Imperial unit. Deployed Tarkin (the only ground unit) attacks the base; on
#// YES the only other Imperial unit — SOR_225 (2/3, space) — auto-receives +1/+1 (→ 3/4). The
#// base takes Tarkin's 2 power.
#// COVERAGE (leader/front side — Action [1 resource, exhaust] + Epic Action deploy):
#//           offer=LeaderAction_Offer_ImperialUnitsOnly_NonImperialFriendlyExcluded (pending
#//           SELECTABLEEXACT; a friendly non-Imperial unit is the excluded target) PLUS
#//           LeaderAction_Offer_UnqualifiedImperialSpansBothSides — RED, see below ·
#//           reqboundary=LeaderAction_RequestBoundary_ExperienceLandsAcrossTheBoundary ·
#//           control=ControlChange_AStolenImperialUnitIsAValidRecipient · boundary
#//           pair=EpicAction_ExactlyFiveResources_Deploys (5 = the inclusive edge) vs
#//           EpicAction_FourResources_DoesNotDeploy (4 = one short, and the Epic Action is NOT spent),
#//           with the zero-recipient edge LeaderAction_NoImperialUnit_NothingIsGiven and the cost
#//           gates LeaderAction_NoResource_NoOp +
#//           LeaderAction_ExhaustedLeader_CannotPayTheExhaustHalfOfTheCost ·
#//           decline=N/A — the front-side text has no "you may"; the recipient pick is a mandatory
#//           MZCHOOSE and the only way to decline is not to use the Action.
#// COVERAGE (deployed side — On Attack: You may give an Experience token to another Imperial unit):
#//           offer=Deployed_OnAttack_Offer_AnotherImperialUnit_TarkinAndNonImperialExcluded (pending
#//           SELECTABLEEXACT spanning BOTH sides; deployed Tarkin himself and a friendly non-Imperial
#//           are the excluded targets) · reqboundary=covered by the front side's
#//           LeaderAction_RequestBoundary_ExperienceLandsAcrossTheBoundary — both sides funnel the
#//           same Experience-token pick through the same decision queue, and the deployed side adds no
#//           state of its own across the boundary · control=ControlChange_AStolenImperialUnitIsAValidRecipient
#//           (same recipient pool; "an Imperial unit" carries no ownership qualifier on either side) ·
#//           boundary pair=Deployed_OnAttack_NoOtherImperialUnit_NoPromptAndNoToken (0 recipients → no
#//           prompt) vs Deployed_OnAttack_ExpToImperial (1 → auto-resolves) vs the two-recipient offer
#//           section · decline=Deployed_OnAttack_Declined_NoExperienceIsGiven (printed "You may").
#// ⚠ LeaderAction_Offer_UnqualifiedImperialSpansBothSides is RED on purpose. The front side's printed
#// text is "Give an Experience token to AN IMPERIAL UNIT" with no "friendly" qualifier — SWU spells
#// "friendly" out when it means it (SOR_094 "another FRIENDLY unit", SOR_036 "a FRIENDLY unit") — and
#// Tarkin's own DEPLOYED side uses the identical unqualified phrase and already offers enemy Imperial
#// units. The front side currently narrows the pool to friendly units only, so with one friendly and
#// one enemy Imperial on the table the pick collapses to a forced single target and no decision is
#// raised at all. Left red as the signal.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_225:1:0     # another Imperial unit (space) — Experience recipient

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P2BASEDMG:2
P1LEADER:EPICUSED

---

# LeaderAction_ExpToImperial
#// SOR_007 Grand Moff Tarkin (leader) — Action [1 resource, exhaust]: Give an Experience token
#// to an Imperial unit. P1 uses the leader action: pays 1 resource (2 → 1 ready), the leader
#// exhausts, and the only Imperial unit (SOR_229, 3/3) auto-receives +1/+1 (→ 4/4).

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_229:1:0    # Imperial unit — Experience recipient

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1

---

# LeaderAction_NoResource_NoOp
#// SOR_007 Grand Moff Tarkin — the leader Action costs 1 resource. With 0 ready resources it
#// is a full no-op: the leader stays READY (action not spent), the Imperial unit gets no
#// Experience, and no decision is pending. Unaffordable-cost guard.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_229:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3
P1NODECISION

---

# LeaderAction_Offer_ImperialUnitsOnly_NonImperialFriendlyExcluded
#// Intended: the leader Action's pool is filtered by the IMPERIAL trait. Two friendly Imperial units
#// (Cell Block Guard on the ground, TIE/ln Fighter in space) are legal while the friendly Battlefield
#// Marine (Rebel/Trooper) is not. Two legal targets keep the pick interactive, so the decision is left
#// PENDING and the offer itself is the assertion. Passing CONTROL for the trait filter and for the
#// fixture used by LeaderAction_Offer_UnqualifiedImperialSpansBothSides below.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: [SOR_229:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# LeaderAction_Offer_UnqualifiedImperialSpansBothSides
#// Intended: the printed leader-side text is "Give an Experience token to AN IMPERIAL UNIT" — it never
#// says "friendly", and SWU templating spells that out when it means it (compare SOR_094 Bail Organa,
#// "another FRIENDLY unit", and SOR_036 Gideon Hask, "a FRIENDLY unit"). An unqualified "an X unit"
#// therefore spans BOTH sides of the table, so P2's Imperial Dark Trooper must be in the pool beside
#// P1's Cell Block Guard, while P1's non-Imperial Battlefield Marine stays out. Tarkin's own DEPLOYED
#// side reads the identical phrase and is already implemented as both-sides — the two halves of one
#// card must agree. The decision is left PENDING so the offer itself is the assertion.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: [SOR_229:1:0 SOR_095:1:0]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# LeaderAction_RequestBoundary_ExperienceLandsAcrossTheBoundary
#// SOR_007 leader side — with two legal Imperial recipients the pick is a real prompt, and in
#// production that prompt ends the request: the answer arrives in a fresh process. The chosen TIE/ln
#// Fighter (2/1) still becomes 3/2, the unchosen Cell Block Guard is untouched, the resource is still
#// spent and the leader stays exhausted.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: [SOR_229:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:2
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:3
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1

---

# LeaderAction_ExhaustedLeader_CannotPayTheExhaustHalfOfTheCost
#// SOR_007 leader side — the Action costs "[1 resource, EXHAUST]". An already-exhausted leader cannot
#// pay the exhaust half even with resources to spare, so the ability is a full no-op: the Imperial
#// unit gains nothing, no decision is raised and the resources are untouched.

## GIVEN
CommonSetup: ggk/bbk/{myLeader:SOR_007:0}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: SOR_229:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:3
P1NODECISION

---

# LeaderAction_NoImperialUnit_NothingIsGiven
#// SOR_007 leader side — boundary at zero targets. P1's only unit is a Rebel Battlefield Marine, so
#// "an Imperial unit" has no referent: nothing is given, no decision is raised, and Tarkin (an
#// Imperial LEADER, not a unit while undeployed) is not a target for his own ability.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# EpicAction_ExactlyFiveResources_Deploys
#// SOR_007 — "Epic Action: If you control 5 OR MORE resources, deploy this leader." Boundary N: five
#// resources is the inclusive edge, so the deploy goes through — Tarkin arrives as a 2/7 ground leader
#// unit and the Epic Action is spent.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:7

---

# EpicAction_FourResources_DoesNotDeploy
#// SOR_007 — boundary N-1. With four resources the "5 or more" condition fails: no leader unit
#// appears, the leader stays undeployed and the Epic Action is NOT spent (it is still available for a
#// later turn). This is the cell that proves the resource threshold is load-bearing.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
P1GROUNDARENACOUNT:0

---

# Deployed_OnAttack_Offer_AnotherImperialUnit_TarkinAndNonImperialExcluded
#// Intended: the DEPLOYED side reads "You may give an Experience token to ANOTHER Imperial unit" —
#// unqualified by controller, so the pool spans both sides: P1's TIE/ln Fighter AND P2's Imperial Dark
#// Trooper. Deployed Tarkin himself is excluded by "another" (he is Imperial) and P1's Rebel
#// Battlefield Marine by the trait. The decision is left PENDING so the offer is the assertion;
#// deployed leaders seat at the END of the arena, so Tarkin is myGroundArena-2.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:2:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&theirGroundArena-0

---

# Deployed_OnAttack_Declined_NoExperienceIsGiven
#// SOR_007 deployed side — the printed text is "YOU MAY give an Experience token", so the pick is
#// declinable. Answering '-' leaves the only Imperial recipient at its printed 2/1 with no upgrade,
#// while the attack itself still lands for Tarkin's 2.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:2

---

# Deployed_OnAttack_NoOtherImperialUnit_NoPromptAndNoToken
#// SOR_007 deployed side — boundary at zero targets. Deployed Tarkin is the ONLY Imperial unit on the
#// table and "another" excludes him, so the On Attack ability has nothing to offer: no decision is
#// raised, Tarkin gains no Experience himself, and the attack still deals his 2 to the base.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1NODECISION

---

# ControlChange_AStolenImperialUnitIsAValidRecipient
#// SOR_007 leader side — the Imperial Dark Trooper P1 CONTROLS but P2 OWNS (the end state after a
#// take-control effect) is an Imperial unit and a legal recipient. It is the only one on the table, so
#// the pick auto-resolves onto it: 3/3 → 4/4 with one Experience upgrade, and the leader pays its
#// resource and exhausts.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArenaControlled: SEC_080:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1
