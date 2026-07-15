# TS26_011 Executioner's Arena (Base, Aggression) — Epic Action: for each friendly leader unit, you may
# deal 2 damage to a unit. With one deployed leader unit, deal 2 to the enemy LAW_124.
## GIVEN
CommonSetup: rrk/rrk/{myBase:TS26_011;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1BASE:EPICUSED
