# MigsMayfeld_HandDiscard_Deal2
#// SHD_163 Migs Mayfeld — "When a player discards a card from their hand: You may deal 2 damage to a unit
#// or base. Once each round." P1 plays SHD_244 (No Bargain), forcing P2 to discard its only card; Migs
#// then deals 2 to the enemy SOR_046.
#// COVERAGE: offer=Offer_SpansEveryUnitAndBothBases (pending pool: every unit both sides both arenas
#//           plus both bases) · decline=Decline_NothingIsDamaged ("you may", answered with the
#//           choose-nothing token) · control=EnemyOwnedMigs_TheReactionBelongsToTheController (a Migs
#//           P1 controls but P2 owns — the reaction goes to the CONTROLLER) · reqboundary=
#//           SimulateRequestBoundary_OfferSurvivesTheBoundary · boundary pair=
#//           OncePerRound_SecondDiscardRaisesNoOffer (second discard in the SAME round is silent) vs
#//           RearmsInTheNextRound (the same second discard one round later fires again) — the two sides
#//           of the round boundary, plus the dispatch-path set OwnHandDiscard_TriggersToo /
#//           DiscardByALookAtSystem_StillTriggers / DiscardEventThatDealsDamage_MigsResolvesAfterIt
#//           against the negative PlayingAnEventDoesNotTriggerHim.
#// Intended but NOT YET encoded: a hand discard performed inside a LEADER ability (either as the
#// ability's cost or as its effect) does not currently arm Migs at all, so those two variants are
#// deliberately absent rather than asserted; every discard source used below is a card play.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_163:1:0
WithP1Hand: SHD_244
WithP1Deck: SOR_095
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2HANDCOUNT:0

---

# PlayingAnEventDoesNotTriggerHim
#// SHD_163 — "When a player DISCARDS a card from their hand". Playing an event sends it to the discard
#// pile but is not a discard, so Migs must not offer his 2 damage. P1 plays Urgent Mission (which draws
#// and deals 2 to P1's own base) with Migs on board: no decision is raised and the enemy unit is untouched.
#// Third card sharing one root cause with LAW_076 and LAW_179 — the discard funnel's from='HAND' block
#// fired on an event's own play. Migs is the reactive member of that trio, so his guard is the one that
#// proves no stray PROMPT appears rather than just a wrong number.

## GIVEN
CommonSetup: rrk/yyw/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_64
WithP1GroundArena: SHD_163:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:2

---

# Offer_SpansEveryUnitAndBothBases
#// SHD_163 — "deal 2 damage to A UNIT OR BASE" is completely unfiltered: every unit on the board in
#// either arena on either side, plus both bases. Five bodies and two bases are seated and the offer is
#// left PENDING so the pool itself is the assertion.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_163:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1Hand: SHD_244
WithP1Deck: [SOR_095 SOR_095]
WithP2Hand: SOR_095
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0&myBase-0&theirBase-0

---

# DamageToTheOpponentBase
#// SHD_163 — the "or base" half of the target clause. Same forced discard as
#// MigsMayfeld_HandDiscard_Deal2, but the 2 damage is aimed at the enemy base instead of a unit; the
#// enemy unit is left untouched, which proves the base branch is a real alternative and not a mis-routed
#// unit hit.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_163:1:0
WithP1Hand: SHD_244
WithP1Deck: SOR_095
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Decline_NothingIsDamaged
#// SHD_163 — "YOU MAY deal 2 damage". The discard still happens, the offer is still raised, but P1
#// declines it and nothing anywhere takes damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_163:1:0
WithP1Hand: SHD_244
WithP1Deck: SOR_095
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1NODECISION
P2HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
P2BASEDMG:0

---

# DiscardByALookAtSystem_StillTriggers
#// SHD_163 — the third dispatch path: a look-at-their-hand effect (Spark of Rebellion, "Look at an
#// opponent's hand and discard a card from it") picks the card out of the opponent's hand for them. That
#// is still "a player discards a card from their hand", so Migs reacts. Two cards are seated in P2's hand
#// so the pick is a real choice rather than an auto-resolve.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_200
WithP1GroundArena: SHD_163:1:0
WithP2Hand: [SOR_095 SOR_046]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# DiscardEventThatDealsDamage_MigsResolvesAfterIt
#// SHD_163 — when the discard comes from an event that ITSELF deals damage, Migs' 2 damage lands after
#// that event has finished resolving, not interleaved with it. Force Throw makes P2 discard its only card
#// (SHD_178, cost 1) and then, because P1 controls a Force unit, deals damage equal to that cost — 1 — to
#// the enemy SOR_046. Migs then adds his 2 on top for 3 total, which is only reachable if both hits land
#// in that order on the same body.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_167
WithP1GroundArena: SHD_163:1:0
WithP1GroundArena: SOR_131:1:0
WithP2Hand: SHD_178
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2HANDCOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# OwnHandDiscard_TriggersToo
#// SHD_163 — "When A PLAYER discards a card from their hand" is symmetric: it fires on MIGS' OWN
#// CONTROLLER discarding just as it does on the opponent. P1 plays an event that draws 2 and then makes
#// P1 discard a card from their own hand, and Migs offers his 2 damage off that self-discard. The played
#// event excludes itself from the discard pool, so the two freshly drawn cards are the candidates.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_074
WithP1GroundArena: SHD_163:1:0
WithP1Deck: [SOR_095 SOR_046 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1HANDCOUNT:1
P1DISCARDCOUNT:2

---

# OncePerRound_SecondDiscardRaisesNoOffer
#// SHD_163 — "Use this ability only once each round", and the limit is on MIGS, not on a particular
#// player's discards. Two hand-discards happen in the same round from opposite sides of the table: No
#// Bargain empties P2's hand and Migs takes his 2 damage, then P1's own draw-2-and-discard event pushes
#// a card out of P1's OWN hand. That second discard raises nothing — the enemy unit ends on 2 damage,
#// not 4, and no decision is left pending.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SHD_163:1:0
WithP1Hand: [SHD_244 IBH_074]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1NODECISION
P1HANDCOUNT:2
P1DISCARDCOUNT:3
P2HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# RearmsInTheNextRound
#// SHD_163 — the once-each-round limit is cleared at the round boundary. Migs fires on P2's forced
#// discard in round one, the game crosses the regroup phase, and P1's own discard in round two raises the
#// offer again for 4 total damage on the same 7-HP body. Both decks are seeded past the regroup draws so
#// no empty-deck damage pollutes the counts.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_163:1:0
WithP1Hand: [SHD_244 IBH_074]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# EnemyOwnedMigs_TheReactionBelongsToTheController
#// SHD_163 — the reaction follows whoever CONTROLS Migs, not whoever owns him. P1 controls a Migs that
#// P2 still owns (the end state after a take-control effect); a discard then puts the offer to P1, whose
#// frame resolves theirGroundArena-0 to P2's unit. Were the reaction routed by ownership it would have
#// gone to P2 and P1's answer would have had nothing to consume.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArenaControlled: SHD_163:2
WithP1Hand: SHD_244
WithP1Deck: SOR_095
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2HANDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# SimulateRequestBoundary_OfferSurvivesTheBoundary
#// SHD_163 — the discard that arms Migs and the answer that spends him arrive as two separate requests
#// in production, so the pending offer and the once-each-round marker both have to be re-read from the
#// serialized gamestate. Mirrors MigsMayfeld_HandDiscard_Deal2 with the boundary inserted between the
#// play and the answer.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_163:1:0
WithP1Hand: SHD_244
WithP1Deck: SOR_095
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2HANDCOUNT:0

---

# DiscardAsACost_InsideALeaderAbility_StillTriggers
#// SHD_163 — "When A PLAYER discards a card from their hand" does not care WHY the card was discarded,
#// nor which ability did it. Here the discard is paid as a COST inside a LEADER Action (Kylo Ren
#// SHD_011's "Action [Exhaust, discard a card from your hand]"), and it is Migs' OWN controller doing it.
#// This is a dispatch-path variant, not a restatement of MigsMayfeld_HandDiscard_Deal2: a discard made
#// during a card PLAY was flushed by the play ceremony, while one made inside a leader ability finished
#// through the after-action with nothing draining the trigger bag, so Migs silently never fired.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_011:1}
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_046]
WithP1GroundArena: SHD_163:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
