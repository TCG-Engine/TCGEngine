# LAW_102 Choke on Aspirations — if the unit does NOT survive, no heal. Deal 5 to SEC_080 (3/3, dies);
# base stays damaged.

## GIVEN
CommonSetup: brk/rrk/{myResources:1;myBaseDamage:5}
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_102

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:5

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:5
P1DISCARDCOUNT:2
