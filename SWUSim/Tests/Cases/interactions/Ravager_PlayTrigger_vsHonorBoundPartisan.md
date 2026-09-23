#// ASH_102 Ravager: "When you play a unit: You may have it deal damage equal to its power to a unit in the same
#// arena." Honor-Bound Partisan's own "When Played: Deal 1 damage to a base." fires on the same play, both P1's, so
#// P1 orders them (CR 7.6.9). Same family as Ravager_PlayTrigger_vsWhenPlayedAndShielded.md.
#//
#// FOUND BY: sweep run 5, retro #3 (2026-09-14): ORDER ASH_102:ASH_102 + LAW_058:WhenPlayed (1×,
#//   vader_yellow.piett_red.s035). Newly reachable: Piett red now casts Ravager (Phase 1b part 2, 'keep').
#//
#// Cards: LAW_058 Honor-Bound Partisan 2/2 ground · ASH_102 Ravager 8/10 space · SOR_128 Death Star Stormtrooper 3/1
#//   (P2, ground — Partisan's 2 defeats it) · SOR_095 Battlefield Marine 3/3 (P2, ground).
#//
# BothTriggers_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LAW_058
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# RavagerFirst_PartisanDefeatsTheStormtrooper_ThenOneToTheBase
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LAW_058
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_102
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2BASEDMG:1
P1NODECISION

---

# PartisanFirst_OneToTheBase_ThenRavagerTwoOnTheMarine
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LAW_058
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2BASEDMG:1
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:DAMAGE:2
P1NODECISION
