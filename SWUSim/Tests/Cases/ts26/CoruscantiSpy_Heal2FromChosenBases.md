# TS26_053 Coruscanti Spy (Unit 0/2, cost 1) — Raid 2 + When Played: heal 2 damage from each of any
# number of bases. Choosing both bases heals 2 from each (P1 5 → 3, P2 5 → 3).
## GIVEN
CommonSetup: ggk/rrk/{myResources:1;handCardIds:TS26_053;myBaseDamage:5;theirBaseDamage:5}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0&theirBase-0
## EXPECT
P1BASEDMG:3
P2BASEDMG:3
