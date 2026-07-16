# DeployedOnAttackShieldEntered
#// TS26_002 Anakin Skywalker (leader deployed, 4/5) — Sentinel + On Attack: give a Shield token to another
#// friendly unit that entered play this phase. After playing SEC_080 this phase, deployed Anakin attacks
#// LAW_124 and shields the entered SEC_080.
## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_002:1:1;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# FrontShieldEnteredUnit
#// TS26_002 Anakin Skywalker (leader front) — Action [Exhaust]: if 2+ friendly units entered play this
#// phase, give a Shield token to 1 of them. After playing 2 units this phase, shield SEC_080.
## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_002;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SEC_080 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EXHAUSTED
