# Deployed_OnAttackDroidBuff
#// TWI_015 General Grievous (Leader, deployed) — "On Attack: You may give a Droid unit +1/+0 and Sentinel
#// for this phase." Deployed and attacking, Grievous buffs the Battle Droid (1 → 2 power, Sentinel).
## GIVEN
CommonSetup: rrk/bbw/{myResources:6;myLeader:TWI_015}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# Front_GiveDroidSentinel
#// TWI_015 General Grievous (Leader, front) — "Action [Exhaust]: Give a Droid unit Sentinel for this phase."
#// The Battle Droid token gains Sentinel.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_015}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
