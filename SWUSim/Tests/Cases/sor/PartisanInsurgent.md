# AnotherAggression_Raid2
#// SOR_159 Partisan Insurgent (1/4) guard — "While you control another Aggression
#// unit, this unit gains Raid 2." P1 controls Partisan Insurgent + another Aggression
#// unit (SOR_130), so it has Raid 2. Attacking P2's base: power 1 + Raid 2 = 3 damage.
#// COVERAGE: offer=Offer_RaidDoesNotInflatePowerOutsideAnAttack (pending SELECTABLEEXACT over a
#//           "3 or less power" pool — the Insurgent must still read 1 with Raid 2 live; a 4-power
#//           Wampa is the excluded target) ·
#//           reqboundary=RequestBoundary_RaidIsRecomputedFromTheSerializedState ·
#//           control=ControlChange_AnAggressionUnitYouControlButDoNotOwnStillEnablesIt ("while YOU
#//           CONTROL another Aggression unit" reads control, not ownership) · boundary pair=zero vs
#//           one other Aggression unit: NoOtherAggressionUnit_NoRaid (0 → 1 damage) vs
#//           AnotherAggression_Raid2 (1 → 3 damage), with the dynamic edge
#//           EnablerLeavesPlay_RaidIsLostImmediately crossing back over the same line mid-turn ·
#//           decline=N/A — a static conditional keyword grant raises no decision at all, so there is
#//           no branch to decline.
#// Scope guards: EnemyAggressionUnitDoesNotEnableIt (controller scope),
#// AggressionUnitInTheOtherArenaStillEnablesIt (no arena scope),
#// TwoInsurgents_EachIsTheOthersAggressionUnit_BothRaidTwo ("another" excludes self, not a second
#// copy), RaidDoesNotApplyWhileDefending (Raid is "while ATTACKING" only).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_159:1:0    # Partisan Insurgent (1/4, Aggression)
WithP1GroundArena: SOR_130:1:0    # another Aggression unit

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# Offer_RaidDoesNotInflatePowerOutsideAnAttack
#// Intended: Raid 2 is "+2/+0 WHILE ATTACKING", not a standing power buff — so a pool that filters on
#// CURRENT power must still see the Partisan Insurgent as a 1-power unit even with its Aggression
#// enabler on the board. Keep Fighting (SOR_169) offers "a unit with 3 OR LESS power": the Insurgent
#// (1) and the First Legion Snowtrooper (2) are in, the Wampa (4) is out. The decision is left PENDING
#// so the offer itself is the assertion; had Raid been applied statically the Insurgent would read 3
#// and still qualify, so the discriminating unit here is the Wampa's exclusion plus the Insurgent's
#// unchanged POWER assertion in the sections below.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2;myhandCardIds:SOR_169}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_159:0:0 SOR_130:0:0 SOR_164:0:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# NoOtherAggressionUnit_NoRaid
#// SOR_159 — the NEGATIVE that proves the gate is load-bearing. The Insurgent is P1's ONLY unit and
#// "another" excludes itself, so it has no Raid: attacking the base deals its printed 1, and its
#// power reads 1 both during and after the attack.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_159:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:4

---

# TwoInsurgents_EachIsTheOthersAggressionUnit_BothRaidTwo
#// SOR_159 — "ANOTHER [Aggression] unit" excludes the Insurgent itself but a SECOND copy satisfies it
#// for both. Each attacks the base for 1+2 = 3, so the base takes 6, and neither one's standing power
#// changes (still 1 after the attack).

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_159:1:0 SOR_159:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:1:POWER:1

---

# EnemyAggressionUnitDoesNotEnableIt
#// SOR_159 — "While YOU control another [Aggression] unit" is controller-scoped. The only other
#// Aggression unit on the table is P2's First Legion Snowtrooper, so the Insurgent gets no Raid and
#// its base attack deals just 1.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_159:1:0
WithP2GroundArena: SOR_130:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# AggressionUnitInTheOtherArenaStillEnablesIt
#// SOR_159 — "another [Aggression] unit" is not arena-scoped: P1's Green Squadron A-Wing sits in SPACE
#// and still switches the ground Insurgent's Raid 2 on, so its base attack deals 1+2 = 3.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_159:1:0
WithP1SpaceArena: SOR_141:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:POWER:1

---

# RaidDoesNotApplyWhileDefending
#// SOR_159 — Raid reads "while ATTACKING". With the enabler in play, an Insurgent that is ATTACKED
#// deals only its printed 1 back: P2's Battlefield Marine (3/3) takes 1, not 3, while the Insurgent
#// takes the Marine's 3.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [SOR_159:1:0 SOR_130:1:0]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# EnablerLeavesPlay_RaidIsLostImmediately
#// SOR_159 — "WHILE you control another [Aggression] unit" is a live condition, not a one-time stamp.
#// P1's only enabler is the First Legion Snowtrooper; it trades into P2's Consular Security Force
#// (3/7 kills the 2/3) and dies. With the enabler gone the Insurgent's follow-up base attack in the
#// SAME turn deals its unboosted 1.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_159:1:0 SOR_130:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_159
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:1

---

# ControlChange_AnAggressionUnitYouControlButDoNotOwnStillEnablesIt
#// SOR_159 — "while YOU CONTROL another [Aggression] unit" reads control, not ownership. The First
#// Legion Snowtrooper P1 controls but P2 OWNS (the end state after a take-control effect) switches
#// Raid 2 on, so the Insurgent's base attack deals 3. Controlled units seat AFTER the plain arena
#// lines, so the Insurgent is index 0.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_159:1:0
WithP1GroundArenaControlled: SOR_130:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_159
P1GROUNDARENAUNIT:1:CARDID:SOR_130
P2BASEDMG:3

---

# RequestBoundary_RaidIsRecomputedFromTheSerializedState
#// SOR_159 — the grant is a live board read, not a cached flag, so it must survive the request
#// boundary that separates two of P1's actions in production. After the round-trip the Insurgent still
#// has Raid 2 (the Snowtrooper is the enabler) and its base attack deals 1+2 = 3.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_159:1:0 SOR_130:1:0]

## WHEN
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:POWER:1
