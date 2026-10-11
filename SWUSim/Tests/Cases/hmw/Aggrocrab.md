# WithInitiative_CostsFour
#// COVERAGE: offer=N/A (STRUCTURAL: a cost modifier, nothing chosen) · decline=N/A (STRUCTURAL: no "may")
#//           boundary=WithoutInitiative_FourIsNotEnough (4 resources: playable only with the discount)
#//           quantity=WithInitiative_FiveResources_OneLeft (exactly 1 less, not more)
#//           control=N/A (the cost is the player's own) · reqboundary=N/A (STRUCTURAL: computed at play time)
#//           modes=2P only ("you have the initiative" is self-only)
#//
#// HMW_184 Aggrocrab — Unit (Ground) 6/4, cost 5, [Aggression], Creature.
#// "While you have the initiative, this unit costs 1 resource less to play."

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Hand: HMW_184

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:0

---

# WithInitiative_FiveResources_OneLeft

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Hand: HMW_184

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:1

---

# WithoutInitiative_FourIsNotEnough

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1Hand: HMW_184

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:4

---

# WithoutInitiative_PaysFullFive

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1Hand: HMW_184

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:0

---

# ClaimedInitiativeStillCounts

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1Hand: HMW_184

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:0
