# Deal1EnemyBaseHealOwn
#// TS26_19 Coleman Trebor (Unit 2/2, cost 1) — Hidden. When Played: deal 1 to each enemy base, then heal
#// 1 from your base per damage dealt. In 2-player: 1 damage to the enemy base → heal 1 from your own base.
## GIVEN
CommonSetup: bgw/rrk/{myResources:3;myBaseDamage:3;handCardIds:TS26_19}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2BASEDMG:1
P1BASEDMG:2
