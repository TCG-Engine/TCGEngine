# SOR_129 Admiral Ozzel — Action [Exhaust]: Play an Imperial unit from your hand (paying its cost).
# It enters play READY. Each opponent may ready a unit. Ozzel chooses SEC_080 from two hand Imperials;
# it enters READY (not the default exhausted); the unchosen SOR_128 stays in hand; then P2 readies its
# exhausted SOR_046. Ozzel is exhausted (paid the [Exhaust] action cost).

## GIVEN
CommonSetup: ryk/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SOR_129:1:0
WithP1Hand: SEC_080
WithP1Hand: SOR_128
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:READY
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:READY
