# BouncedByTheVeryEventItObserved_StillTriggers
#// CORE MECHANIC — CR 778.3: "For a triggered ability to resolve, the card with the ability must be in
#// play when the triggering condition occurs … the triggered ability must resolve once triggered, EVEN
#// IF THE CARD WITH THE ABILITY LEAVES PLAY before the triggered ability resolves."
#// Paired with CR 319.6: for a "When you play an event" trigger, the event resolves as completely as
#// possible BEFORE the triggered ability — which is precisely the window in which the observer can
#// leave play.
#//
#// COVERAGE: offer=BouncedThenReplayed_TriggersExactlyOnce (the pool shrinks to the enemy unit once the
#//             observer is out of play, which is itself the tell that the departed copy is the one
#//             resolving) · decline=N/A (structural: this file tests WHO observes, not the observers'
#//             own optionality — each card's file owns that) · boundary=N/A (no threshold) ·
#//             control=N/A (an own-play observer is scoped to the playing player by construction) ·
#//             reqboundary=N/A (the snapshot rides the CUSTOM decision's own Param, which is
#//             serialised — it was never an in-memory global) ·
#//             modes=2P only (the snapshot is per-seat and the collector is scoped to the playing
#//             player) · TwinSuns=N/A · TeamSuns=N/A
#//
#// Bossk (SOR_182, "When you play an event: you may deal 2 damage to a unit") is in play when P1 plays
#// A New Adventure (SHD_207). ANA returns him to hand — he costs 5, inside its "6 or less" — and P1
#// DECLINES the free replay, so Bossk is sitting in HAND, entirely out of play, when his ability
#// resolves. It must resolve anyway.
#//
#// ⚠ THE INVERSE DIRECTION MUST STAY TRUE and is guarded elsewhere: a unit the event itself SEATS is
#// not an observer of that event (the Bossk verdict —
#// sor/Bossk_DeadlyStalker.md :: PlayedByOwnEvent_NoSelfTrigger). The observer set is the snapshot taken
#// when the event was PLAYED: a unit that left is still in it, a unit that arrived is not.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: SOR_182:1:0
WithP1Hand: SHD_207
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# BouncedThenReplayed_TriggersExactlyOnce
#// THE DOUBLE-FIRE GUARD, and the way this fix is most likely to go wrong. When the bounced observer is
#// replayed by the same event, the board briefly contains a copy of it again — but that is a NEW COPY
#// (CR 885) which was NOT in play when the event was played, so it is not in the snapshot and must not
#// observe. Exactly ONE resolution: 2 damage, not 4.
#// The departed original is what resolves; the new copy just stands there.
#// ⚠ FIXTURE: Bossk has AMBUSH, so REPLAYING him raises an "Ambush attack?" YESNO before his event
#// trigger is reached. Declining it keeps this section about the trigger COUNT; without that answer the
#// Ambush prompt silently eats the damage target and the section reads as the trigger never firing.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: SOR_182:1:0
WithP1Hand: SHD_207
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:NO
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_182
P1HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# ObserverStillInPlay_UnaffectedByTheChange
#// The ordinary case, unchanged: the observer is in play when the event is played AND still in play when
#// its ability resolves. A regression guard for the common path — the snapshot rebuild must not disturb
#// the overwhelming majority of own-play reactions, which never leave play at all.
#// SOR_251 Confiscate is the neutral do-nothing event (no upgrades in play, so it fizzles cleanly and
#// raises no decision of its own), leaving Bossk's offer as the only pending decision.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: SOR_182:1:0
WithP1Hand: SOR_251
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_182
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
