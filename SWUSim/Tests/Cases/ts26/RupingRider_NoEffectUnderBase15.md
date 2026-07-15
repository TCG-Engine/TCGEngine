# TS26_067 Ruping Rider — with only 14 damage on your base, the condition fails: no damage is dealt to
# any base (enemy base stays at 0).
## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:TS26_067;myBaseDamage:14}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2BASEDMG:0
