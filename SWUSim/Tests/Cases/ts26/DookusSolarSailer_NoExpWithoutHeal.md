# TS26_038 Dooku's Solar Sailer — with no base healed this phase, playing the Sailer gives no Experience:
# the friendly Battle Droid stays at 1 power (no decision, no Experience).
## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_038}
WithP1GroundArena: TS26_T01:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:1
