# ActionPlayTopDeck
#// LAW_094 Hondo Ohnaka (3/7) — Action: play the top card of your deck (paying its cost). Once each
#// round. Top is SOR_063 (Vigilance, cost 2); pay 2 -> it enters play.
#// COVERAGE: offer=N/A (no target picker — the action is availability-gated; the gate is asserted by the
#//           no-op sections ActionCannotUseWithEmptyDeck / ActionCannotAffordTopCard /
#//           ActionCannotPlayRestrictedTopCard) · decline=N/A (no "you may" prompt; using the action is
#//           itself voluntary) · control=StolenHondo_PlaysNewControllersDeck + StolenHondo_OncePerRound
#//           (plays the NEW controller's deck; once-each-round tracked per player) · boundary pair=
#//           ActionCannotAffordTopCard (5 res vs cost 6) vs ActionPlayTopDeck (affordable), and
#//           ActionOnceEachRound (same round: no) vs ActionUsableAgainNextRound (next round: yes) ·
#//           reqboundary=N/A (each use is a single self-contained action; no state read across a later
#//           request)

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

---

# StolenHondo_PlaysNewControllersDeck
#// LAW_094 Hondo — "Action: play the top card of YOUR deck." When an opponent steals Hondo (control-take),
#// the action plays the top of the NEW CONTROLLER's deck, not the owner's. P2 controls a stolen Hondo
#// (owned by P1, seated directly); P2 uses the action and plays P2's deck-top SOR_095 into P2's arena.

## GIVEN
CommonSetup: ggw/ggw/{}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 3
WithP2GroundArenaControlled: LAW_094:1
WithP2Deck: SOR_095
WithP2Deck: SOR_128

## WHEN
- P2>UseUnitAbility:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:SOR_095

---

# StolenHondo_OncePerRound
#// LAW_094 Hondo — "Use this ability only once each round" is tracked per player. The new controller (P2)
#// can use the stolen Hondo once; a second use the same round is a no-op (SOR_128 stays on top of P2's deck).

## GIVEN
CommonSetup: ggw/ggw/{}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 6
WithP2GroundArenaControlled: LAW_094:1
WithP2Deck: SOR_095
WithP2Deck: SOR_128

## WHEN
- P2>UseUnitAbility:myGroundArena-0
- P2>UseUnitAbility:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2DECKCOUNT:1

---

# ActionUsableAgainNextRound
#// LAW_094 Hondo Ohnaka — "once each round" resets at the round boundary. Round 1: the action plays the
#// deck-top SOR_063 (cost 3). Cross regroup (both players decline the optional resource; each draws 2 —
#// P1 draws the two SOR_095 fillers). Round 2: the action is available again and plays the new deck-top
#// SOR_063. End: Hondo + two Wing Guards in P1's arena, deck empty, the 2 regroup draws in hand.

## GIVEN
CommonSetup: byk/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: LAW_094:1:0
WithP1Deck: [SOR_063 SOR_095 SOR_095 SOR_063]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:3
P1DECKCOUNT:0
P1HANDCOUNT:2
