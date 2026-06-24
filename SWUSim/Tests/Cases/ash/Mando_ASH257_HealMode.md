# ASH_257 Choose Your Path (Event) — Heal mode: control a Force unit → heal 5 from your base.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myBaseDamage:6;handCardIds:ASH_257}
WithP1GroundArena: SOR_049:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Heal
## EXPECT
P1BASEDMG:1
