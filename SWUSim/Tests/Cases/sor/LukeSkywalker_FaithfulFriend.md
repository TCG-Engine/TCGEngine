# Deploy_OnAttack_Decline
#// SOR_005 Luke Skywalker — Deployed: OnAttack NO → no shield given.

## GIVEN
CommonSetup: gbw/grw/{myResources:6}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1LEADER:EPICUSED

---

# Deploy_OnAttack_ShieldAnotherUnit
#// SOR_005 Luke Skywalker — Deployed: OnAttack YES → give Shield to another unit.
#// Luke attacks base; OnAttack gives shield to P2's unit (valid "another unit" target).

## GIVEN
CommonSetup: gbw/grw/{myResources:6}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EPICUSED

---

# LeaderAction_NotPlayedThisPhase
#// SOR_005 Luke Skywalker — Leader Action: No shield when unit not played this phase.
#// SOR_095 is pre-existing (GIVEN), not played this phase — no valid targets.

## GIVEN
CommonSetup: gbw/grw/{myResources:1}
WithP1GroundArena: SOR_095:2:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderAction_ShieldPlayedUnit
#// SOR_005 Luke Skywalker — Leader Action: Shield a Heroism unit played this phase.

## GIVEN
CommonSetup: gbw/grw/{myResources:3;handCardIds:SOR_095}
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
