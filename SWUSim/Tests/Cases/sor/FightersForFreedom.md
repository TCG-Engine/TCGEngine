# Decline
#// SOR_143 Fighters for Freedom (Unit, cost 3, [Aggression][Heroism], Rebel/Trooper, non-unique, 3/4)
#// — "Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When you
#// play another [Aggression] card: You may deal 1 damage to a base."
#// COVERAGE: offer=Offer_BothBasesAreLegalTargets (menu asserted on a PENDING MZMAYCHOOSE — the
#//           unqualified "a base" spans BOTH bases) with DealsToYourOwnBase exercising the half that
#//           an opponent-hardcoded pool would lose · decline=Decline ('-', neither base touched) ·
#//           boundary pair=Boundary_TwoCopies_TriggerTwice (2 copies → 2 damage) vs
#//           PlayAggression_DealsBase (1 copy → 1 damage), which also pins the "another"
#//           self-exclusion at exactly one · control
#//           change=ControlChange_AStolenFightersTriggersForItsCONTROLLER +
#//           ControlChange_AFightersYouOWNButDoNotCONTROLDoesNotTrigger (same card, control flipped,
#//           opposite answers) · request boundary=structural in every resolving section — the play is
#//           one request and the reaction's target answer is a SEPARATE one, so the base pool is
#//           rebuilt from serialized state; Offer_BothBasesAreLegalTargets reads that rebuilt pool
#//           with the decision still open, and Boundary_TwoCopies_TriggerTwice carries a trigger-order
#//           MZCHOOSE plus two answers across three requests.
#// Trigger-gate negatives: NonAggression_NoTrigger (wrong aspect),
#// OpponentPlaysAnAggressionCard_NoTrigger (wrong player), DeployAggressionLeader_DoesNotDeal
#// (deployed ≠ played), AggressionEVENTAlsoTriggersIt ("card" covers events, not just units).
#// Saboteur is the printed reminder keyword and is exercised centrally, not here.
#// This section: decline the optional "deal 1 to a base" reaction.
#// Playing another Aggression card triggers FFF, but the player passes → no base damage.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:SOR_143}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION

---

# DeployAggressionLeader_DoesNotDeal
#// SOR_143 Fighters for Freedom — "When you play another [Aggression] card: you may deal 1 to a base."
#// FFF#1 in play; P1 deploys Sabine (Red Hero leader); not "played" so no trigger

## GIVEN
CommonSetup: rrw/rrk/{myResources:4}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:2
P2BASEDMG:0
P1NODECISION

---

# NonAggression_NoTrigger
#// SOR_143 Fighters for Freedom — a NON-Aggression card does NOT trigger the reaction.
#// Absence guard: Confiscate is a neutral event (no Aggression aspect), so FFF stays silent.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:SOR_251}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION

---

# PlayAggression_DealsBase
#// SOR_143 Fighters for Freedom — "When you play another [Aggression] card: you may deal 1 to a base."
#// FFF#1 in play; P1 plays a SECOND FFF (an Aggression card). FFF#1 reacts → deal 1 to a base.
#// Also proves the "another" self-exclusion: only FFF#1 triggers (the just-played FFF#2 is excluded),
#// so after one base-deal there is NO second pending decision.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:SOR_143}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:2
P2BASEDMG:1
P1NODECISION

---

# Offer_BothBasesAreLegalTargets
#// THE OFFER CELL, asserted on a PENDING decision. "You may deal 1 damage to A BASE" names no
#// controller, so per the unqualified-target rule BOTH bases are legal — your own included. P1 plays a
#// second Fighters for Freedom (an [Aggression] card), FFF#1 reacts, and the menu is left unanswered.
#// Two legal targets is also what keeps the choice interactive; the resolutions live in
#// PlayAggression_DealsBase (enemy base) and DealsToYourOwnBase (own base).

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:SOR_143}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# DealsToYourOwnBase
#// The other half of the unqualified "a base": the player may point the 1 damage at their OWN base.
#// Same board and same trigger as PlayAggression_DealsBase, answered myBase-0 instead — P1's base
#// takes the 1 and P2's stays clean, which is only possible if the pool really does span both sides
#// rather than being hardcoded to the opponent.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:SOR_143}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:1
P2BASEDMG:0
P1GROUNDARENACOUNT:2
P1NODECISION

---

# AggressionEVENTAlsoTriggersIt
#// "When you play another [Aggression] CARD" — card, not unit. P1 plays SOR_169 Keep Fighting, a
#// mono-[Aggression] EVENT (its own effect is a no-op here: Fighters for Freedom is power 3 and
#// already ready, and is the only unit within its "3 or less power" window, so it auto-resolves onto
#// itself and readies an already-ready unit). The reaction still fires and puts 1 on P2's base.
#// Distinct from PlayAggression_DealsBase, where the played card is a UNIT.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:SOR_169}
P1OnlyActions: true
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_143
P1GROUNDARENAUNIT:0:READY
P1NODECISION

---

# OpponentPlaysAnAggressionCard_NoTrigger
#// "When YOU play another [Aggression] card" — the reaction belongs to the CONTROLLER of Fighters for
#// Freedom and reads only that player's plays. P2 plays SOR_128 Death Star Stormtrooper
#// ([Aggression][Villainy], cost 1) while P1 controls FFF: no trigger, no decision on either seat, and
#// neither base is touched.
#// The counterpart to NonAggression_NoTrigger — there the ASPECT disqualified the play, here the
#// PLAYER does.

## GIVEN
CommonSetup: rrw/rrk/{}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_143:1:0
WithP2Hand: SOR_128
WithP2Resources: 4

## WHEN
- P2>PlayHand:0

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION
P2NODECISION
P2GROUNDARENACOUNT:1

---

# ControlChange_AStolenFightersTriggersForItsCONTROLLER
#// OWNER ≠ CONTROLLER, half one. The Fighters for Freedom in P1's arena is OWNED by P2 (the end state
#// of a take-control effect). "When YOU play another [Aggression] card" is resolved from the
#// CONTROLLER's seat, so P1's own play of a second Fighters for Freedom triggers it, P1 is the seat
#// that receives the decision, and P1 chooses the target.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:SOR_143}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_143:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1BASEDMG:0
P1GROUNDARENACOUNT:2
P1NODECISION

---

# ControlChange_AFightersYouOWNButDoNotCONTROLDoesNotTrigger
#// THE DISCRIMINATING HALF. The Fighters for Freedom is OWNED by P1 but CONTROLLED by P2, and P1 plays
#// an [Aggression] card. Under "when YOU play", the "you" is P2 — who played nothing — so the reaction
#// must stay silent: no decision on either seat and no base damage anywhere.
#// If the reaction were wired to the card's OWNER it would fire here, which is exactly the failure the
#// paired section above cannot see.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:SOR_143}
P1OnlyActions: true
WithP2GroundArenaControlled: SOR_143:1

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION
P2NODECISION
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1

---

# Boundary_TwoCopies_TriggerTwice
#// QUANTITY DISCRIMINATION — one Fighters for Freedom deals 1, TWO of them deal 2. Both copies are
#// already in play (non-unique, so they may coexist) and P1 plays a THIRD as the [Aggression] card;
#// the "another" self-exclusion removes only the just-played copy, so exactly two reactions fire and
#// the player orders them, then answers each. P2's base ends on 2.
#// Read against PlayAggression_DealsBase (one copy, 1 damage) this proves the reaction is per-COPY
#// rather than a once-per-play effect, and that the self-exclusion subtracts exactly one.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:SOR_143}
P1OnlyActions: true
WithP1GroundArena: [SOR_143:1:0 SOR_143:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1BASEDMG:0
P1GROUNDARENACOUNT:3
P1NODECISION
