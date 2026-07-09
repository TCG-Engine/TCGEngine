# SHD_004 Rey (deployed) — Restore 3 (heal 3 from your base when she attacks) + On Attack: You may give
# an Experience token to a unit with 2 or less power. Deployed (6 resources), Rey attacks the base:
# Restore heals P1's base from 5 → 2, and her On Attack gives SHD_095 (power 2) an Experience token.

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
