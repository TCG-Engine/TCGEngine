# DealsFourToEachEnemyGround
#// LAW_179 Fear and Dead Men (Aggression,Villainy event, cost 7) — cost reduction (1 less per card
#// discarded from hand this phase) handled by the play-cost modifier; effect: "Deal 4 damage to each
#// enemy ground unit." SOR_046 (3/7) survives at DAMAGE:4; SOR_095 (3/3) dies.

## GIVEN
CommonSetup: rrk/bgw/{myResources:7}
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_179

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1

---

# ForcedHandDiscount_CountsOpponentInducedDiscards
#// LAW_179 "costs 1 less per card discarded from your hand this phase" must count FORCED discards too —
#// e.g. an opponent's Pillage (SHD_181) making you discard. P2 Pillages P1: P1 discards 2 of 3 cards
#// (keeping LAW_179), so LAW_179 costs 7-2=5. P1 has exactly 5 resources → it is playable ONLY because the
#// two forced discards counted (regression: SWUDiscardCards previously never set SWU_DISCARDED_HAND).

## GIVEN
CommonSetup: rrk/brk/{theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 5
WithP2Resources: 8
WithP1Hand: LAW_179
WithP1Hand: SOR_095
WithP1Hand: SOR_063
WithP2Hand: SHD_181
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:myHand-2
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# NoEnemyGroundUnits_NoEffectStillPlays
#// LAW_179 Fear and Dead Men — with no enemy GROUND units in play the event has nothing to damage; it
#// still resolves with no effect and goes to the discard pile. A friendly ground unit (SOR_095) and an
#// enemy SPACE unit (SOR_237) are both untouched.

## GIVEN
CommonSetup: rrk/bgw/{myResources:7}
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_179

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0

---

# PlayingAnEventDoesNotDiscountThisOne
#// LAW_179 — "costs 1 resource less for each card DISCARDED FROM YOUR HAND this phase". A card you PLAY
#// is not a card you discard, even though it ends up in the same pile. P1 plays Urgent Mission (4 here)
#// and then this event at its full 7: 12 - 4 - 7 = 1 resource left, and all enemy ground units die.
#// Shares its root cause with LAW_076 Vult Skerris's Defender — the discard funnel's per-hand counters
#// counted an event's own play. A discount of 1 would leave 2 resources instead.

## GIVEN
CommonSetup: rrk/yyw/{myResources:12}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_64 LAW_179]
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1
P2GROUNDARENACOUNT:0

---

# ForcedHandDiscount_SurvivesTheRequestBoundary
#// LAW_179 — request-boundary guard for ForcedHandDiscount_CountsOpponentInducedDiscards: same fixture,
#// one extra SimulateRequestBoundary between the TWO Pillage discards. Production starts a FRESH process
#// on every answered decision, so the running "cards discarded from your hand this phase" tally — already
#// at 1 when the boundary hits — has to be reconstructed from serialized gamestate rather than an
#// in-memory counter. Both discards must still count: LAW_179 costs 7-2 = 5, P1 has exactly 5, so it is
#// playable only if the pre-boundary discard survived, and it lands its 4 on the enemy ground unit.
#// The insertion point is a genuine pending MZCHOOSE (Choose_card_to_discard over P1's remaining hand).
#// The second discard is answered as myHand-1 rather than the pre-boundary section's myHand-2 because
#// the re-parsed decision re-expands the myHand pool against the LIVE 2-card hand; both indices resolve
#// to the same card (SOR_063), so the outcome is identical.

## GIVEN
CommonSetup: rrk/brk/{theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 5
WithP2Resources: 8
WithP1Hand: LAW_179
WithP1Hand: SOR_095
WithP1Hand: SOR_063
WithP2Hand: SHD_181
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-1
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# EnemyIsScopedByControlNotOwnership
#// LAW_179 — control axis. "Deal 4 damage to each ENEMY ground unit" names no owner: a unit is enemy
#// or friendly by who CONTROLS it right now, not by who owns the card. The board splits both seats so
#// owner and controller disagree in opposite directions:
#//   · P1's ground arena holds SOR_046 (3/7) OWNED BY P2 but CONTROLLED BY P1 -> friendly to the
#//     event's controller -> must take NO damage.
#//   · P2's ground arena holds SOR_095 (3/3) OWNED BY P1 but CONTROLLED BY P2 -> enemy -> takes 4
#//     and dies.
#// The end state also proves the OWNER still governs where a defeated card goes: the dead SOR_095
#// returns to P1's discard, so P1's discard holds 2 (the spent event + the P1-owned unit) and P2's
#// discard is EMPTY. Resolve "enemy" from ownership instead and every assertion flips — the
#// P1-controlled SOR_046 would take 4 and P2's discard would hold the body.
#//
#// COVERAGE: offer=N/A (no target picker — "each enemy ground unit" is an untargeted sweep; the only
#//           decisions in this file are the opponent's forced-discard picks) · decline=N/A (no "you
#//           may"; the damage is mandatory) · control=this section (enemy scope resolved by
#//           controller; defeated card still routed to its OWNER's discard) · reqboundary=
#//           ForcedHandDiscount_SurvivesTheRequestBoundary · boundary pair=DealsFourToEachEnemyGround
#//           (3/7 survives at 4 / 3/3 dies) + PlayingAnEventDoesNotDiscountThisOne vs
#//           ForcedHandDiscount_CountsOpponentInducedDiscards (played card does NOT discount, forced
#//           discard DOES).

## GIVEN
CommonSetup: rrk/bgw/{myResources:7}
WithP1GroundArenaControlled: SOR_046:2
WithP2GroundArenaControlled: SOR_095:1
WithP1Hand: LAW_179

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:0
