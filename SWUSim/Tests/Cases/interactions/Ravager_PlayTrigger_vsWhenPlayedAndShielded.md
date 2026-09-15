#// ASH_102 Ravager (space, 8/10): "When you play a unit: You may have it deal damage equal to its power to a unit in
#// the same arena." It triggers with the played unit's own When Played / Shielded, and P1 orders them (CR 7.6.9).
#//
#// FOUND BY: sweep retros #4–#6 of run 2 (2026-09-13), control_piett_blue: ORDER ASH_102 + LAW_101:WhenPlayed
#//   (28×) · ASH_102 + SEC_037:WhenPlayed (21×) · ASH_052:WhenPlayed + ASH_102 (20×) · ASH_102 + JTL_089 (17×) ·
#//   ASH_048:Shielded / LAW_118:Shielded + ASH_102.
#//   All sections were green on first run: this file is REGRESSION COVERAGE, not a bug.
#//
#// THE RULE THE FIRST SECTION PINS: CR 7.6.3 — "the triggered ability must resolve once triggered, even if the
#//   card with the ability leaves play before the triggered ability resolves." Chimaera's When Played may defeat
#//   RAVAGER ITSELF (the friendly half of its choice); Ravager's trigger, already pending, still resolves.
#//
#// Cards: ASH_052 Chimaera 6/6 space ("When Played: You may choose a friendly unit and an enemy non-leader unit. If
#//   you do, defeat those units.") · LAW_101 Lawbringer 7/7 space ("When Played/On Attack: Choose an aspect. Give
#//   each enemy unit with that aspect -2/-2 for this phase.") · SEC_037 Cantwell Arrestor Cruiser 6/7 space (may
#//   disclose VigilanceVigilanceVillainy) · ASH_048 Imperial Armored Commando 4/3 ground (Sentinel, Shielded) ·
#//   SOR_237 Alliance X-Wing 2/3 · JTL_069 Munificent Frigate 4/7 · SOR_095 Battlefield Marine 3/3.
#//
# Chimaera_WhenPlayedFirst_DefeatsRavagerItself_RavagersTriggerStillResolves
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: ASH_052
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:ASH_052
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:6

---

# Chimaera_RavagerFirst_SixToTheFrigate_ThenTheWhenPlayedDefeatsRavagerAndTheXWing
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: ASH_052
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_102
- P1>AnswerDecision:theirSpaceArena-1
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:6

---

# Lawbringer_RavagerFirst_SevenDefeatsTheFrigate_ThenHeroismEnemiesGetMinusTwo
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LAW_101
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_102
- P1>AnswerDecision:theirSpaceArena-1
- P1>AnswerDecision:Heroism
## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:POWER:1

---

# Cantwell_RavagerFirst_SixDefeatsTheXWing_NoDiscloseWithAnEmptyHand
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: SEC_037
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_102
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P1NODECISION
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:READY

---

# Commando_RavagerFirst_TheGroundUnitDealsFourInItsOwnArena_ThenShielded
#// The played unit is in the GROUND arena, so Ravager's "same arena" is the ground: only the Marine is offered.
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: ASH_048
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_102
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:CARDID:ASH_048
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
