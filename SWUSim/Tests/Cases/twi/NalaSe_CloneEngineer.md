# AspectIgnoreClone
#// TWI_001 Nala Se (Leader, front) — "Ignore the aspect penalty on Clone units you play." With Nala Se as
#// P1's leader, TWI_109 (Clone, Command, cost 3) plays for its printed 3 despite the off-aspect base, so 3
#// resources suffice.
## GIVEN
CommonSetup: yyk/rrk/{myResources:3;myLeader:TWI_001;handCardIds:TWI_109}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_109
P1RESAVAILABLE:0

---

# Deployed_CloneDefeatedHeals
#// TWI_001 Nala Se (Leader, deployed) — "Each friendly Clone unit gains: When Defeated: Heal 2 damage from
#// your base." With Nala Se deployed and P1's base at 5, the Clone TWI_109 attacks SOR_046 (3/7) and dies to
#// the counter, healing 2 from P1's base (5 → 3).
## GIVEN
CommonSetup: yyk/rrk/{myBaseDamage:5;myLeader:TWI_001:1:1}
P1OnlyActions: true
WithP1GroundArena: TWI_109:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1BASEDMG:3
