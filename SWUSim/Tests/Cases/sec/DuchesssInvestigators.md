# Decline_NoDiscard
#// SEC_223 Duchess's Investigators — decline the disclose → opponent discards nothing.

## GIVEN
CommonSetup: yyk/grw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SEC_223
WithP1Hand: SEC_220
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2HANDCOUNT:1
P2DISCARDCOUNT:0
P1NODECISION

---

# Disclose_OppRandomDiscard
#// SEC_223 Duchess's Investigators (Ground, 4/4, Cunning) — When Played: you may disclose Cunning →
#//   each opponent discards a random card. P2 has exactly 1 card so the "random" discard is deterministic.

## GIVEN
CommonSetup: yyk/grw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SEC_223
WithP1Hand: SEC_220
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1NODECISION

---

# ThreeSeat_EACHOpponentDiscards
#// "EACH OPPONENT discards a random card from their hand" — every opponent, not one. Both seat 2 and
#// seat 3 hold a single card and both must lose it.
#// The helper discarded from `OtherPlayer($player)` alone, so only seat 2 was ever hit.

## GIVEN
CommonSetup3P: yyk/grw/grw/{myResources:5}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SEC_223
WithP1Hand: SEC_220
WithP2Hand: SOR_095
WithP3Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
SEATCOUNT:3
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P3HANDCOUNT:0
P3DISCARDCOUNT:1

---

# FourSeat_EACHOpponentDiscards_AllThree
#// 4P sibling: three opponents, three empty hands.

## GIVEN
CommonSetup4P: yyk/grw/grw/grw/{myResources:5}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SEC_223
WithP1Hand: SEC_220
WithP2Hand: SOR_095
WithP3Hand: SOR_095
WithP4Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
SEATCOUNT:4
P2HANDCOUNT:0
P3HANDCOUNT:0
P4HANDCOUNT:0
