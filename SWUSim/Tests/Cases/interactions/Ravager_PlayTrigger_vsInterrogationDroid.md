#// ASH_102 Ravager ("When you play a unit: You may have it deal damage equal to its power to a unit in the same
#// arena.") ordered against Interrogation Droid's own "When Played: Exhaust an enemy unit. If you do and that unit
#// costs 3 or less, its controller discards a card from their hand." Both P1's — P1 orders them (CR 7.6.9), and the
#// order decides whether P2 discards: Ravager first removes the cheap target the discard needs.
#//
#// FOUND BY: sweep run 5, retro #7 (2026-09-14): ORDER ASH_102:ASH_102 + LAW_075:WhenPlayed (1×,
#//   luke_datavault.piett_red.s085). Newly reachable: Piett red now casts Ravager.
#//
#// Cards: LAW_075 Interrogation Droid 3/1 ground · SOR_095 Battlefield Marine 3/3 cost 2 (P2) · SOR_164 Wampa 4/5
#//   cost 4 (P2) · P2 holds two cards.
#//
# BothTriggers_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LAW_075
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
WithP2Hand: [SOR_046 SOR_046]
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# RavagerFirst_TheMarineIsDefeated_TheExhaustFindsOnlyTheWampa_NoDiscard
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LAW_075
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
WithP2Hand: [SOR_046 SOR_046]
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_102
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:2
P1NODECISION

---

# DroidFirst_TheMarineIsExhausted_P2Discards_ThenRavagerDefeatsIt
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LAW_075
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
WithP2Hand: [SOR_046 SOR_046]
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2HANDCOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:READY
P1NODECISION
