#// ASH_102 Ravager ("When you play a unit: You may have it deal damage equal to its power to a unit in the same
#// arena.") ordered against DRK-1 Probe Droid's own "When Played: You may defeat a non-unique upgrade." Both P1's — P1
#// orders them (CR 7.6.9), and against a SHIELDED target the order decides the kill: the Droid first strips the
#// Shield so Ravager's 2 lands; Ravager first spends its 2 on the Shield.
#//
#// FOUND BY: sweep run 5, retro #8 (2026-09-14): ORDER ASH_102:ASH_102 + LOF_155:WhenPlayed (2×,
#//   piett_red.boba_lakecountry.s086). Newly reachable: Piett red now casts Ravager.
#//
#// Cards: LOF_155 DRK-1 Probe Droid 2/3 ground · SOR_128 Death Star Stormtrooper 3/1 (P2) with a Shield token (SOR_T02,
#//   a non-unique upgrade — addressed as its host's subcard theirGroundArena-0.u0).
#//
# BothTriggers_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LOF_155
WithP2GroundArena: SOR_128:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# DroidFirst_TheShieldIsDefeated_ThenRavagersTwoDefeatsTheStormtrooper
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LOF_155
WithP2GroundArena: SOR_128:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:theirGroundArena-0.u0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1NODECISION

---

# RavagerFirst_TheShieldTakesTheTwo_TheStormtrooperSurvives
#// Ravager's 2 is prevented by the Shield (which is then defeated); the Droid's "you may defeat an upgrade" has no
#//   upgrade left to pick.
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LOF_155
WithP2GroundArena: SOR_128:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_102
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION
