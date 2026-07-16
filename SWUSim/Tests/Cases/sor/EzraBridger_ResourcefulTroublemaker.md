# OnAttackEnd_Discard
#// SOR_192 Ezra Bridger — On Attack End: choosing "Discard" puts the top card into the discard pile
#// (From DECK). Ezra attacks P2's base for 3; the top card SOR_095 is milled (deck 3 → 2, discard
#// 0 → 1).

## GIVEN
CommonSetup: rrw/rrw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Discard

## EXPECT
P2BASEDMG:3
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1DISCARDUNIT:0:FROM:DECK
P1GROUNDARENACOUNT:1

---

# OnAttackEnd_EmptyDeck_NoOp
#// SOR_192 Ezra Bridger — On Attack End with an empty deck: there is no top card to look at, so the
#// ability fizzles with no decision (no option popup). Ezra still attacks P2's base for 3, and the
#// turn proceeds with no pending decision.

## GIVEN
CommonSetup: rrw/rrw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1NODECISION
P1DECKCOUNT:0
P1DISCARDCOUNT:0
P1GROUNDARENACOUNT:1

---

# OnAttackEnd_Leave
#// SOR_192 Ezra Bridger (Unit 3/4, cost 3, Cunning/Heroism) — When this unit completes an attack:
#// look at the top card; you may play it, discard it, or leave it on top. Ezra (in play, ready)
#// attacks P2's base for 3, then the On Attack End trigger fires; the player chooses "Leave". The
#// deck is untouched at the On Attack End step (top stays SOR_095). P1 then passes, the round rolls
#// to Regroup, and the Draw step draws the top card — confirming the card LEFT ON TOP (SOR_095) is
#// the one actually drawn (hand index 0), not a card from the other end of the deck.

## GIVEN
CommonSetup: rrw/rrw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Leave
- P1>Pass

## EXPECT
P2BASEDMG:3
P1DECKCOUNT:1
P1DISCARDCOUNT:0
P1HANDCOUNT:2
P1GROUNDARENACOUNT:1
#// The card left on top (SOR_095) is the next one drawn in the regroup Draw step → hand index 0.
P1HANDCARD:0:SOR_095

---

# OnAttackEnd_Play
#// SOR_192 Ezra Bridger — On Attack End: choosing "Play" plays the top card from the deck, paying
#// its normal cost. Ezra attacks P2's base for 3; the top card is SOR_157 (cost 1, Aggression, no
#// entry trigger). With matched Aggression aspects and 1 ready resource, it is played to the ground
#// arena (arena 1 → 2, deck 3 → 2, resources 1 → 0).

## GIVEN
CommonSetup: rrw/rrw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1Deck: SOR_157
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Play

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_157
P1DECKCOUNT:2
P1RESAVAILABLE:0
P1DISCARDCOUNT:0
