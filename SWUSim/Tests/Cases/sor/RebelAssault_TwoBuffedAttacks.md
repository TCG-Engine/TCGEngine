# SOR_103 Rebel Assault — Event (cost 1, Command/Heroism): "Attack with a Rebel unit. It gets
# +1/+0 for this attack. Then, attack with another Rebel unit. It gets +1/+0 for this attack."
# P1 has two 3-power Rebels; each attacks the base for 3+1=4 → 8 total. The +1 is one-shot per
# attack (POWER stays 3 on both afterward).

## GIVEN
CommonSetup: ggw/grk/{myResources:1;handCardIds:SOR_103}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:8
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:3
