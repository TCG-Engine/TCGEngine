# ActionPlayTopDeck
#// LAW_094 Hondo Ohnaka (3/7) — Action: play the top card of your deck (paying its cost). Once each
#// round. Top is SOR_063 (Vigilance, cost 2); pay 2 -> it enters play.

## GIVEN
CommonSetup: byk/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: LAW_094:1:0
WithP1Deck: SOR_063

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1DECKCOUNT:0

---

# ActionOnceEachRound
#// LAW_094 Hondo Ohnaka — "Action: Play the top card of your deck. Once each round." Using the action a
#// SECOND time in the same round does nothing: the first use plays the top card (SOR_063) into play, the
#// second use is a no-op so only ONE card leaves the deck.

## GIVEN
CommonSetup: byk/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: LAW_094:1:0
WithP1Deck: [SOR_063 SOR_063]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1DECKCOUNT:1

---

# ActionCannotUseWithEmptyDeck
#// LAW_094 Hondo Ohnaka — the action requires a top card. With an empty deck the ability is unavailable:
#// using it is a no-op (nothing is played, the deck stays empty).

## GIVEN
CommonSetup: byk/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: LAW_094:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1DECKCOUNT:0

---

# ActionCannotAffordTopCard
#// LAW_094 Hondo Ohnaka — you must still pay the top card's cost. With only 5 resources and AT-ST
#// (SOR_232, cost 6) on top, the top card cannot be paid for, so the ability does nothing and AT-ST
#// stays on the deck.

## GIVEN
CommonSetup: byk/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_094:1:0
WithP1Deck: [SOR_232 SOR_063]

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_232

---

# ActionCannotPlayRestrictedTopCard
#// LAW_094 Hondo Ohnaka — "play the top card" respects a play-restriction. P2's Regional Governor
#// (SOR_062) names "Battlefield Marine", which is the top of P1's deck. Hondo's action can't play the
#// blocked card: the top card stays in the deck and the once-per-round use is not consumed.

## GIVEN
CommonSetup: byk/bbw/{myResources:4;theirResources:2}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LAW_094:1:0
WithP1Deck: [SOR_095 SOR_063]
WithP2Hand: SOR_062

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Battlefield Marine
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1DECKCOUNT:2
