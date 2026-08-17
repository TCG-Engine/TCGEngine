# OppDiscardUnit_Exhaust
#// JTL_201 Ahsoka Tano — When Played: An opponent discards a card; if it's a unit, you may exhaust a unit.
#// P2's only card (the unit SOR_095) is discarded, so P1 exhausts P2's SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_201
WithP1Resources: 9
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# DiscardNonUnit_NoExhaust
#// JTL_201 Ahsoka Tano — the exhaust follows only if the discarded card is a UNIT. P2's only card is an
#// event (JTL_176 Shoot Down), so it is discarded but no exhaust is offered (no decision), and P2's SOR_046
#// stays ready.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_201
WithP1Resources: 9
WithP2Hand: JTL_176
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:READY

---

# EmptyHand_NoDiscard
#// JTL_201 Ahsoka Tano — with the opponent's hand empty there is nothing to discard, so no exhaust option
#// arises either.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_201
WithP1Resources: 9
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:READY

---

# OppChoosesWhichToDiscard_NonUnit_NoExhaust
#// JTL_201 Ahsoka Tano — ⚠ THE OPPONENT-CHOICE BRANCH (live bug report #965: "Ahsoka is letting exhaust no
#// matter what, not even waiting for discard result").
#// "An opponent discards a card from their hand" — with 2+ cards the OPPONENT chooses which, so
#// SWUDiscardCards queues an MZCHOOSE on THEIR queue. All three sections above give P2 a hand of 0 or 1,
#// which takes SWUDiscardCards' auto-discard branch (inline, no decision) — so none of them ever exercise
#// the branch where the caster's "if it's a unit" continuation has to WAIT for the opponent's pick.
#// Both of P2's cards are EVENTS, so whatever they choose the answer is "not a unit" and no exhaust may be
#// offered. P2's discard pile is deliberately seeded with a UNIT (SOR_095) already on top: a continuation
#// that resolves before the opponent has actually discarded reads that stale card, sees "unit", and offers
#// the exhaust anyway — which is exactly the reported symptom.
#// P1NODECISION and the READY unit are the load-bearing assertions.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_201
WithP1Resources: 9
WithP2Hand: [JTL_176 SOR_043]
WithP2Discard: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0

## EXPECT
P2DISCARDCOUNT:2
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# OppChoosesWhichToDiscard_Unit_Exhaust
#// JTL_201 Ahsoka Tano — the positive half of the opponent-choice branch, and the guard against
#// over-correcting into "never offers". P2 holds a UNIT (SOR_095) and an event and chooses to pitch the
#// unit, so the exhaust must be offered and applied.
#// P2's discard pile is seeded with a NON-unit (JTL_176) on top, the mirror of the section above: a
#// continuation that resolves before the pick reads that stale event, concludes "not a unit", and silently
#// drops the exhaust. So the two sections fail in opposite directions if the ordering is wrong either way.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_201
WithP1Resources: 9
WithP2Hand: [SOR_095 JTL_176]
WithP2Discard: JTL_176
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2DISCARDCOUNT:2
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# EmptyHand_NoDiscard_EvenWithAUnitOnTopOfTheirDiscard
#// JTL_201 Ahsoka Tano — the DISCRIMINATING empty-hand case, and the partner of EmptyHand_NoDiscard above.
#// That section leaves P2's discard pile empty, so it passes even for an implementation with no hand check
#// and no unit gate at all: there is simply no card anywhere for a stale read to find. It cannot fail.
#// Here P2's hand is empty but a UNIT (SOR_095) already sits on top of their discard pile. "An opponent
#// discards a card from their hand" cannot happen with an empty hand, so the "if it's a unit" clause never
#// gets a card and no exhaust may be offered — but an implementation that skips the empty-hand guard, or
#// that answers the gate by peeking at the discard pile instead of at what was actually discarded, sees a
#// unit sitting there and offers the exhaust.
#// P1NODECISION plus the READY unit are the assertions; P2DISCARDCOUNT stays at the seeded 1, proving
#// nothing was discarded.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_201
WithP1Resources: 9
WithP2Discard: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:READY
