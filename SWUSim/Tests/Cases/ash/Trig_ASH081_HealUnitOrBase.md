# ASH_081 Nebulon-C Frigate (Space, 3/6, cost 5) — When Played: you may heal 3 damage from a unit or
# base. P1's base starts at 3 damage; playing the Frigate heals 3 from it (3 → 0).
## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:ASH_081;myBaseDamage:3}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:0
