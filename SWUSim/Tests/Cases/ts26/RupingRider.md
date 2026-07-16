# Deal2WhenBase15Plus
#// TS26_067 Ruping Rider (Unit 3/4, cost 4) — Grit + When Played: if your base has 15 or more damage on
#// it, deal 2 damage to a base. With P1's base at 15 damage, deal 2 to the enemy base.
## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:TS26_067;myBaseDamage:15}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:2

---

# NoEffectUnderBase15
#// TS26_067 Ruping Rider — with only 14 damage on your base, the condition fails: no damage is dealt to
#// any base (enemy base stays at 0).
## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:TS26_067;myBaseDamage:14}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2BASEDMG:0
