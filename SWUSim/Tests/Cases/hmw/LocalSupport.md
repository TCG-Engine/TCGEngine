# SharesATraitWithAFriendlyUnit_Draws
#// COVERAGE: offer=N/A (STRUCTURAL: nothing chosen — the reveal and the draw are automatic)
#//           decline=N/A (STRUCTURAL: no "may") · boundary=N/A (STRUCTURAL: a trait match)
#//           negative=NoSharedTrait_StaysOnTop · scope=EnemyUnitsTraitsDoNotCount · empty=EmptyDeck_NothingHappens
#//           control=N/A · reqboundary=N/A (STRUCTURAL: no decision)
#//           modes=2P,TeamSuns (text says "a friendly unit") — TeamSuns_TeammatesUnitCounts
#//
#// HMW_148 Local Support — Upgrade +1/+3, cost 2, [Command], Supply.
#// "When Played: Reveal the top card of your deck. If it shares a trait with a friendly unit, draw it."
#// Host SOR_095 Battlefield Marine (Rebel/Trooper); top card SOR_046 (Rebel/Trooper).

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_148
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_128

---

# NoSharedTrait_StaysOnTop
#// SOR_225 TIE/ln Fighter is Imperial/Vehicle/Fighter — nothing in common with Rebel/Trooper.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_148
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_225 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_225

---

# EnemyUnitsTraitsDoNotCount
#// No friendly unit: Local Support attaches to P2's SOR_095. The top card shares its traits, but it is not friendly.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_148
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:0
P1DECKTOPCARD:SOR_046

---

# EmptyDeck_NothingHappens

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_148
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1BASEDMG:0

---

# TeamSuns_TeammatesUnitCounts
#// Local Support goes on P1's SOR_237 X-Wing (Rebel/Vehicle/Fighter); the top card SEC_080 (Imperial/Droid/
#// Trooper) shares only with the teammate's SOR_128 Stormtrooper (Imperial/Trooper).

## GIVEN
CommonSetup: ggw/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_148
WithP1SpaceArena: SOR_237:1:0
WithP3GroundArena: SOR_128:1:0
WithP1Deck: [SEC_080 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1HANDCOUNT:1
P1DECKTOPCARD:SOR_046
