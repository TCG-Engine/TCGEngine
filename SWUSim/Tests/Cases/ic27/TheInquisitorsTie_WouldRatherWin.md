# BothPlayersAtFour_BothDiscard
#// IC27_104 The Inquisitor's TIE (Would Rather Win) — 4 cost, 4/5, Aggression+Villainy, SPACE.
#// Text: "On Attack: Each player with 4 or more cards in their hand discards a card from their hand."
#// SYMMETRIC — "each player" includes the attacker's controller. Both sides at 4 cards, so both
#// choose and discard one; the base still takes the TIE's 4 combat damage.

## GIVEN
CommonSetup: rrk/rrk/{}
WithActivePlayer: 1
WithP1SpaceArena: IC27_104:1:0
WithP1Hand: [SOR_095 SOR_046 SOR_237 SEC_080]
WithP2Hand: [SOR_095 SOR_046 SOR_237 SEC_080]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:3
P2HANDCOUNT:3
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P2BASEDMG:4

---

# OnlyAttackerAtFour_AttackerStillDiscards
#// THE MOST-MISSED CELL. An implementation built on SWUDiscardCards (which targets the OPPONENT)
#// would leave the attacker's own hand untouched and still pass every opponent-side section.
#// P1 at 4, P2 at 3 — only P1 discards.

## GIVEN
CommonSetup: rrk/rrk/{}
WithActivePlayer: 1
WithP1SpaceArena: IC27_104:1:0
WithP1Hand: [SOR_095 SOR_046 SOR_237 SEC_080]
WithP2Hand: [SOR_095 SOR_046 SOR_237]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:3
P1DISCARDCOUNT:1
P2HANDCOUNT:3
P2DISCARDCOUNT:0

---

# OnlyOpponentAtFour_OpponentOnlyDiscards
#// The mirror: P1 at 3, P2 at 4 — the per-player threshold is evaluated per hand, not once.

## GIVEN
CommonSetup: rrk/rrk/{}
WithActivePlayer: 1
WithP1SpaceArena: IC27_104:1:0
WithP1Hand: [SOR_095 SOR_046 SOR_237]
WithP2Hand: [SOR_095 SOR_046 SOR_237 SEC_080]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:3
P1DISCARDCOUNT:0
P2HANDCOUNT:3
P2DISCARDCOUNT:1

---

# NeitherAtFour_NoDiscardAndNoPrompt
#// BOUNDARY + load-bearing negative: at exactly 3 cards each, nobody discards AND nobody is asked.
#// A prompt with no legal outcome is itself a bug (the SEC_186 "empty hand still prompted" family).

## GIVEN
CommonSetup: rrk/rrk/{}
WithActivePlayer: 1
WithP1SpaceArena: IC27_104:1:0
WithP1Hand: [SOR_095 SOR_046 SOR_237]
WithP2Hand: [SOR_095 SOR_046 SOR_237]

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HANDCOUNT:3
P2HANDCOUNT:3
P1DISCARDCOUNT:0
P2DISCARDCOUNT:0
P1NODECISION
P2BASEDMG:4

---

# FiveCardsStillDiscardsExactlyOne
#// QUANTITY discrimination: the threshold is a gate, not a per-card multiplier — a 5-card hand
#// loses one card, not two (and not "down to 3").

## GIVEN
CommonSetup: rrk/rrk/{}
WithActivePlayer: 1
WithP1SpaceArena: IC27_104:1:0
WithP1Hand: [SOR_095 SOR_046 SOR_237 SEC_080 SOR_225]
WithP2Hand: [SOR_095 SOR_046 SOR_237]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:4
P1DISCARDCOUNT:1
P2HANDCOUNT:3

---

# AttackingAUnitAlsoTriggers
#// DISPATCH: On Attack fires on any attack, not just a base attack. The TIE (4 power) kills a
#// 2/1 TIE Fighter and takes 2 back.

## GIVEN
CommonSetup: rrk/rrk/{}
WithActivePlayer: 1
WithP1SpaceArena: IC27_104:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: [SOR_095 SOR_046 SOR_237 SEC_080]
WithP2Hand: [SOR_095 SOR_046 SOR_237 SEC_080]

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:3
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:IC27_104
P1SPACEARENAUNIT:0:DAMAGE:2
