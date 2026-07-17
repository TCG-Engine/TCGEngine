# DeployedTokenAttackBuff
#// TS26_07 Asajj Ventress (leader deployed, 3/5) — Hidden + "While you've attacked with a token unit this
#// phase, this unit gets +2/+0." After the Battle Droid token attacks, deployed Asajj is 5 power.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TS26_07:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_T01:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TS26_07
P1GROUNDARENAUNIT:1:POWER:5

---

# FrontAttackWithToken
#// TS26_07 Asajj Ventress (leader front) — Action [Exhaust]: attack with a token unit; it gets +1/+0 for
#// this attack. The Battle Droid token (1 power) attacks the enemy base with +1 → deals 2.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TS26_07}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_T01:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P2BASEDMG:2
P1LEADER:EXHAUSTED
