# OnAttackBaseDamagePerFive
#// ASH_179 Boba Fett's Rancor — On Attack: you may deal 1 damage to a base for every 5 damage on your
#// base. P1's base has 10 damage (= 2), so when Rancor attacks the enemy base the On Attack deals 2 to the
#// enemy base, then combat deals 8 → 10 total.
## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:10}
WithP1GroundArena: ASH_179:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:10

---

# WhenPlayedDamage
#// ASH_179 Boba Fett's Rancor (Ground, 8/9, cost 8) — When Played: deal 5 to your base; then deal 5 to an
#// enemy ground unit; then deal 5 to the same unit. P1's base takes 5; SOR_046 (3/7) takes 5+5 = 10 and is
#// defeated.
## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:ASH_179}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:5
P2GROUNDARENACOUNT:0
