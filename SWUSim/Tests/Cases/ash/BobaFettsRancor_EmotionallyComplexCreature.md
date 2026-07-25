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

---

# OnAttack_LessThanFiveBaseDamage_NoBonus
#// ASH_179 Boba Fett's Rancor — the On Attack deals 1 per FULL 5 damage on your base. With only 4 damage on
#// P1's base (< 5), the bonus is 0, so the enemy base takes just the 8 combat damage.
## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:4}
WithP1GroundArena: ASH_179:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:8

---

# WhenPlayed_FirstDamageDefeats_SecondFizzles
#// ASH_179 Boba Fett's Rancor — When Played deals 5 to a chosen enemy ground unit, THEN 5 more to the SAME
#// unit. If the first 5 defeats that unit, the second 5 has no target and does NOT carry over to another
#// unit. P1 targets SOR_095 (3/3): it dies from the first 5 while the bystander SEC_080 is untouched.
## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:ASH_179}
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:5
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_NoEnemyGroundUnits
#// ASH_179 Boba Fett's Rancor — with no enemy ground unit to target, the two follow-up 5-damage effects
#// simply fizzle; only the "deal 5 to your base" resolves. P1's base takes 5.
## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:ASH_179}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:5
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_179

---

# OnAttack_DealToFriendlyBase
#// ASH_179 Boba Fett's Rancor — the On Attack "deal 1 per 5 damage on your base" may target EITHER base.
#// P1's base has 13 damage (= 2), and P1 chooses to deal the 2 to its OWN base (13 -> 15). Rancor also
#// takes 3 counter from the SOR_095 (3/3) it defeats in combat.
## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:13}
WithP1GroundArena: ASH_179:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:15
P2BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0

---

# OnAttack_PassBaseDamage
#// ASH_179 Boba Fett's Rancor — the On Attack base-damage is optional. With 13 damage on P1's base P1
#// declines the bonus, so no base takes the extra damage; combat still defeats the SOR_095 and Rancor takes
#// 3 counter.
## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:13}
WithP1GroundArena: ASH_179:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:PASS
## EXPECT
P1BASEDMG:13
P2BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
