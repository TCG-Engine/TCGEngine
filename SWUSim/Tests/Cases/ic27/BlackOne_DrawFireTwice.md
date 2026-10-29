# WhenPlayed_DrawsACard
#// IC27_116 Black One — Draw Fire, Twice (2/2 Space, Aggression/Heroism, Resistance·Vehicle·Fighter, unique)
#// "When Played/When Defeated: Draw a card."
#// One ability on two triggers: $whenPlayedAbilities and $whenDefeatedAbilities share one closure (DoDrawCard for
#// the resolving player — the controller when played / when defeated).
#//
#// COVERAGE: offer=N/A (no choice anywhere on the card) ·
#//           decline=N/A (no "you may") ·
#//           boundary=N/A (no threshold) ·
#//           control=WhenDefeated_StolenCopy_ControllerDraws (the NEW controller resolves the When Defeated) ·
#//           reqboundary=N/A (no decision and nothing stored — each trigger draws inline) ·
#//           modes=2P only (no player reference, no friendly/enemy wording)

## GIVEN
CommonSetup: rrw/bbk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_116
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:IC27_116
P1HANDCOUNT:1
P1DECKCOUNT:2
P1NODECISION

---

# WhenDefeated_InCombat_Draws
#// Black One (2/2) attacks an Alliance X-Wing (2/3) and dies to the counter: P1 draws.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_116:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# WhenDefeated_ByAnEffect_Draws
#// The effect-defeat path: P1's own Vanquish (Vigilance, off-aspect: 5 + 2) defeats Black One. P1 draws.

## GIVEN
CommonSetup: rrw/bbk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_078
WithP1SpaceArena: IC27_116:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# WhenDefeated_OnTheOpponentsTurn_OwnerDraws
#// P2's X-Wing attacks and defeats Black One (which has 1 damage). The When Defeated is P1's — P1 draws, P2 does not.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: IC27_116:1:1
WithP2SpaceArena: SOR_237:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P2>AttackSpaceArena:0:0
- P1>Drain

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P2HANDCOUNT:0

---

# WhenDefeated_StolenCopy_ControllerDraws
#// Black One OWNED by P2 but controlled by P1 dies attacking: the controller at defeat (P1) resolves the When
#// Defeated and draws; the card goes to its owner's discard.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArenaControlled: IC27_116:2
WithP2SpaceArena: SOR_237:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P2HANDCOUNT:0
P2DISCARDCOUNT:1

---

# WhenDefeated_Blanked_NoDraw
#// A Black One that has lost all abilities (SOR_138 marker) has no When Defeated.

## GIVEN
CommonSetup: rrw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_116:1:0:SOR_138
WithP2SpaceArena: SOR_237:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:3
