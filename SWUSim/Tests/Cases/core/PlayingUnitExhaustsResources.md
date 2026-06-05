## GIVEN
# Han Solo (Cunning+Heroism) + Echo Base (Command) covers Leia's aspects → 0 penalty
CommonSetup: gyw/ggk/{myResources:3;handCardIds:SOR_189}

## WHEN
# Leia Organa: cost 2, 0 penalty → exhausts 2, leaves 1 ready
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
