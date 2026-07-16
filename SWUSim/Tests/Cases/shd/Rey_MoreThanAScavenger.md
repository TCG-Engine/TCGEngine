# Rey_Deployed_Restore3_OnAttackExp
#// SHD_004 Rey (deployed) — Restore 3 (heal 3 from your base when she attacks) + On Attack: You may give
#// an Experience token to a unit with 2 or less power. Deployed (6 resources), Rey attacks the base:
#// Restore heals P1's base from 5 → 2, and her On Attack gives SHD_095 (power 2) an Experience token.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_004;myResources:6;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1BASEDMG:2
P1GROUNDARENAUNIT:0:POWER:3

---

# Rey_Front_ExpToLowPower
#// SHD_004 Rey (front Action [1 resource, Exhaust]) — "Give an Experience token to a unit with 2 or less
#// power." SHD_095 (power 2) is the lone eligible target → gets an Experience token (2/3 → 3/4). Rey
#// exhausts and 1 resource is spent.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_004}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SHD_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
