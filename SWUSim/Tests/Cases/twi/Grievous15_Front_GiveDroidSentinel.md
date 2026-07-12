# TWI_015 General Grievous (Leader, front) — "Action [Exhaust]: Give a Droid unit Sentinel for this phase."
# The Battle Droid token gains Sentinel.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_015}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
