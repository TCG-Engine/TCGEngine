# WhenPlayed_Reveal3Vigilance_Gives3Experience
#// SOR_035 Lieutenant Childsen (Ground, 2/2, Vigilance/Villainy, cost 4) — When Played: reveal up to 4
#//   [Vigilance] cards from hand; give an Experience token to this unit per card revealed. Reveal 3 of 3
#//   → 3 Experience (2/2 + 3 = 5/5); revealed cards STAY in hand (reveal, not discard).

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_035
WithP1Hand: SOR_063
WithP1Hand: SOR_063
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_035
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1HANDCOUNT:3

---

# WhenPlayed_RevealFewer_GivesFewerExperience
#// Reveal only 2 of 4 available Vigilance cards → exactly 2 Experience (count logic distinguishes
#//   "per card revealed" from "one per Vigilance card in hand").

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_035
WithP1Hand: SOR_063
WithP1Hand: SOR_063
WithP1Hand: SOR_063
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1HANDCOUNT:4

---

# WhenPlayed_DeclineReveal_NoExperience
#// "up to 4" → the player may reveal NONE (decline). 0 Experience; unit stays base 2/2.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_035
WithP1Hand: SOR_063
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:2

---

# WhenPlayed_NoVigilanceInHand_NoOp
#// Hand has only non-Vigilance cards (SOR_095 Command/Heroism, SEC_080 Command/Villainy) → no cards to
#//   reveal → no decision, 0 Experience (clean fizzle).

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_035
WithP1Hand: SOR_095
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_035
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# WhenPlayed_CapsAtFour
#// "up to 4" is a hard cap. Five Vigilance cards in hand, answer all five → only 4 Experience (the
#//   resolver validates the count itself; the harness does not enforce the MZMULTICHOOSE max).

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_035
WithP1Hand: SOR_063
WithP1Hand: SOR_063
WithP1Hand: SOR_063
WithP1Hand: SOR_063
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2&myHand-3&myHand-4

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:4
P1HANDCOUNT:5
