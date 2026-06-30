# LOF_124 Niman Strike — Attack with a Force unit, even if exhausted; it gets +1/+0 and can't attack bases.
# The exhausted Plo Koon (6 power → 7 with the bonus) attacks the only legal target SOR_046 (3/7) and
# defeats it, taking 3 counter damage.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_124}
P1OnlyActions: true
WithP1GroundArena: LOF_050:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3
