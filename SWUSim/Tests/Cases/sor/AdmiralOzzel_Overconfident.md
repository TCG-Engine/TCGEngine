# OppDeclinesReady
#// SOR_129 Admiral Ozzel — the opponent's ready is a "may": declining leaves their unit exhausted.
#// Ozzel plays SEC_080 (enters ready); P2 declines the ready → its SOR_046 stays EXHAUSTED.
#// COVERAGE: offer=PlayOffer_ImperialsOnly (hand pool, pending SELECTABLEEXACT); the opponent-ready
#//           pool assertion is DEFERRED — open candidates: the ready pool omits the ability
#//           controller's units ("a unit" is unqualified), and both the choose-nothing and the
#//           no-Imperial-in-hand paths skip the opponent-ready clause entirely · decline=
#//           OppDeclinesReady (P2 side); the P1 choose-nothing decline branch is deferred with the
#//           same candidates · control=N/A (no persistent effect follows a unit) · boundary=
#//           deferred (empty play pool — same candidates) · reqboundary=covered by
#//           PlayOffer_ImperialsOnly (the pending pick survives to the end-state read)

## GIVEN
CommonSetup: ryk/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SOR_129:1:0
WithP1Hand: SEC_080
WithP1Hand: SOR_128
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
# P2>Pass drains the queued OZZEL_READY_OFFER builder (drain-time pool build, 2026-08-14); the
# resulting may-choose stays pending for the next line
- P2>Pass
- P2>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# PlayImperialEntersReady_OppReadies
#// SOR_129 Admiral Ozzel — Action [Exhaust]: Play an Imperial unit from your hand (paying its cost).
#// It enters play READY. Each opponent may ready a unit. Ozzel chooses SEC_080 from two hand Imperials;
#// it enters READY (not the default exhausted); the unchosen SOR_128 stays in hand; then P2 readies its
#// exhausted SOR_046. Ozzel is exhausted (paid the [Exhaust] action cost).

## GIVEN
CommonSetup: ryk/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SOR_129:1:0
WithP1Hand: SEC_080
WithP1Hand: SOR_128
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P2>Pass
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:READY
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:READY

---

# PlayOffer_ImperialsOnly
#// SOR_129 Admiral Ozzel — Intended: the play-from-hand pool is IMPERIAL units only. Hand holds
#// two Imperials (SEC_080, SOR_128) and a Rebel (SOR_095); the pick is left PENDING — the pool
#// must be exactly the two Imperials.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SOR_129:1:0
WithP1Hand: SEC_080
WithP1Hand: SOR_128
WithP1Hand: SOR_095
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# Declined_OppReadyStillOffered
#// Candidate #2 fix guard (decline leg): "Each opponent may ready a unit" is an UNCONDITIONAL
#// sentence — declining the play half must not skip it. P1 declines; P2 still gets the may-ready
#// and readies its exhausted SOR_046. Nothing is played; Ozzel is exhausted (the action cost).

## GIVEN
CommonSetup: ryk/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SOR_129:1:0
WithP1Hand: SEC_080
WithP1Hand: SOR_128
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
- P2>Pass
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1HANDCOUNT:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:READY

---

# NoImperialInHand_OppReadyStillOffered
#// Candidate #2 fix guard (empty-pool leg): with NO playable Imperial in hand the play half
#// resolves as nothing, but the opponent's may-ready still happens.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SOR_129:1:0
WithP1Hand: SOR_046
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P2>Pass
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:READY

---

# ReadyPool_IncludesCastersUnits_OfferAsserted
#// Candidate #2 fix guard (pool scope + drain-time build): the text is an unqualified "a unit" —
#// the opponent may ready ANY exhausted unit, including the caster's. After the play, P2's pool is
#// its own exhausted SOR_046 AND P1's now-exhausted Ozzel (the action cost — visible only if the
#// pool is built at drain time, after the action resolved). The ready-entering SEC_080 is not in
#// the pool (readying a ready unit is a no-op; pointless-prompt doctrine filters it). Left pending.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SOR_129:1:0
WithP1Hand: SEC_080
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P2>Pass

## EXPECT
P2SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
