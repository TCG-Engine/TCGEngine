# DeclineDisclose_NoDiscard
#// SEC_153 Luthen's Haulcraft — decline the When Defeated disclose → opponent discards nothing.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: SEC_153:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SEC_148
WithP1Hand: SEC_133
WithP2Hand: SOR_095
WithP2Hand: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:0
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P1NODECISION

---

# WhenDefeated_Disclose_OppDiscards2
#// SEC_153 Luthen's Haulcraft (Space, 5/3, Aggression/Heroism) — When Defeated: you may choose an
#//   opponent and disclose AggressionAggressionHeroism → that opponent discards 2 cards.
#// SEC_153 (5/3) attacks JTL_069 (4/7): takes 4, dies (JTL_069 survives). When Defeated: disclose
#// SEC_148 (Agg,Heroism) + SEC_133 (Agg,Villainy) → covers AggAggHeroism → P2 discards its 2 cards.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: SEC_153:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SEC_148
WithP1Hand: SEC_133
WithP2Hand: SOR_095
WithP2Hand: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P2HANDCOUNT:0
P2DISCARDCOUNT:2
P1NODECISION

---

# WhenDefeated_Disclose_OppChoosesWhich2OfThree
#// SEC_153 Luthen's Haulcraft — the disclosed discard is the OPPONENT's choice. Here P2 holds 3 cards,
#//   so after the AggAggHeroism disclose P2 picks which 2 of the 3 to discard; one card stays in hand.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: SEC_153:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SEC_148
WithP1Hand: SEC_133
WithP2Hand: SOR_128
WithP2Hand: SOR_095
WithP2Hand: JTL_069

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myHand-0&myHand-1
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-1

## EXPECT
P1SPACEARENACOUNT:0
P2HANDCOUNT:1
P2DISCARDCOUNT:2
P1NODECISION

---

# WhenDefeated_Disclose_OppHasOnlyOne_DiscardsOne
#// SEC_153 Luthen's Haulcraft — "discards 2" is capped by hand size. P2 holds a single card, so after
#//   the disclose the opponent discards only that 1 card (no way to discard a second).

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: SEC_153:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SEC_148
WithP1Hand: SEC_133
WithP2Hand: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1SPACEARENACOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1NODECISION
