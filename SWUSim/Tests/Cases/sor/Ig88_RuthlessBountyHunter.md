# Front_Offer_ReadyUnitsBothArenas
#// SOR_012 IG-88, Ruthless Bounty Hunter — Leader, [Aggression][Villainy], deployed 5/4.
#// Front: "Action [Exhaust]: Attack with a unit. If you control more units than the defending
#// player, the attacker gets +1/+0 for this attack."
#// Deployed: "Each other friendly unit gains Raid 1."
#// COVERAGE: offer=Front_Offer_ReadyUnitsBothArenas (pending SELECTABLEEXACT: ready friendly units
#//           from BOTH arenas; the exhausted ground unit is excluded because an exhausted unit
#//           cannot attack) ·
#//           decline=N/A (the attacker pick is a mandatory chooser once the leader action is taken,
#//           and the deployed side is a static aura with nothing to decline) ·
#//           control=Front_ControlledEnemyOwnedUnitCounts (the outnumber check counts units you
#//           CONTROL, including an enemy-owned unit under your control) ·
#//           boundary=Front_EqualUnits_NoBuff (2v2, equal is NOT "more") vs
#//           Front_Outnumber_PlusOneForThisAttackOnly (2v1) — the strictly-greater threshold ·
#//           reqboundary=the outnumber judgment is settled at attacker selection and must survive
#//           into the attack across the separate target decision — every Front_* attack section
#//           spans the attacker→target decision chain.
#// This section pins the OFFER: P1 has a ready ground unit, an EXHAUSTED ground unit and a ready
#// space unit. Using the leader action offers exactly the two ready units (both arenas); the
#// decision is left pending so the offer itself is the assertion.

## GIVEN
CommonSetup: rrk/brw/{myLeader:SOR_012}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_128:1:0 SOR_164:0:0]
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# Front_EqualUnits_NoBuff
#// SOR_012 IG-88 (front) — EQUAL unit counts is not "more": with 2 friendly vs 2 enemy units the
#// chosen attacker gets no buff. The 3/3 attacks the enemy base for exactly 3. The leader action
#// exhausts IG-88 and the attack exhausts the attacker.

## GIVEN
CommonSetup: rrk/brw/{myLeader:SOR_012}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_128:1:0 SOR_095:1:0]
WithP2GroundArena: [SOR_128:1:0 SOR_095:1:0]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:3
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:1:POWER:3

---

# Front_Outnumber_PlusOneForThisAttackOnly
#// SOR_012 IG-88 (front) — with 2 friendly vs 1 enemy unit the attacker gets +1/+0 for this
#// attack: the 3/3 hits the base for 4. The bonus is attack-scoped: at the end state the unit's
#// power is back to its printed 3.

## GIVEN
CommonSetup: rrk/brw/{myLeader:SOR_012}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_128:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:1:POWER:3

---

# Front_ControlledEnemyOwnedUnitCounts
#// SOR_012 IG-88 (front) — the outnumber check is about units you CONTROL: P1 controls its own
#// 3/3 plus an enemy-OWNED Wampa (control previously taken), P2 controls 1 unit → 2 > 1, so the
#// 3/3 attacks the base for 4.

## GIVEN
CommonSetup: rrk/brw/{myLeader:SOR_012}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaControlled: SOR_164:2
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P1LEADER:EXHAUSTED

---

# Front_SpaceUnitCountsTowardOutnumber
#// SOR_012 IG-88 (front) — the count spans BOTH arenas: a friendly space unit tips 2 v 1 even
#// though the attack happens on the ground, so the 3/3 hits the base for 4.

## GIVEN
CommonSetup: rrk/brw/{myLeader:SOR_012}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P1LEADER:EXHAUSTED

---

# Deployed_OtherFriendlyGainsRaid1
#// SOR_012 IG-88 (deployed) — "Each other friendly unit gains Raid 1": with IG-88 on the ground,
#// the friendly 3/3 attacks the enemy base for 3 + 1 = 4. (The deployed leader seats AFTER the
#// pre-seeded unit: the 3/3 is ground index 0, IG-88 index 1.)

## GIVEN
CommonSetup: rrk/brw/{myLeader:SOR_012:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:1:CARDID:SOR_012
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P2BASEDMG:4

---

# Deployed_Ig88ItselfHasNoRaid
#// SOR_012 IG-88 (deployed) — the aura is "each OTHER friendly unit": IG-88 himself gains nothing
#// from it. With a friendly 3/3 also in play, IG-88 (5/4, ground index 1) attacks the base for
#// exactly his printed 5.

## GIVEN
CommonSetup: rrk/brw/{myLeader:SOR_012:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P2BASEDMG:5

---

# Deployed_AuraEndsWhenIg88Defeated
#// SOR_012 IG-88 (deployed) — the Raid 1 grant is tied to IG-88 being in play: P1 defeats his own
#// deployed IG-88 (ground index 1, behind the pre-seeded 3/3) with Rival's Fall (SHD_079; the
#// pool holds IG-88 and the 3/3, so the pick is a real prompt), the leader returns to the base
#// zone, and the 3/3's next attack on the base is back to its printed 3.

## GIVEN
CommonSetup: brk/brw/{myLeader:SOR_012:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_079
WithP1Resources: 6:SOR_128:1
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:1
P2BASEDMG:3

---

# Deployed_EnemyUnitsGetNoRaid
#// SOR_012 IG-88 (deployed) — the aura is FRIENDLY-only: with P1's IG-88 deployed, P2's 3/3
#// attacks P1's base for exactly its printed 3.

## GIVEN
CommonSetup: rrk/brw/{myLeader:SOR_012:1:1}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3

---

# SimulateRequestBoundary_OutnumberBuffSurvivesFreshProcess
#// SOR_012 IG-88 (front) — the leader action raises two decisions (attacker, then defender) and each
#// ends the request in production, so the outnumber judgment and the "+1/+0 for this attack" grant must
#// survive a fresh process on BOTH legs. Mirrors Front_Outnumber_PlusOneForThisAttackOnly with a
#// boundary before each answer: 2 friendly vs 1 enemy → the 3/3 still hits the base for 4 and is back
#// to its printed 3 at the end state.

## GIVEN
CommonSetup: rrk/brw/{myLeader:SOR_012}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_128:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:1:POWER:3
