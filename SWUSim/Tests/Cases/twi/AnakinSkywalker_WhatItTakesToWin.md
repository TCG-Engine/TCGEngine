# Deployed_BaseDamagePower
#// TWI_012 Anakin Skywalker (Leader, deployed) — Overwhelm + "This unit gets +1/+0 for every 5 damage on
#// your base." With 10 damage on P1's base, deployed Anakin (4 power) is 6.
## GIVEN
CommonSetup: rrk/bbw/{myResources:6;myBaseDamage:10;myLeader:TWI_012}
P1OnlyActions: true
## WHEN
- P1>DeployLeader
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_012
P1GROUNDARENAUNIT:0:POWER:6

---

# Front_BaseCostAttackBonus
#// TWI_012 Anakin Skywalker (Leader, front) — "Action [Exhaust, deal 2 damage to your base]: Attack with a
#// unit. If it's attacking a unit, it gets +2/+0 for this attack." SOR_095 (3/3 → 5) attacks SOR_046, dealing
#// 5; P1's base takes the 2 cost.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_012}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1BASEDMG:2
