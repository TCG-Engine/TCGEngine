# LOF_140 Maul's Lightsaber (+4/+2) — When Played: if the attached unit is Darth Maul, you may attack with
# him; for this attack he gains Overwhelm and can't attack bases. Attached to TWI_135 (Darth Maul, 5/6 →
# 9/8), he attacks SOR_059 (1/3): 9 power kills it and the 6 excess overwhelms onto the base; the 1 counter
# hits Maul.

## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:LOF_140}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:6
P1GROUNDARENAUNIT:0:DAMAGE:1
