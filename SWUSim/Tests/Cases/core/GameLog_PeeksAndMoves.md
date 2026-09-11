# Scry_PublicCount_PrivateCards_ThenThePlacement
#// Game-log sweep, phase 3c — peeks, searches, moves, returns (2026-09-11). A scry logs a PUBLIC "looked at
#// the top N" and a PRIVATE "You saw …", then the placement as a public COUNT (the cards stay face down).
#// SOR_031 Inferno Four (Vigilance/Villainy, 2): "When Played: Look at the top 2 cards of your deck. Put any
#// number of them on the bottom and the rest on top in any order."

## GIVEN
CommonSetup: bbk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_031
WithP1Deck: [SOR_095 SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128
LOGCONTAINS:P1 looked at the top 2 cards of their deck ([[SOR_031|Inferno Four]])
P1LOGSEES:You saw [[SOR_095|Battlefield Marine]], [[SOR_128|Death Star Stormtrooper]]
P2LOGNOTSEES:You saw
LOGCONTAINS:P1 put 1 card on the bottom and kept 1 on top of their deck

---

# Search_Recruit_RevealedAndDrawn
#// SOR_123 Recruit (Command, 1): "Search the top 5 cards of your deck for a unit, reveal it, and draw it." The
#// search is a peek (public count, private cards); the pick is REVEALED, so its line is public; the rest go
#// to the bottom face down (a count). Only one of the top 5 is a unit.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_123
WithP1Deck: [SOR_251 SOR_095 SOR_172 SOR_073 SOR_124 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1 searched the top 5 cards of their deck ([[SOR_123|Recruit]])
LOGCONTAINS:P1 revealed and drew [[SOR_095|Battlefield Marine]] ([[SOR_123|Recruit]])
LOGCONTAINS:P1 put 4 cards on the bottom of their deck
P2LOGNOTSEES:You saw
P2LOGSEES:revealed and drew [[SOR_095

---

# ArenaMove_LowAltitudeCombat
#// HMW_050 Low Altitude Combat (Command/Cunning, 2): "Move a space unit to the ground arena. If you do, you
#// may attack with a ground unit." The optional attack is declined.

## GIVEN
CommonSetup: gyw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_050
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
LOGCONTAINS:P1's [[HMW_050|Low Altitude Combat]] moved P1's [[SOR_237|Alliance X-Wing]] to the ground arena

---

# BottomOfDeck_IHadNoChoice
#// SOR_187 I Had No Choice (Cunning/Villainy, 7): "Choose up to 2 non-leader units. An opponent chooses 1 of
#// those units. Return that unit to its owner's hand and put the other on the bottom of its owner's deck."
#// P2 makes the choice on P2's queue — the effects are still P1's card's. (Fixture from sor/IHadNoChoice.md.)

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_187
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
LOGCONTAINS:P1's [[SOR_187|I Had No Choice]] returned P2's [[SEC_080|Imperial Dark Trooper]] to its owner's hand
LOGCONTAINS:P1's [[SOR_187|I Had No Choice]] put P2's [[SOR_128|Death Star Stormtrooper]] on the bottom of its owner's deck

---

# ResourcesReturned_NeverNamed
#// IC27_167 Lando Calrissian (Cunning/Cunning, 3): "When Played: Return 3 friendly resources to their owner's
#// hands. Then, you may resource up to 3 cards from your hand." Resources are FACE DOWN — each return is
#// logged as "a resource", never by name, so the opponent learns nothing it could not see.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1Hand: IC27_167
WithP1Resources: 3:SEC_080:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1&myResources-2
- P1>AnswerDecision:-

## EXPECT
P1RESCOUNT:0
LOGCOUNT:3:returned a resource to their hand ([[IC27_167|Lando Calrissian]])
P2LOGNOTSEES:[[SEC_080

---

# ReturnFromDiscard_BountyHunterCrew
#// SOR_183 Bounty Hunter Crew (Cunning/Villainy, 6): "When Played: You may return an event from a discard pile
#// to its owner's hand." A card coming back from a discard pile was face up — the line names it.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;discardCardIds:SOR_251}
P1OnlyActions: true
WithP1Hand: SOR_183

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1's [[SOR_183|Bounty Hunter Crew]] returned [[SOR_251|Confiscate]] from P1's discard pile to their hand

---

# HiddenSearch_SearchYourFeelings_NeverNamesTheCard
#// Found by the existing sor/SearchYourFeelings.md guard during this sweep: the first draft of the search
#// finalize logged "revealed and drew X" for EVERY search. SOR_042 Search Your Feelings ("Search your deck for
#// a card and draw it") does not reveal — its pick is hidden. Only a searching card whose text says "reveal"
#// names the card publicly; any other search logs a public "drew a card" plus a line only the drawer sees.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_042
WithP1Resources: 4
WithP1Deck: SOR_063
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_063

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1 drew a card ([[SOR_042|Search Your Feelings]])
P1LOGSEES:You drew [[SOR_063
P2LOGNOTSEES:[[SOR_063
LOGCOUNT:0:revealed and drew
