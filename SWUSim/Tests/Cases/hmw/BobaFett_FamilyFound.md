# SelfEntry_GrantsHimselfRaidAndSaboteur
#// COVERAGE: offer=N/A (the grant names no target — it always applies to the entering unit; the only
#//           pool involved is Ambush's own target choice, which is the keyword's, not this card's) ·
#//           decline=N/A (the grant is mandatory — no "may" anywhere in the clause) ·
#//           boundary=N/A (no numeric threshold; the gate is the binary "has Ambush", covered by
#//           NonAmbushUnit_GetsNothing) ·
#//           control=EnemyAmbushUnit_GetsNothing (the "friendly" scoping — an enemy unit entering with
#//           Ambush while Boba is in play must get nothing) ·
#//           reqboundary=SimulateRequestBoundary_GrantsSurviveTheBoundary
#//
#// HMW_225 Boba Fett - Family Found (Ground, 1/5, cost 3, Cunning, Tusken, unique)
#// "Ambush
#//  When a friendly unit with Ambush enters play (including this one):
#//   Give it Raid 1 and Saboteur for this phase."
#//
#// ⚠ PREVIEW SET — no card-specific-rulings.md entry for HMW, so the readings here are reasoned from the
#// CR plus the closest released analogue (ASH_041 Outcast, which carries the identical
#// "(including this one)" wording) and are FLAGGED rather than sourced.
#//
#// "(including this one)" is the self-trigger: Boba has Ambush himself, and by the time entry triggers
#// are collected he is already in the arena, so he observes his own arrival.
#// The enemy board is deliberately EMPTY — Ambush with no enemy unit adds no trigger at all, so this
#// section isolates the GRANT with no attack and no ordering prompt in the way.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_225

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_225
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid

---

# AnotherFriendlyAmbushUnit_GetsBoth
#// The other half of the observer: a DIFFERENT friendly unit with Ambush entering while Boba is already
#// out. LOF_257 Kowakian Monkey-Lizard is the ideal probe — its entire text is "Ambush" + reminder, and
#// it has NO aspect, so it costs 2 flat under any leader/base and brings no ability of its own that
#// could be confused for the grant.

## GIVEN
CommonSetup: yyk/bbw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_225:1:0
WithP1Hand: LOF_257

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LOF_257
P1GROUNDARENAUNIT:1:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid

---

# NonAmbushUnit_GetsNothing
#// ⚠ THE LOAD-BEARING NEGATIVE. The clause is gated on the ENTERING unit having Ambush — without this
#// section a build that grants to every friendly unit entering play (i.e. plain ASH_041 Outcast, which
#// is the code this is modelled on) passes every positive above.
#// SOR_128 Death Star Stormtrooper is a 3/1 with a completely blank text box — no keywords of its own,
#// so anything it ends up holding came from Boba. (Cost 1 + 2 for its unmatched Aggression pip = 3.)

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_225:1:0
WithP1Hand: SOR_128

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_128
P1GROUNDARENAUNIT:1:NOTKEYWORD:Saboteur
P1GROUNDARENAUNIT:1:NOTKEYWORD:Raid

---

# NoBobaInPlay_AmbushUnitGetsNothing
#// The observer's own presence must be load-bearing: the same Ambush unit entering with NO Boba on the
#// board gets nothing. Byte-identical to AnotherFriendlyAmbushUnit_GetsBoth except that Boba is absent,
#// so the two together isolate Boba as the sole cause.

## GIVEN
CommonSetup: yyk/bbw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: LOF_257

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_257
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# EnemyAmbushUnit_GetsNothing
#// "a FRIENDLY unit with Ambush" — an OPPONENT's Ambush unit entering while Boba is in play must get
#// nothing. This is the control-scoping cell: the observer keys on Boba's controller, not on "anyone".
#// Initiative is left UNCLAIMED so the turn genuinely alternates and P2 can act (P1OnlyActions would
#// make P2 auto-pass and the play would never happen).

## GIVEN
CommonSetup: yyk/bbw/{myResources:2; theirResources:2}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: HMW_225:1:0
WithP2Hand: LOF_257

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:LOF_257
P2GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# Raid1AppliesToHisOwnAmbushAttack
#// BOTH CLAUSES TOGETHER, and the section that pins the ORDER. Boba is 1 power; the Raid 1 he grants
#// himself on entry makes his Ambush attack deal 2. That only works if the grant is applied BEFORE the
#// bagged Ambush trigger is flushed — if the grant landed after the attack, the enemy 3/1 would survive
#// on 1 damage and Boba would be the only thing that changed.
#// ⚠ THE TARGET'S HP IS THE WHOLE POINT. SEC_237 Supreme Council Aide is a 2/2 vanilla, so 2 damage
#// (1 power + Raid 1) defeats it and 1 damage does NOT — the section reds the moment the Raid grant is
#// removed. An earlier draft used a 3/1, which dies to 1 damage just as readily: it carried this
#// section's name while proving nothing about Raid, and only the mutation pass exposed that.
#// Its 2 counter-damage leaves Boba (5 HP) alive on 2, so both halves of the combat stay observable.
#// ⚠ Ambush is offered as a YESNO ("it MAY attack an enemy unit"); with a single enemy unit the TARGET
#// auto-resolves, so `YES` is the only answer — a target mzID here is silently absorbed and no attack
#// happens, which reads exactly like the trigger never firing.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_225
WithP2GroundArena: SEC_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:HMW_225
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# SaboteurLetsHisAmbushAttackIgnoreSentinel
#// The Saboteur half, proven behaviourally rather than by the keyword flag alone. The enemy fields a
#// SENTINEL unit (SOR_063, 2/4) alongside a 3/1 — Sentinel normally forces an attack in that arena onto
#// itself, so without the granted Saboteur the 3/1 is not a legal Ambush target at all.
#// With Saboteur, Boba may ignore the Sentinel and ambush the 3/1: it is defeated (1 power + Raid 1 = 2)
#// and the Sentinel unit is left completely untouched, which is what separates "Saboteur applied" from
#// "the attack happened to hit something".

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_225
WithP2GroundArena: [SOR_063:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Duration_GrantsExpireNextPhase
#// "for this phase" must END. Without this, a permanent grant passes every other section in the file.
#// Boba is played on an empty enemy board (no Ambush attack), then both players pass to reach regroup
#// and resource-pass into the next action phase, where SWUExpireTurnEffects has stripped both tokens.
#// ⚠ Decks are seeded for both players: an empty deck at regroup costs the base 6 damage (CR 6.1
#// deck-out), which is noise that has faked results on other cards.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_225
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_225
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# TwinSuns_FarSeatBobaGrantsOnlyToItsOwnSeat
#// TWIN SUNS. "Friendly" is a controller relation, so at four seats a seat-3 Boba must grant to seat 3's
#// entering Ambush unit and to nobody else's. This section CANNOT PASS AT TWO SEATS — seat 3 does not
#// exist there — and it discriminates: seat 1 ALSO has an Ambush unit entering, and must get nothing.
#// LOF_257 is aspect-less, so neither seat pays a penalty despite seat 3 having no leader.

## GIVEN
CommonSetup: yyk/bbw/{myResources:2}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Resources: 2
WithP3GroundArena: HMW_225:1:0
WithP3Hand: LOF_257
WithP1Hand: LOF_257

## WHEN
- P3>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:1:CARDID:LOF_257
P3GROUNDARENAUNIT:1:HASKEYWORD:Saboteur
P3GROUNDARENAUNIT:1:HASKEYWORD:Raid

---

# SimulateRequestBoundary_GrantsSurviveTheBoundary
#// REQUEST BOUNDARY. The grant is written during the play action and read by a LATER action (the attack),
#// so the boundary goes between them. Both tokens live on the unit's serialized TurnEffects, so this is
#// expected to hold — the cell is written because this axis is the one a later validation pass provably
#// never backfills, and "expected to hold" is what was said about every transient that turned out not to.
#// ⚠ Boba must be PLAYED, not seeded: a unit placed by WithP1GroundArena never "enters play", so the
#// observer never fires and the section would assert against grants that were never made. He is played
#// onto an empty enemy board so no Ambush attack intervenes, then the boundary is crossed and both
#// grants must still be on him.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_225

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_225
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
