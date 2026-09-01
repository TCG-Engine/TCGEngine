# Choose1of1
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose 1 of 1 matching Imperial card.
#// COVERAGE: offer=SearchOffer_ImperialsInTheTopFiveOnly (decision left pending and the pool read
#//           directly: a non-Imperial inside the window and an Imperial one card PAST the window are
#//           both excluded, so the filter is proven to be trait AND depth) · control=
#//           OpponentOwnedTarkin_PlayedByYou_SearchesYourDeck ("your deck" follows the player who
#//           PLAYED him, not his owner — an opponent-owned Tarkin played via a Bounty searches the
#//           new controller's deck, with the opponent's Imperial-stocked deck untouched) ·
#//           decline=ChooseNoneof1 + ChooseNoneof2 ("up to 2" includes zero: nothing is drawn and the
#//           whole window goes back to the deck) · boundary pair=the quantity ladder Choose1of1 /
#//           Choose1of2 / Choose1of3 / Choose2of2 / Choose2of3 pins the cap at 2 (never 3 from 3),
#//           and SearchOffer_ImperialsInTheTopFiveOnly pins the depth at 5 (card 6 is out of reach) ·
#//           reqboundary=every resolving section (the play and the search answer are separate
#//           serialized requests) and OpponentOwnedTarkin_PlayedByYou_SearchesYourDeck in particular,
#//           where attach, defeat, Bounty-collect and search each land in their own request.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:7

---

# Choose1of2
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose 1 of 2 matching Imperial cards.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_128
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:7

---

# Choose1of3
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose 1 of 3 matching Imperial cards.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_128
WithP1Deck: SOR_086
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:7

---

# Choose2of2
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose 2 of 2 matching Imperial cards.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_128
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085,SOR_128

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:6

---

# Choose2of3
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose 2 of 3 matching Imperial cards.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_128
WithP1Deck: SOR_086
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085,SOR_128

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:6

---

# ChooseNoneof1
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose none of 1 matching Imperial card.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:8

---

# ChooseNoneof2
#// SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose none of 2 matching Imperial cards.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_128
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:8

---

# OpponentOwnedTarkin_PlayedByYou_SearchesYourDeck
#// SOR_084 Grand Moff Tarkin — the CONTROL axis. "Search the top 5 cards of YOUR deck" resolves for
#// the player who PLAYED the card, which is not always the player who OWNS it. P1 attaches
#// SHD_226 Unrefusable Offer to P2's Tarkin (granting it a Bounty: "play this unit for free under
#// your control"), then Takedown defeats it; P1 collects the Bounty and plays the P2-OWNED Tarkin
#// onto P1's board. His When Played must search P1's deck and draw into P1's hand — P1's deck holds
#// the only Rukh, while P2's deck is stocked with three Imperial Stormtroopers that must never be
#// seen: P2's deck stays at 3 and P2's hand stays empty. If "your deck" were read from the card's
#// owner the search would have run on P2's deck instead.

## GIVEN
CommonSetup: gyk/rrk/{
  myResources:8;
  myhandCardIds:SHD_226,SOR_077
}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_084:1:0
WithP1Deck: [SOR_085 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
WithP2Deck: [SOR_128 SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_085

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_084
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_085
P1DECKCOUNT:5
P2DECKCOUNT:3
P2HANDCOUNT:0

---

# SearchOffer_ImperialsInTheTopFiveOnly
#// SOR_084 Grand Moff Tarkin — the OFFER axis, and the depth boundary that no answer-based section can
#// reach. Answering a search proves the branch, never the POOL, so here the search decision is left
#// PENDING and the pool is read directly. The deck is stacked so that both exclusions are live at
#// once: Cloud City Wing Guard (Fringe/Trooper, sitting INSIDE the top 5) fails the Imperial filter,
#// and a Death Star Stormtrooper — Imperial, and so filtered IN on trait — sits at depth 6, one card
#// past the window, and must not be offered either. Only the two Imperial cards inside the top 5 are
#// selectable. Nothing has been drawn yet, so the hand is still empty.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: [SOR_085 SOR_086 SOR_063 SOR_063 SOR_063 SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_085
P1SEARCHPLAYABLEHAS:SOR_086
P1SEARCHPLAYABLENOT:SOR_063
P1SEARCHPLAYABLENOT:SOR_128
P1HANDCOUNT:0
