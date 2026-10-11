# DrawsThreeFromTheTop
#// COVERAGE: offer=N/A (STRUCTURAL: nothing chosen) · decline=N/A (STRUCTURAL: mandatory)
#//           boundary=ShortDeck_DrawsWhatIsThere_TakesDeckOutDamage · empty=EmptyDeck_OneNineDamageEvent
#//           control=N/A (the drawing player is the controller) · reqboundary=N/A (STRUCTURAL: no decision)
#//           modes=2P only (no player reference)
#//
#// HMW_189 Neebray Manta — Unit (Space) 7/6, cost 8, [Aggression], Creature.
#// "When Played: Draw 3 cards."

## GIVEN
CommonSetup: rrk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_189
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:3
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_046
P2HANDCOUNT:0
P1BASEDMG:0

---

# ShortDeck_DrawsWhatIsThere_TakesDeckOutDamage

## GIVEN
CommonSetup: rrk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_189
WithP1Deck: [SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:0
P1BASEDMG:3

---

# EmptyDeck_OneNineDamageEvent

## GIVEN
CommonSetup: rrk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_189

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1BASEDMG:9

---

# PlacedButNotPlayed_NoDraw

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_189:1:0
WithP1Deck: [SOR_095 SEC_080 SOR_128]

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
