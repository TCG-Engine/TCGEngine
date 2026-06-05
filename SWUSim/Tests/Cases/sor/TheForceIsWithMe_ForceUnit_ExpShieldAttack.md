# SOR_055 The Force Is With Me (Vigilance/Heroism event, cost 4, Force) — "Choose a friendly unit and
# give 2 Experience tokens to it. If you control a FORCE unit, also give a Shield token to it. You may
# attack with the chosen unit." P1 chooses SOR_049 Obi-Wan (a Force unit, 4/6): +2 Experience → 5/8,
# +1 Shield (Force controlled), then attacks the enemy base for 5.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_049:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_055

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:6
P2BASEDMG:6
