# WhenDefeatedOppGivesExp
#// TS26_54 Wartime Mercenaries (Unit 5/5, cost 4) — When Defeated: an opponent may give an Experience
#// token to a unit. The Mercenaries (pre-damaged) attack LAW_124 and die; P2 (the opponent) gives 1
#// Experience to its own LAW_124 (4 power → 5).
## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1GroundArena: TS26_54:1:3
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:POWER:5
