# NoOverwhelm_DealsPowerToEnemyUnit
#// HMW_114 Breach — cost 2, [Command][Villainy], Event, Trait: Tactic.
#// Text: "A friendly unit deals damage equal to its power to an enemy unit in its arena. If the
#//        friendly unit has Overwhelm, deal excess damage to an enemy base."
#// (The mock text reads "deal deal excess damage" — a typo in the card text, read as a single "deal".)
#//
#// A two-step targeting effect where the SECOND pool depends on the FIRST pick: the enemy must be in
#// the DEALER's arena. Damage is ability damage equal to the dealer's CURRENT power, and the excess
#// rider is gated on the dealer having Overwhelm.
#//
#// COVERAGE: offer=Offer_EnemyPoolIsArenaMatched (step 2) + Offer_DealerPoolExcludesNoEnemyInArena
#//                 + Offer_DealerPoolExcludesZeroPower (step 1, both exclusions)
#//           decline=N/A — the effect is MANDATORY ("A friendly unit deals…", no "may"); the
#//                 no-legal-target case is NoEnemyUnits_NoPromptNoEffect instead
#//           boundary=ExcessBoundary_ExactlyLethal_NoBaseDamage vs ExcessBoundary_OneOverLethal_OneToBase
#//           control=N/A (no owner-scoped zone; "friendly"/"enemy" are controller reads and the
#//                 arena a unit sits in IS its controller's, so a stolen unit is already handled)
#//           reqboundary=N/A (the dealer is re-resolved from the step-1 answer inside step 2's own
#//                 continuation param; no transient global spans the decision)
#//
#// Baseline: a dealer WITHOUT Overwhelm. One friendly and one enemy means both picks auto-resolve.
#// SOR_095 is 3/3 → 3 damage onto SOR_046 (3/7), which survives. Base untouched.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0

---

# Overwhelm_ExcessGoesToEnemyBase
#// The rider. SOR_164 Wampa is 4/5 with Overwhelm (keyword-only text). It deals 4 to SOR_128 (3/1),
#// which dies to the first point — the other 3 spill onto the enemy base.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3

---

# NoOverwhelm_LethalDamage_NoExcessToBase
#// The load-bearing NEGATIVE for the rider: an identical lethal overkill, but the dealer has no
#// Overwhelm, so nothing reaches the base. Without this the positive above passes even if the excess
#// were dealt unconditionally.
#// SOR_095 (3/3, no Overwhelm) kills SOR_128 (3/1) with 2 points to spare.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0

---

# ExcessBoundary_ExactlyLethal_NoBaseDamage
#// Boundary upper half. Wampa's 4 power against SOR_063 (2/4) is EXACTLY lethal — 4 damage, 4 HP — so
#// the excess is 0 and the base takes nothing despite Overwhelm.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0

---

# ExcessBoundary_OneOverLethal_OneToBase
#// Boundary lower half, one HP smaller: Wampa's 4 against SEC_080 (3/3) leaves exactly 1 excess.
#// The pair is what pins the arithmetic — either section alone passes for a wrong formula.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1

---

# ShieldedTarget_AbsorbsAll_NoDamageAndNoExcess
#// The "what if the first half is PREVENTED" cell. A Shield token absorbs the entire instance, so the
#// target takes 0 damage and SURVIVES — which means there is no excess either, even though Wampa's 4
#// power would otherwise have overkilled a 1-HP unit by 3. An implementation that computed the excess
#// from power-minus-printed-HP rather than from damage actually dealt would wrongly hit the base for 3.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2BASEDMG:0

---

# PowerIsCurrentNotPrinted_IncludesUpgrades
#// "damage equal to its power" reads the unit's CURRENT power. SOR_095 (3/3) carrying SOR_120 Academy
#// Training (+2/+2) is a 5/5, so it deals 5, not its printed 3.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
P2BASEDMG:0

---

# SpaceArena_DealerHitsSpaceEnemyOnly
#// Arena matching, proven by OUTCOME on a board where both arenas are occupied. The only friendly is
#// in space (LOF_080 Exegol Patroller, 3/1, Overwhelm), so its target must be the SPACE enemy —
#// the ground enemy must be untouched even though it is the more obvious victim.
#// 3 power into SOR_237 (2/3) is exactly lethal, so no excess reaches the base either.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1SpaceArena: LOF_080:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# Offer_EnemyPoolIsArenaMatched
#// OFFER cell for STEP 2, left pending. The lone friendly is on the ground, so the enemy pool must be
#// the two GROUND units only — the space enemy is excluded by "in its arena". Two ground enemies keep
#// the choice from auto-resolving so the pool is inspectable.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# Offer_DealerPoolExcludesNoEnemyInArena
#// OFFER cell for STEP 1. A friendly unit with NO enemy in its arena can do nothing, so it must not be
#// selectable as the dealer — the zero-effect-selection rule (the Focus Fire JTL_129 precedent).
#// P1 has two ground units and one space unit; P2's only unit is on the ground, so the space friendly
#// is excluded while both ground friendlies remain legal.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Offer_DealerPoolExcludesZeroPower
#// OFFER cell for STEP 1, second exclusion: a 0-power friendly deals 0 damage, so it is a zero-effect
#// selection and must be excluded. SOR_063 (2/4) is reduced to 0 power by two Weakness tokens (-1/-1
#// each) while staying alive at 2 HP, which is what makes it an in-play 0-power unit at all.
#// The two 3-power friendlies stay legal so the pool remains inspectable.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0 SOR_063:1:0]
WithP1GroundArenaUpgrade: 2:HMW_T02
WithP1GroundArenaUpgrade: 2:HMW_T02
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:2:POWER:0
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# NoEnemyUnits_NoPromptNoEffect
#// No valid target anywhere: the opponent controls no units, so no friendly unit can be a legal dealer
#// and the event resolves as a clean no-op with no dangling decision. The event itself still goes to
#// the discard pile (it was played).

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1DISCARDCOUNT:1

---

# SpaceOverwhelm_ExcessGoesToEnemyBase
#// The Overwhelm rider on the SPACE side — the rider must not be ground-only. LOF_080 Exegol Patroller
#// (3/1, Overwhelm) deals 3 to SOR_225 TIE/ln Fighter (2/1): 1 point kills it, 2 spill to the base.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_114
WithP1SpaceArena: LOF_080:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:2

---

# TwinSuns_OverwhelmExcessHitsTheDEFENDERSBase
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — a DETERMINED seat, not a choice.
#// Overwhelm excess spills into the base of the seat whose unit just died. It went to OtherPlayer($player)
#// — seat 2 — so killing a SEAT 4 unit splashed a bystander's base while seat 4 took nothing.
#// The Wampa (4 power) kills seat 4's 3-HP Marine for 1 excess: seat 4's base takes it, seat 2's stays 0.
## GIVEN
CommonSetup: ggk/rrk
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 2
WithP1Hand: HMW_114
WithP1GroundArena: SOR_164:1:0
WithP4GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:p4GroundArena-0
## EXPECT
SEATCOUNT:4
P4GROUNDARENACOUNT:0
P4BASEDMG:1
P2BASEDMG:0
