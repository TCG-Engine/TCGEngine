# CombatBaseDamage_SacFor3
#// SEC_150 Valiant Commando (Ground, 3/3) — When this unit deals combat damage to a base: you may defeat
#//   this unit; if you do, deal 3 to that base. Attacks P2 base (3), then sacrifices for 3 more (total 6).

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_150:1:0
WithP1Hand: SEC_150

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:6
P1GROUNDARENACOUNT:0
P1NODECISION
