# TS26_038 Dooku's Solar Sailer (Unit 2/4 space, cost 3) — When Played/On Attack: if a base was healed
# this phase, give an Experience token to another Separatist unit. Jendirian Valley (Restore 1) attacks
# and heals P1's base; then playing the Sailer gives 1 Experience to the friendly Battle Droid (Separatist).
## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_038;myBaseDamage:3}
WithP1SpaceArena: TS26_018:1:0
WithP1GroundArena: TS26_T01:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
