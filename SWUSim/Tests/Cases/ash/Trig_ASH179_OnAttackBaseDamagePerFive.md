# ASH_179 Boba Fett's Rancor — On Attack: you may deal 1 damage to a base for every 5 damage on your
# base. P1's base has 10 damage (= 2), so when Rancor attacks the enemy base the On Attack deals 2 to the
# enemy base, then combat deals 8 → 10 total.
## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:10}
WithP1GroundArena: ASH_179:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:10
