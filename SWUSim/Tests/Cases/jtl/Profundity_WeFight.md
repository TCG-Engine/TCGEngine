# WhenPlayed_ChooseOpponentDiscard
#// JTL_154 Profundity — When Played: Choose a player; they discard a card. P1 chooses the Opponent, whose
#// 1-card hand auto-discards. The conditional second discard does not fire (P2 ends at 0 cards, not more
#// than P1's 0).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_154
WithP1Resources: 13
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P1SPACEARENACOUNT:1
P2HANDCOUNT:0
P2DISCARDCOUNT:1

---

# WhenPlayed_ChooseSelfDiscard
#// JTL_154 Profundity — When Played: Choose a player; they discard a card. P1 chooses itself (You) and
#// discards one card from hand. The follow-up second discard does not fire (P1's hand is not larger than
#// its own). Choosing YOURSELF also means the pick is queued on the CASTER's own queue, so this section
#// says nothing about cross-player ordering — that is
#// OpponentChoosesDiscard_SecondDiscardCountsTheHandAFTERTheFirst below, which covers it and passes.
#// (An older note here called that case "deferred, needs interactive opponent input"; it is not — `P2>` WHEN
#// lines drive the opponent's queue directly.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_154
WithP1Hand: SOR_095
WithP1Hand: SOR_128
WithP1Resources: 13

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# OpponentChoosesDiscard_SecondDiscardCountsTheHandAFTERTheFirst
#// JTL_154 Profundity — the case the section above calls "deferred": the OPPONENT is chosen AND has a real
#// choice (2+ cards), so their pick is queued on THEIR queue. This is the same shape as the JTL_201 Ahsoka
#// bug (report #965) — a continuation on the CASTER's queue resolves before the opponent has discarded.
#// "THEN, if they have more cards in their hand than you" — the word "then" is load-bearing: the comparison
#// reads the hand AFTER the first discard.
#// The numbers are chosen so correct and buggy diverge. P1 keeps 1 card after playing Profundity; P2 starts
#// with 2. Correct: P2 discards 1 and is left with 1, which is NOT more than P1's 1, so there is no second
#// discard. Reading the pre-discard hand instead sees 2 > 1 and takes a second card.
#// P2HANDCOUNT:1 / P2DISCARDCOUNT:1 is the whole assertion.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [JTL_154 SOR_095]
WithP1Resources: 13
WithP2Hand: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:1
P2DISCARDCOUNT:1
