# ActionGivesExp
#// SOR_094 Bail Organa (1/?) — Action [Exhaust]: give an Experience token to another
#// friendly unit. P1 uses Bail's action; his only other friendly (Battlefield Marine,
#// 3/3) is the sole target → auto-receives +1/+1 (→ 4/4). Bail is exhausted.
#// COVERAGE: offer=Offer_AnotherFriendlyUnitOnly_SelfAndEnemyExcluded (pending SELECTABLEEXACT over
#//           P1's other units in BOTH arenas; Bail himself is excluded by "another" and the enemy unit
#//           by "friendly") · reqboundary=RequestBoundary_ExperienceLandsAfterTheAnswerCrossesTheBoundary ·
#//           control=ControlChange_AStolenUnitIsFriendlyAndEligible ("friendly" reads CONTROL, so a
#//           P2-owned unit P1 controls is a legal recipient) · boundary pair=zero vs one vs two other
#//           friendly units: NoOtherFriendlyUnit_NothingIsGiven (0 → nothing given, no prompt) vs
#//           ActionGivesExp (1 → auto-resolves) vs Offer_AnotherFriendlyUnitOnly_SelfAndEnemyExcluded
#//           (2 → real prompt); the [Exhaust] cost gate is ExhaustedBail_CannotPayTheExhaustCost ·
#//           decline=N/A — the printed text has no "you may"; the Action's target pick is a mandatory
#//           MZCHOOSE, and the only "decline" available is not activating the Action at all.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_094:1:0    # Bail Organa (ready) — index 0
WithP1GroundArena: SOR_095:1:0    # the other friendly unit — index 1

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Offer_AnotherFriendlyUnitOnly_SelfAndEnemyExcluded
#// Intended: "give an Experience token to ANOTHER FRIENDLY unit" — the pool is P1's other units in
#// BOTH arenas (the Marine on the ground, the A-Wing in space); Bail himself is excluded by
#// "another", and P2's Security Force is excluded by "friendly". Two legal targets keep the pick
#// interactive, so the decision is left PENDING and the offer itself is the assertion.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_094:1:0    # Bail Organa — index 0
WithP1GroundArena: SOR_095:1:0    # friendly ground unit — index 1
WithP1SpaceArena: SOR_141:1:0     # friendly space unit
WithP2GroundArena: SOR_046:1:0    # enemy unit — never eligible

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&mySpaceArena-0

---

# PicksTheSpaceUnit_ExperienceIsAPlusOnePlusOneUpgrade
#// SOR_094 — the Experience token is a real attached upgrade (SOR_T01), not a stat rewrite: the
#// chosen A-Wing (1/3) reads 2/4 and carries exactly one upgrade, the unchosen Marine is untouched,
#// and the enemy unit never receives anything. Bail is exhausted by his own [Exhaust] cost.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_094:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_141:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:4
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# RequestBoundary_ExperienceLandsAfterTheAnswerCrossesTheBoundary
#// SOR_094 — with two legal recipients the pick is a real prompt, and in production that prompt ends
#// the request: the answer arrives in a fresh process. The chosen Marine still gains +1/+1 (3/3 → 4/4),
#// the unchosen A-Wing is untouched, and Bail stays exhausted.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_094:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_141:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
P1SPACEARENAUNIT:0:POWER:1
P1SPACEARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ControlChange_AStolenUnitIsFriendlyAndEligible
#// SOR_094 — "another FRIENDLY unit" reads CONTROL, not ownership. The Dark Trooper P1 controls but
#// P2 OWNS (the end state after a take-control effect) is friendly to Bail and is the only other unit
#// P1 controls, so the pick auto-resolves onto it: 3/3 → 4/4. Controlled units seat AFTER the plain
#// arena lines, so Bail is index 0 and the stolen Trooper index 1.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_094:1:0
WithP1GroundArenaControlled: SEC_080:2

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
P1NODECISION
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# NoOtherFriendlyUnit_NothingIsGiven
#// SOR_094 — boundary at zero targets. Bail is P1's ONLY unit; "another friendly unit" therefore has
#// no referent, so no Experience token is created anywhere and the enemy unit — never eligible in the
#// first place — carries no upgrade. Bail himself never gets the token ("another" excludes him).

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_094:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# ExhaustedBail_CannotPayTheExhaustCost
#// SOR_094 — the Action costs [Exhaust]. An already-exhausted Bail cannot pay it, so the ability is a
#// full no-op: the Marine gains nothing and no decision is raised. Cost-gate guard.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_094:0:0    # Bail already EXHAUSTED
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1NODECISION
