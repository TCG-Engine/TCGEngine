# CostReductionAndDebuffOthers
#// TS26_36 Tribunal (Unit 6/8 space, cost 10) — costs 2 less per other card played this phase; When
#// Played: give each OTHER unit -2/-2 for this phase. P1 first plays Take Action (5 here — 3 printed +2
#// for the uncovered Aggression), which deals 3 to the enemy LAW_124 and is the ONE other card played
#// this phase, so Tribunal costs 10 - 2 = 8. 13 - 5 - 8 = 0, and it is only affordable at all because of
#// that discount. On entry every OTHER
#// unit gets -2/-2 (Tribunal itself is excluded): friendly SEC_080 (3/3) → power 1; enemy LAW_124 (4/7)
#// → power 2; Tribunal stays 6 power.
## GIVEN
CommonSetup: byk/rrk/{myResources:13}
WithP1Hand: [TS26_71 TS26_36]
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:POWER:6

---

# FullPriceWhenNoOtherCardWasPlayedThisPhase
#// TS26_36 Tribunal — the zero case of "for each OTHER card you played this phase". Played as the first
#// card of the phase it costs the printed 10, emptying a 10-resource pool.
#// Discriminating: Tribunal used to be counted in its OWN discount, so this cost 8 and left 2 behind.

## GIVEN
CommonSetup: byk/rrk/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_36
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1SPACEARENACOUNT:1

---

# TheOPPONENTSCardsDoNotReduceTheCost
#// TS26_36 Tribunal — "each other card YOU played". P2 plays a unit first; that is not P1's card, so
#// Tribunal is still full price at 10 and P1's pool ends at 0.

## GIVEN
CommonSetup: byk/byk/{myResources:10;theirResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithP1Hand: TS26_36
WithP2Hand: SOR_095
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1SPACEARENACOUNT:1

---

# CardsPlayedInAPREVIOUSPhaseDoNotReduceTheCost
#// TS26_36 Tribunal — "this phase". P1 plays SOR_095, then both players pass out the action phase and
#// decline the next round's resource step. Tribunal is the first card of the NEW phase, so the earlier
#// play is forgotten and it costs the full 10.

## GIVEN
CommonSetup: byk/rrk/{myResources:10}
SkipPreGame: true
WithInitiativePlayer: 1
WithP1Hand: [SOR_095 TS26_36]
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1SPACEARENACOUNT:1
