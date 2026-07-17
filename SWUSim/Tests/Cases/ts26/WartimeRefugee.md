# OnAttackOpponentHealsBase
#// TS26_43 Wartime Refugee (Unit 2/3, cost 1) — On Attack: an opponent heals 1 damage from their base.
#// Wartime Refugee attacks the enemy LAW_124 (7 HP, no base combat damage), so the ONLY base change is
#// the On-Attack heal: P2's base damage 3 → 2. Combat deals 2 to LAW_124.
## GIVEN
CommonSetup: bbw/rrk/{theirBaseDamage:3}
WithP1GroundArena: TS26_43:1:0
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2
