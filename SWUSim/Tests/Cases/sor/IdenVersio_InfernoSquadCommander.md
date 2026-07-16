# Deploy_GetsShield
#// SOR_002 Iden Versio — Deploy: Shielded keyword gives Shield token on enter.

## GIVEN
CommonSetup: bbk/grk/{myResources:6}

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:DEPLOYED
P1LEADER:EPICUSED

---

# LeaderAction_HealBase
#// SOR_002 Iden Versio — Leader Action: Heal Base
#// Enemy unit defeated this phase → heal 1 from P1 base.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:2:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:2
P1LEADER:EXHAUSTED

---

# LeaderAction_NoHeal
#// SOR_002 Iden Versio — Leader Action: No Heal
#// No enemy defeated this phase → leader exhausts but base stays damaged.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3}

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3
P1LEADER:EXHAUSTED
