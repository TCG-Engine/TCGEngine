# WhenPlayed_Decline_NoReturn
#// SOR_099 Bright Hope — the return is optional ("You may"). Declining means no unit is
#// returned and NO card is drawn. The friendly ground unit stays, hand holds only what's left
#// after playing Bright Hope (0), and the deck is untouched.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# WhenPlayed_ReturnGroundDraw
#// SOR_099 Bright Hope (2/6, Space, Sentinel) — When Played: You may return a friendly
#// non-leader GROUND unit to its owner's hand. If you do, draw a card. P1 returns its
#// Battlefield Marine and draws. Net hand: played Bright Hope (-1), Marine back (+1), draw (+1)
#// = 2; deck -1; the ground arena is emptied.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1GroundArena: SOR_095:1:0    # friendly non-leader ground unit — returned
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1DECKCOUNT:1
