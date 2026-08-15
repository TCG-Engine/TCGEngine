# Action_PlaysUnitDiscounted
#// SOR_093 Alliance Dispatcher (1/2) — Action [Exhaust]: Play a unit from your hand.
#// It costs 1 resource less. Host is the only arena unit (idx 0, ready). Hand holds
#// Battlefield Marine (SOR_095, Command/Heroism, cost 2). With exactly 1 ready resource
#// the play succeeds ONLY because of the −1 discount (2 → 1): the Marine enters the
#// ground arena, the single resource is spent, and the Dispatcher is exhausted.
#// (Extra answer since 2026-08-14: this "you may" offer no longer auto-resolves a lone target —
#// the single hand unit myHand-0 is now named explicitly.)
#// COVERAGE: offer=Offer_UnitsOnly_EventExcluded (pending SELECTABLEEXACT over hand mzIDs) ·
#//           decline=Decline_DispatcherExhausted_NextPlayFullPrice (also pins that the −1 does NOT
#//           linger onto a later normal play) · reqboundary=Decline_* (the hand pick and the
#//           follow-up play are separate requests) · boundary pair=Action_PlaysUnitDiscounted
#//           (affordable only via the −1, cost 2 with 1 resource) + Action_Unaffordable_NoOp
#//           (unaffordable even with the −1 → refuses to activate) · control=N/A (the discount is a
#//           one-shot channel on the dispatcher's own play action; no per-unit marker)

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: SOR_093:1:0    # Alliance Dispatcher (ready) — index 0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# Action_Unaffordable_NoOp
#// SOR_093 Alliance Dispatcher — the Action can only be taken if there is a unit in hand
#// the player can actually play at the −1 discount. Here the hand unit (SOR_095, cost 2 →
#// discounted 1) is unaffordable with 0 ready resources, so the action has no legal play
#// and is a full no-op: the Dispatcher stays READY (action not spent), the Marine stays in
#// hand, no resources change, and no decision is pending.

## GIVEN
CommonSetup: ggw/ggw/{myResources:0;handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: SOR_093:1:0    # Alliance Dispatcher (ready) — index 0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:READY
P1HANDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# Offer_UnitsOnly_EventExcluded
#// Intended: "Play a UNIT from your hand" — the pick is over hand cards and holds only the two
#// units; Waylay (an event, hand index 0) is outside the pool. Ample resources keep both units
#// affordable. The decision is left pending so the offer itself is asserted.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5;handCardIds:SOR_222,SOR_095,SOR_046}
P1OnlyActions: true
WithP1GroundArena: SOR_093:1:0    # Alliance Dispatcher (ready)

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-1&myHand-2

---

# Decline_DispatcherExhausted_NextPlayFullPrice
#// Intended: the play is optional — declining still leaves the Dispatcher EXHAUSTED (the cost was
#// paid), and the unused −1 does NOT linger: the Marine played normally afterwards costs its full
#// 2 (3 resources → 1 left). Two on-aspect affordable hand units (Marine cost 2→1, Echo Base
#// Defender cost 3→2) keep the pick interactive for the decline.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3;handCardIds:SOR_095,SOR_098}
P1OnlyActions: true
WithP1GroundArena: SOR_093:1:0    # Alliance Dispatcher (ready)

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1HANDCOUNT:1
P1RESAVAILABLE:1
P1NODECISION

---

# PilotableUnit_PlaysAsGroundUnit_NotAsPilot
#// Intended: the granted play is a UNIT play only — a unit with Piloting (JTL_203 Han Solo,
#// ground 5 / Piloting 2) picked through the Dispatcher never gets the pilot-attach option even
#// with a friendly Vehicle in play: he lands in the ground arena at the discounted ground cost
#// (5−1=4, exactly the 4 resources available), and the Turncoat carries no new upgrade. Sole hand
#// unit → picked explicitly; no enemy units, so Ambush has nothing to prompt about.
#// (Extra answer since 2026-08-14: this "you may" offer no longer auto-resolves a lone target.)

## GIVEN
CommonSetup: yyw/yyw/{myResources:4;handCardIds:JTL_203}
P1OnlyActions: true
WithP1GroundArena: SOR_093:1:0    # Alliance Dispatcher (ready)
WithP1SpaceArena: SHD_195:1:0    # Cartel Turncoat — a friendly Vehicle he must NOT board

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_203
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1NODECISION

---

# Decline_SingleTarget_NoUnitPlayed
#// SOR_093 Alliance Dispatcher — declining is now possible even when the hand holds exactly ONE
#// playable unit (since 2026-08-14 a lone target no longer auto-resolves). Mirrors
#// Action_PlaysUnitDiscounted but P1 answers "-": the Marine stays in hand, the arena keeps only the
#// Dispatcher and the 1 resource stays ready — yet the action's cost was still paid, so the
#// Dispatcher is EXHAUSTED.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: SOR_093:1:0    # Alliance Dispatcher (ready) — index 0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1
P1RESAVAILABLE:1
P1NODECISION
