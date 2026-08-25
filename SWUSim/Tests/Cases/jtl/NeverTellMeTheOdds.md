# MillSix_DamageByOddCost
#// JTL_208 — Discard 3 from an opponent's deck and 3 from your deck; deal damage to a unit equal to the
#// number of odd-cost cards discarded. Self: SOR_128(1,odd)/SOR_095(2)/SOR_237(2). Opp: SOR_225(1,odd)/
#// SOR_237(2)/SOR_044(2). Two odd-cost → deal 2 to the only unit (SOR_046, 7 HP).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_208
WithP1Resources: 7
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP2Deck: SOR_225
WithP2Deck: SOR_237
WithP2Deck: SOR_044

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1DECKCOUNT:0
P2DECKCOUNT:0

---

# FewerThanThreeCards_MillWhatsLeft
#// JTL_208 Never Tell Me the Odds — discards up to 3 from each deck, or as many as remain. P1's deck has 2
#// cards (SOR_128 cost 1 = odd, SOR_095 cost 2), P2's deck has 1 (SOR_144 Red Three cost 3 = odd). Both
#// decks empty out; two odd-cost cards were discarded → deal 2 to a unit (the AT-ST survives with 2 damage).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_208
WithP1Resources: 7
WithP2GroundArena: SOR_232:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_095
WithP2Deck: SOR_144

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1DECKCOUNT:0
P2DECKCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# BothDecksEmpty_DoesNothing
#// JTL_208 Never Tell Me the Odds — with both decks empty, nothing is discarded, no odd-cost cards, and no
#// damage is dealt (the event just pays its cost and goes to discard).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_208
WithP1Resources: 7
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION

---

# DamagePrompt_ShowsTheComputedAmount
#// JTL_208 — the damage amount is computed from the mill (odd-cost cards discarded) and is not visible
#// anywhere in the board state until it has already been applied, so the PROMPT has to carry it. Seeded
#// for exactly three odd-cost discards: P1 mills SOR_128(1) + SOR_225(1) + SOR_095(2) = 2 odd, P2 mills
#// SOR_225(1) + SOR_237(2) + SOR_044(2) = 1 odd. Two enemy units keep the target choice pending so the
#// tooltip can be read rather than auto-resolving.
#// (Previously written off as un-portable "prompt-title matcher"; P1DECISIONTOOLTIP covers it exactly.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_208
WithP1Resources: 7
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]
WithP1Deck: [SOR_128 SOR_225 SOR_095]
WithP2Deck: [SOR_225 SOR_237 SOR_044]

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Deal_3_damage_to_a_unit
P1DECKCOUNT:0
P2DECKCOUNT:0

---

# TwinSuns_MillsTheCHOSENSeat_AndTheSelfMillSurvives
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. "Discard 3 from AN OPPONENT's deck and 3 from your deck."
#// ⚠⚠ THE FIX ITSELF WAS THE HAZARD HERE. SWUQueueChooseOpponent queues NOTHING at zero eligible, and the
#// SELF-mill plus the damage live AFTER the pick — so filtering on "opponents with cards in deck" would
#// silently lose your own mill and your own damage in a 2-PLAYER game against an empty deck. That is a
#// Premier regression introduced by the FIX, not the bug, and no pre-existing section covered it. So
#// $eligible is deliberately null and an empty-deck opponent stays a legal, meaningful pick.
#// P1 picks SEAT 3: seat 3's deck loses 3, P1's loses 3, seats 2 and 4 are untouched.
#// ⚠ 'side' => 'any' on the damage is CORRECT and must NOT be narrowed — "a unit" is unqualified and spans
#//   every board including your own. Contrast JTL_125, scoped to "that opponent".
#// ⚠ FIXTURE: keep the existing section's bbk/bbk aspects and 7 resources — JTL_208 is Cunning, and an
#//   off-aspect deck adds a penalty that pushes the cost past a small pool, so the event is never played
#//   and every assertion fails for an unrelated reason.
#// Mutation check: revert to OtherPlayer() and this reds.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001;myBase:JTL_019}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Resources: 7
WithP1Hand: JTL_208
WithP1Deck: [SOR_095 SOR_046 SEC_080 SOR_141]
WithP2Deck: [SOR_095 SOR_046 SEC_080 SOR_141]
WithP3Deck: [SOR_095 SOR_046 SEC_080 SOR_141]
WithP4Deck: [SOR_095 SOR_046 SEC_080 SOR_141]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3DECKCOUNT:1
P1DECKCOUNT:1
P2DECKCOUNT:4
P4DECKCOUNT:4
