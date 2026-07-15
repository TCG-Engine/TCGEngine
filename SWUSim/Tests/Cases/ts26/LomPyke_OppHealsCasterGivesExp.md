# TS26_051 Lom Pyke (Unit 5/5, cost 5) — When Played: each opponent may heal 5 from their base; for each
# that does, give 2 Experience tokens to a unit. The opponent heals their base (5 → 0), and the caster
# gives 2 Experience to SEC_080 (3 power → 5).
## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_051;theirBaseDamage:5}
WithP1GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:POWER:5
