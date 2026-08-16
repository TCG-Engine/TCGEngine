# FrontSearchAfterRebelDefeat
#// LAW_005 Jyn Erso (leader front) — "Action [1 resource, Exhaust]: If a friendly Rebel unit was defeated
#// this phase, search the top 3 of your deck for a card and draw it." P1's Rebel SOR_095 attacks the 8/8
#// SOR_039 and dies (Rebel defeated this phase); then Jyn's action searches and draws SOR_046.
#// COVERAGE: offer=DeployedOnAttack_OfferIsTop3 (SEARCHPLAYABLE has exactly the top 3, NOT the 4th card)
#//           · decline=FrontSearch_TakeNothing · reqboundary=FrontSearchAfterRebelDefeat +
#//           DeployedOnAttack_RebelDefeated_Searches (the Rebel-defeated-this-phase flag must survive the
#//           attack→search-answer boundary) · boundary=FrontFewerThan3Cards_Works +
#//           FrontEmptyDeck_UsableButNoDraw (and the deployed pair) — deck at 2 / at 0 · control=N/A (the
#//           flag is stamped per defeated friendly unit at defeat time; no scenario moves units across
#//           seats, and the ability itself never targets an enemy object)

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:1

---

# FrontFewerThan3Cards_Works
#// LAW_005 Jyn Erso (front) — the search works with fewer than 3 cards in the deck. A friendly Rebel
#// (SOR_095) dies attacking the 8/8 SOR_039; Jyn's action then searches a 2-card deck and draws SOR_046.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:1

---

# FrontEmptyDeck_UsableButNoDraw
#// LAW_005 Jyn Erso (front) — with a friendly Rebel defeated this phase the action is still usable even if
#// the deck is empty: the [1 resource, Exhaust] cost is paid, but the search finds nothing so no card is
#// drawn (and no draw-from-empty base damage). SOR_095 dies attacking the 8/8 SOR_039, then Jyn's action
#// exhausts and draws nothing.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1BASEDMG:0
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# DeployedOnAttack_RebelDefeated_Searches
#// LAW_005 Jyn Erso (deployed) — same effect as the front, but it triggers On Attack instead of as an
#// action. A friendly Rebel (SOR_095) dies attacking the 8/8 SOR_039; then Jyn (deployed) attacks P2's base,
#// her On Attack fires automatically, and P1 draws SOR_046 from the top 3.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1LEADER:DEPLOYED

---

# DeployedOnAttack_NoRebelDefeated_NoEffect
#// LAW_005 Jyn Erso (deployed) — no friendly Rebel was defeated this phase, so Jyn's On Attack has no
#// effect. Jyn simply attacks P2's base; no card is drawn and the deck is untouched.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
P1NODECISION

---

# DeployedOnAttack_EnemyRebelDefeated_NoEffect
#// LAW_005 Jyn Erso (deployed) — only a FRIENDLY Rebel defeat counts. P1's SOR_046 defeats the enemy Rebel
#// SOR_095 (and survives), but that is an enemy Rebel, so Jyn's On Attack has no effect: no card is drawn.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:1:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
P1NODECISION

---

# DeployedOnAttack_FriendlyNonRebelDefeated_NoEffect
#// LAW_005 Jyn Erso (deployed) — a friendly NON-Rebel unit (SEC_080) dying doesn't count. It dies attacking
#// the 8/8 SOR_039, then Jyn attacks P2's base and her On Attack has no effect: no card is drawn.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
P1NODECISION

---

# DeployedOnAttack_FewerThan3Cards_Works
#// LAW_005 Jyn Erso (deployed) — the On Attack search works with fewer than 3 cards in the deck. SOR_095
#// dies attacking the 8/8 SOR_039, then Jyn attacks P2's base and draws SOR_046 from a 2-card deck.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1LEADER:DEPLOYED

---

# DeployedOnAttack_EmptyDeck_NoEffect
#// LAW_005 Jyn Erso (deployed) — with an empty deck the On Attack search finds nothing, so no card is drawn
#// and there is no draw-from-empty base damage. SOR_095 dies attacking the 8/8 SOR_039, then Jyn attacks
#// P2's base with no effect.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1BASEDMG:0
P1NODECISION

---

# FrontNoRebelDefeated_UsableAnyway
#// LAW_005 Jyn Erso (front) — "If a friendly Rebel was defeated this phase, search…" is a conditional
#// EFFECT, not an activation gate: the [1 resource, Exhaust] cost is a game-state change, so the Action is
#// usable even with NO Rebel defeated (CR 6.4.587.c — "Use it anyway"). It pays 1 + exhausts Jyn, draws
#// nothing (deck untouched, hand stays empty).

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:1
P1DECKCOUNT:3
P1LEADER:EXHAUSTED

---

# FrontEnemyRebelDefeated_NoEffect
#// LAW_005 Jyn Erso (front) — only a FRIENDLY Rebel defeat counts for the action too. P1's SOR_046
#// defeats the enemy Rebel SOR_095 (and survives), but that is an enemy Rebel, so the action's search
#// clause has no effect: the [1 resource, Exhaust] cost is still paid (CR 6.4.587.c "Use it anyway"),
#// no card is drawn and the deck is untouched.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# FrontFriendlyNonRebelDefeated_NoEffect
#// LAW_005 Jyn Erso (front) — a friendly NON-Rebel unit (SEC_080) dying doesn't count for the action
#// either. It dies attacking the 8/8 SOR_039, then Jyn's action is used: the [1 resource, Exhaust] cost
#// is paid but the search clause has no effect — no card drawn, deck untouched.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# FrontGainedRebelTrait_Triggers
#// LAW_005 Jyn Erso (front) — the "friendly Rebel defeated this phase" condition reads GRANTED traits, not
#// just printed ones. P1's Republic unit (SEC_167, NOT printed Rebel) wears Nemik's Manifesto (SEC_156,
#// grants the Rebel trait); it attacks the 8/8 SOR_039 and dies (a gained-Rebel unit defeated), so Jyn's
#// action then searches and draws SOR_046.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SEC_167:1:0
WithP1GroundArenaUpgrade: 0:SEC_156
WithP2GroundArena: SOR_039:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:1

---

# DeployedOnAttack_GainedRebelTrait_Triggers
#// LAW_005 Jyn Erso (deployed) — the On Attack side also reads GRANTED traits. P1's Republic unit
#// (SEC_167, NOT printed Rebel) wears Nemik's Manifesto (SEC_156, grants the Rebel trait); it dies
#// attacking the 8/8 SOR_039 (a gained-Rebel friendly unit defeated this phase). Jyn then attacks
#// P2's base, her On Attack fires, and P1 draws SOR_046 from the top 3.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_167:1:0
WithP1GroundArenaUpgrade: 0:SEC_156
WithP2GroundArena: SOR_039:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1LEADER:DEPLOYED

---

# DeployedOnAttack_OfferIsTop3
#// LAW_005 Jyn Erso (deployed) — the search OFFER is exactly the top 3 cards of the deck (the 4th card,
#// SOR_128, must NOT be offered). SOR_095 dies attacking the 8/8 SOR_039, Jyn attacks P2's base, and the
#// section ends on the pending search picker to assert its exact contents.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_231
WithP1Deck: SOR_139
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_046
P1SEARCHPLAYABLEHAS:SOR_231
P1SEARCHPLAYABLEHAS:SOR_139
P1SEARCHPLAYABLENOT:SOR_128

---

# FrontSearch_TakeNothing
#// LAW_005 Jyn Erso (front) — "search ... for a card and draw it" still allows taking nothing. A friendly
#// Rebel (SOR_095) dies attacking the 8/8 SOR_039; Jyn's action searches, and P1 DECLINES the pick: no
#// card is drawn, the deck keeps its 3 cards, and the [1 resource, Exhaust] cost stays paid.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# FrontSearchAfterRebelDefeat_SurvivesTheRequestBoundary
#// LAW_005 — request-boundary guard for FrontSearchAfterRebelDefeat: same fixture, same flow, one extra
#// SimulateRequestBoundary inserted before the search answer. Production starts a FRESH process on every
#// answered decision, so the top-3 snapshot the action peeled off the deck (cards spliced OUT of the deck
#// and parked while the picker is open) plus the "a friendly Rebel was defeated this phase" flag have to
#// come back out of the serialized gamestate rather than an in-memory continuation global. SOR_046 must
#// still be drawable after the boundary, and the deck must not have leaked its peeked cards.
#// The insertion point is a genuine 3-option search picker (SOR_046 / SOR_095 / SOR_128), so the boundary
#// is not vacuous.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_005;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:1
