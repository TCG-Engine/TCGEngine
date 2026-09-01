# Action_NoEventInHand_NoOp
#// SOR_177 Bib Fortuna — the Action plays only an EVENT (not a unit). Here the hand holds
#// a UNIT (SOR_095, Battlefield Marine), no events, with resources to spare. The action has
#// no legal play, so it is a full no-op: Bib stays READY (action not spent), the unit stays
#// in hand, resources unchanged, no decision pending. Guards the event-only type filter
#// (distinguishing Bib from Alliance Dispatcher SOR_093, which plays a unit).
#// COVERAGE: offer=Offer_EventsOnly_UnitAndUpgradeExcluded (pending SELECTABLEEXACT over the hand:
#//           events only, unit + upgrade excluded) + Offer_AffordabilityBoundaryAfterTheDiscount ·
#//           boundary=Offer_AffordabilityBoundaryAfterTheDiscount (2-cost in / 3-cost out at 1 ready
#//           resource) + Discount_CostOneEventBecomesFree (the -1 floors at zero) ·
#//           control=UnderEnemyControl_PlaysFromTheControllersHand ("your hand" follows the
#//           CONTROLLER, not the owner) · reqboundary=EventPickSurvivesRequestBoundary ·
#//           decline=Decline_NoEventPlayed_ExhaustStillPaid — ⚠ currently RED. A "play a card from
#//           your HAND" offer must be declinable (the hand is a hidden zone, so no player can be
#//           compelled to reveal they held a playable card); SOR_177 raises a MANDATORY MZCHOOSE, so
#//           the branch is unreachable. Same printed shape and same offer helper as SOR_093 Alliance
#//           Dispatcher / TWI_120 Strategic Acumen, whose decline sections pass.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:SEC_080}
P1OnlyActions: true
WithP1GroundArena: SOR_177:1:0    # Bib Fortuna (ready) — index 0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:READY
P1HANDCOUNT:1
P1RESAVAILABLE:3
P1NODECISION

---

# Action_PlaysEventDiscounted
#// SOR_177 Bib Fortuna (1/3, Shielded) — Action [Exhaust]: Play an EVENT from your hand.
#// It costs 1 resource less. Host is the only friendly unit (idx 0, ready). Hand holds
#// Surprise Strike (SOR_220, Cunning, cost 2). With exactly 1 ready resource the event is
#// played ONLY because of the −1 discount (2 → 1); it then fizzles harmlessly ("attack with
#// a unit" finds no ready friendly unit — the host just exhausted itself) and goes to the
#// discard. The single resource is spent and Bib is exhausted. Single hand event → auto.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_220}
P1OnlyActions: true
WithP1GroundArena: SOR_177:1:0    # Bib Fortuna (ready) — index 0, only friendly unit

## WHEN
- P1>UseUnitAbility:myGroundArena-0
#// ⚠ One added answer, assertions untouched. Bib's hand-play offer is now a declinable
#// MZMAYCHOOSE (play-from-hand is always declinable — the hand is a hidden zone), so the lone
#// legal event no longer AUTO-RESOLVES and the pick has to be made explicitly.
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# Shielded_OnPlay_GivesShieldToken
#// SOR_177 Bib Fortuna (Cunning/Villainy, cost 2, 1/3) — clause 1: "Shielded (When you play this unit,
#// give him a Shield token.)" Played on-aspect for exactly 2; he arrives carrying one Shield token and
#// the friendly bystander gains nothing.

## GIVEN
CommonSetup: yyk/grw/{myResources:2;myhandCardIds:SOR_177}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_177
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1RESAVAILABLE:0

---

# Offer_EventsOnly_UnitAndUpgradeExcluded
#// SOR_177 Bib Fortuna — OFFER axis, type filter. "Play an EVENT from your hand" — the pool is the
#// hand's events and nothing else. Four cards in hand, all comfortably affordable at 8 resources so
#// that nothing is filtered out by COST: two events (SOR_220 Surprise Strike, SOR_216 Disarm) plus a
#// UNIT (SOR_095) and an UPGRADE (SOR_120). The decision is left PENDING so the offer itself is the
#// assertion — exactly the two event indexes, with the unit and the upgrade excluded.

## GIVEN
CommonSetup: yyk/grw/{myResources:8;myhandCardIds:SOR_220,SOR_216,SOR_095,SOR_120}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_177:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1
P1HANDCOUNT:4
P1DISCARDCOUNT:0

---

# Offer_AffordabilityBoundaryAfterTheDiscount
#// SOR_177 Bib Fortuna — OFFER axis, boundary pair. The −1 is applied BEFORE affordability is judged,
#// so with exactly 1 ready resource the cutoff sits between a 2-cost and a 3-cost event. All three
#// cards in hand are on-aspect Cunning events: SOR_216 (1 → free) and SOR_220 (2 → 1) are payable and
#// must be offered; SOR_222 Waylay (3 → 2) is one over the line and must NOT be. Decision left pending.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;myhandCardIds:SOR_216,SOR_220,SOR_222}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_177:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1
P1HANDCOUNT:3
P1RESAVAILABLE:1

---

# Discount_CostOneEventBecomesFree
#// SOR_177 Bib Fortuna — the −1 floors at zero rather than going negative or being refunded. SOR_216
#// Disarm (on-aspect Cunning, cost 1) is the only event in hand, so the pick auto-resolves; it is
#// played for FREE (all 8 resources still ready) and lands in the discard. It has no enemy unit to
#// debuff, so it simply fizzles — the point here is the payment, not the effect.

## GIVEN
CommonSetup: yyk/grw/{myResources:8;myhandCardIds:SOR_216}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_177:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
#// ⚠ One added answer, assertions untouched. Bib's hand-play offer is now a declinable
#// MZMAYCHOOSE (play-from-hand is always declinable — the hand is a hidden zone), so the lone
#// legal event no longer AUTO-RESOLVES and the pick has to be made explicitly.
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:8
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Decline_NoEventPlayed_ExhaustStillPaid
#// SOR_177 Bib Fortuna — DECLINE branch. A "play a card from your HAND" offer is always declinable:
#// the hand is a hidden zone, so a player can never be compelled to reveal that they were holding a
#// playable card. Two affordable events are offered (a genuine, non-auto-resolving choice) and the
#// player declines. Intended: nothing is played, the hand and the resources are untouched, and the
#// [Exhaust] cost is still spent — Bib ends exhausted with no decision pending.

## GIVEN
CommonSetup: yyk/grw/{myResources:8;myhandCardIds:SOR_216,SOR_220}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_177:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:0
P1RESAVAILABLE:8
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# UnderEnemyControl_PlaysFromTheControllersHand
#// SOR_177 Bib Fortuna — CONTROL CHANGE. Bib sits in P2's arena but is OWNED by P1 (the end state
#// after a take-control effect). "Play an event from YOUR hand" belongs to whoever CONTROLS him, so
#// P2 uses the action and plays out of P2's OWN hand: P2's hand drops from 2 to 1 and P2's discard
#// gains the event, while P1's hand — which also holds a legal event — is never touched. Two
#// affordable events on P2's side keep the pick a real choice rather than an auto-resolve.

## GIVEN
CommonSetup: yyk/yyk/{
  myResources:8;
  myhandCardIds:SOR_220;
  theirResources:8;
  theirhandCardIds:SOR_216,SOR_222
}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArenaControlled: SOR_177:1

## WHEN
- P2>UseUnitAbility:myGroundArena-0
- P2>AnswerDecision:myHand-0

## EXPECT
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_177
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# EventPickSurvivesRequestBoundary
#// SOR_177 Bib Fortuna — REQUEST BOUNDARY. The [Exhaust] payment and the −1 discount charge are
#// written during the action request and read during the LATER request that answers the hand pick, so
#// both must live in the serialized gamestate. Two events keep the pick genuinely pending across the
#// round-trip; SOR_220 (cost 2) is then played for 1, leaving 7 of 8 resources.

## GIVEN
CommonSetup: yyk/grw/{myResources:8;myhandCardIds:SOR_216,SOR_220}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_177:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-1

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1RESAVAILABLE:7
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# AlreadyExhausted_ActionIsAFullNoOp
#// SOR_177 Bib Fortuna — the NEGATIVE that proves the [Exhaust] cost is load-bearing. Bib starts
#// exhausted, so the action cannot be paid for: no event pool is built, nothing is played, the hand
#// and resources are untouched and no decision is raised.

## GIVEN
CommonSetup: yyk/grw/{myResources:8;myhandCardIds:SOR_216,SOR_220}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_177:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:0
P1RESAVAILABLE:8
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
