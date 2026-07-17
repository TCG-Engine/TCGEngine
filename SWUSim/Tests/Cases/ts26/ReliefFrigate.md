# ChooseBaseHealOthers
#// TS26_42 Relief Frigate (Unit 3/7 space, cost 5) — When Played: choose a base; heal 3 from each OTHER
#// base. Choosing the enemy base heals 3 from your own (damage 5 → 2); the chosen enemy base is unhealed.
## GIVEN
CommonSetup: bgw/rrk/{myResources:5;myBaseDamage:5;theirBaseDamage:4;handCardIds:TS26_42}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1BASEDMG:2
P2BASEDMG:4
