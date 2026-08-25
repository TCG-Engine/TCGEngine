# Gate_AnotherTuskenUnit_OffersExhaust
#// COVERAGE: offer=Offer_ReadyGroundUnitsBothSides_ExcludesExhaustedAndSpace (SELECTABLEEXACT proves
#//           both sides, ready-only and ground-only in one pool) + TwinSuns_OfferReachesEveryLiveSeat
#//           decline=Decline_NothingIsExhausted (and NoReadyGroundUnit_GateOpenButNoPrompt — a
#//           fizzle-only optional is not offered at all, which is a different branch from declining)
#//           boundary=N/A — no numeric threshold anywhere in the text. The gate is two boolean limbs,
#//           each with its own negative (self-is-not-"another", non-Tusken, enemy Tusken, non-Tatooine
#//           base, opponent's Tatooine base).
#//           control=N/A — the only ability is a When Played, which does not re-fire on a later control
#//           change. Seat scope is pinned instead by Gate_EnemyTuskenDoesNotCount /
#//           Gate_OpponentsTatooineBaseDoesNotCount.
#//           reqboundary=AcrossTheRequestBoundary
#//
#// HMW_230 Raiding Party — Unit (Ground) 0/6, cost 5, [Cunning], Tusken, non-unique.
#// "Raid 6 (This unit gets +6/+0 while attacking.)
#//  When Played: If you control another Tusken unit or a Tatooine base, you may exhaust a ground unit."
#//
#// TWO independent gate limbs joined by OR, then an optional effect. The effect clause is word-for-word
#// SHD_201 Principled Outlaw's "You may exhaust a ground unit", so it follows the same house rules:
#// "a ground unit" carries NO controller qualifier and so spans BOTH sides, and only READY units are
#// offered (an already-exhausted unit is not a meaningful target — the exhaust-only-ready convention,
#// cf. SEC_069 Nimble Prowess).
#//
#// Base 'y' is SOR_029 Administrator's Tower (Cloud City) — deliberately NOT Tatooine, so every section
#// that does not override the base is testing the Tusken limb in isolation.
#// LOF_209 Tusken Tracker is the friendly Tusken; it is seeded into the arena, so its own When Played
#// never fires.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_230
WithP1GroundArena: LOF_209:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_230
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:READY

---

# Gate_SelfIsNotAnotherTuskenUnit_NoPrompt
#// ⚠ THE "ANOTHER" NEGATIVE, and the sharpest one on this card: Raiding Party is ITSELF a Tusken unit,
#// so an implementation that counts friendly Tuskens without excluding the source opens its own gate
#// every single time and passes every positive section in this file.
#// Board: no other friendly unit, base is Cloud City. Both limbs are false, so there must be no prompt
#// and the enemy must be left alone.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_230
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_230
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# Gate_NonTuskenFriendlyUnit_DoesNotOpenTheGate
#// The trait is load-bearing, not merely "another friendly unit". SEC_214 Skyhopper Canyon Runner is a
#// Fringe/Vehicle/Speeder with a blank text box — a friendly ground unit in every respect except the
#// one that matters.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_230
WithP1GroundArena: SEC_214:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# Gate_EnemyTuskenDoesNotCount
#// "If YOU CONTROL another Tusken unit" — controller scope. The opponent fielding a Tusken must not
#// open my gate. A pool built from both arenas (which the EFFECT legitimately uses) would wrongly
#// satisfy the CONDITION too, so the two must not share a collector.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_230
WithP2GroundArena: [LOF_209:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:READY
P1NODECISION

---

# Gate_TatooineBase_OffersExhaust
#// THE SECOND LIMB ALONE. SHD_026 Jabba's Palace is a 30-HP Cunning Tatooine base with a blank text
#// box, so it changes nothing except the trait. No friendly Tusken is in play, so this section fails
#// outright if the base limb was never implemented — the two limbs cannot cover for each other here.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_230
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# Gate_OpponentsTatooineBaseDoesNotCount
#// "If YOU CONTROL ... a Tatooine base" — the OPPONENT's Tatooine base is not yours. The mirror of the
#// enemy-Tusken negative, and the case a base lookup that reads the wrong seat gets wrong.

## GIVEN
CommonSetup: yyk/yyk/{theirBase:SHD_026;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_230
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# Offer_ReadyGroundUnitsBothSides_ExcludesExhaustedAndSpace
#// ⚠ THE OFFER — three independent restrictions proven in one pool, none of which any "answer a target"
#// section can see. Gate opened by the Tatooine base so the friendly board is free to be non-Tusken.
#// After Raiding Party enters, the boards are:
#//   myGroundArena-0    SEC_214  ready  ground  → OFFERED (a FRIENDLY unit is a legal target: "a ground
#//                                                unit" carries no controller qualifier)
#//   myGroundArena-1    HMW_230  EXHAUSTED      → excluded; it entered play exhausted like any unit
#//   theirGroundArena-0 SOR_046  ready  ground  → OFFERED (enemy side)
#//   theirGroundArena-1 SOR_128  EXHAUSTED      → excluded by the ready-only rule
#//   theirSpaceArena-0  SOR_225  ready  SPACE   → excluded by the arena
#// Each exclusion has a DIFFERENT cause, so a pool that is wrong in any one way fails here.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_230
WithP1GroundArena: SEC_214:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_128:0:0]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Decline_NothingIsExhausted
#// "You MAY exhaust" — the decline branch, with two legal targets so the choice is genuinely open.
#// Nothing may move, and no decision may be left dangling.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_230
WithP1GroundArena: SEC_214:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# NoReadyGroundUnit_GateOpenButNoPrompt
#// A DIFFERENT branch from declining: the gate is open, but every ground unit on the board is already
#// exhausted, so there is nothing the effect could do. A "you may" whose every outcome is a no-op must
#// not be offered at all (the fizzle-only-optional rule) — asserting P1NODECISION is the whole point,
#// since the end state is identical either way.
#// Raiding Party's own entry-exhausted body is the only friendly unit, and the enemy's is seeded
#// exhausted.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_230
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# EntersReadyViaRitualDragon_CanExhaustItself
#// VALUE-CLASS variant: normally Raiding Party enters exhausted and so cannot be its own target, which
#// makes "excluded because exhausted" and "excluded because it is the source" indistinguishable in
#// every other section. HMW_234 Ritual Dragon ("While you control a Tatooine base, friendly units enter
#// play ready (including this one)") separates them — Raiding Party enters READY, and since the text
#// says "a ground unit" with no "other", it is a legal target for its own ability.
#// The same Tatooine base opens the gate, so one fixture does both jobs. Ritual Dragon is a Creature,
#// not a Tusken, so the Tusken limb stays shut.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_230
WithP1GroundArena: HMW_234:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_230
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:READY

---

# Raid6_AttackingPowerIsSix
#// Raid 6 on a 0-power body: the keyword IS this unit's entire offense. Seeded straight into the arena
#// so no When Played is involved.
#// POWER:0 afterwards is the other half — Raid is a while-attacking bonus, not a standing stat change,
#// and asserting only the base damage would pass just as well for a permanent +6/+0.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_230:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Raid6_DoesNotApplyWhileDefending
#// THE RAID NEGATIVE, and unusually loud on a 0-power unit: "while ATTACKING" means that as the
#// DEFENDER Raiding Party deals its printed 0 combat damage, so the attacker walks away untouched.
#// An unconditional +6/+0 would deal 6 back and defeat SOR_046 outright, so this section cannot pass
#// under that bug.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5;theirResources:5}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: HMW_230:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_230
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1

---

# AcrossTheRequestBoundary
#// THE REQUEST-BOUNDARY CELL. The exhaust pick is interactive, so in production it ends the request and
#// the continuation resolves in a FRESH process. Anything the ability held in an in-memory global
#// between evaluating the gate and applying the exhaust — the gate result, the source's identity, the
#// candidate list — is gone by then, and the exhaust silently never happens.
#// Same board and answer as Gate_AnotherTuskenUnit_OffersExhaust, with one boundary inserted.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_230
WithP1GroundArena: LOF_209:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:READY

---

# TwinSuns_OfferReachesEveryLiveSeat
#// ⚠ THE SEAT-COUNT CELL — the offer pool, at four seats. "A ground unit" names no player, so it spans
#// EVERY live seat's ground arena, not just the one the two-player engine calls "their".
#// The neighbouring implementation of this exact clause (SHD_201 Principled Outlaw) enumerates
#// ['myGroundArena','theirGroundArena'], which silently truncates to seats 1 and 2 — copying that shape
#// here would leave seat 3's unit permanently untargetable with the suite still green.
#// Seat 2 and seat 3 each field one ready ground unit; seat 4's is EXHAUSTED, so the pool must contain
#// exactly two entries.
#// ⚠ At four seats there is no "their" — SWUForeignMzID addresses EVERY foreign seat positionally, so
#// seat 2 comes back as p2GroundArena-0 rather than theirGroundArena-0. That is the tell that the pool
#// is genuinely seat-addressed instead of "the two-seat pair plus some extras bolted on"; a
#// theirGroundArena-0 in this list would mean seat 2 was still being reached the old way.
#// (At two seats SWUForeignMzID collapses back to "their…", so Premier is byte-identical.)

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1Hand: HMW_230
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_128:1:0
WithP4GroundArena: SEC_214:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:p2GroundArena-0&p3GroundArena-0

---

# TwinSuns_ExhaustsAFarSeatsGroundUnit
#// The end-to-end half of the seat-count cell: choosing seat 3's unit must actually exhaust it. At two
#// seats the mzID p3GroundArena-0 does not exist at all, so this section cannot pass there — and seat
#// 2's unit must be left untouched, which is what proves the answer resolved to the seat it names
#// rather than to whichever unit the two-seat frame happened to put at that index.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1Hand: HMW_230
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:CARDID:SOR_128
P3GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:READY
