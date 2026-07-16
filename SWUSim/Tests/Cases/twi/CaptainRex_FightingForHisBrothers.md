# Deployed_CloneAndTrooperBuff
#// TWI_007 Captain Rex (Leader, deployed) — "When Deployed: Create a Clone Trooper token. Each other
#// friendly Trooper unit gets +0/+1." Deploying Rex creates a Clone and buffs SOR_095 (Trooper) to 3/4.
## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:TWI_007}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:2:CARDID:TWI_T02

---

# Front_FriendlyAttacked_Clone
#// TWI_007 Captain Rex (Leader, front) — "Action [2 resources, Exhaust]: If a friendly unit attacked this
#// phase, create a Clone Trooper token." SOR_095 attacks the base (a friendly attack this phase); then Rex's
#// action creates a Clone.
## GIVEN
CommonSetup: rrk/bbw/{myResources:2;myLeader:TWI_007}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_T02
P1RESAVAILABLE:0
