# LAW_264 From a Certain Point of View (neutral event, cost 1) — "Play a card from your hand, ignoring
# its aspect penalties." With a Cunning/Villainy leader+base, SOR_095 (Command,Heroism, cost 2) is
# fully off-aspect (+4 -> would cost 6). After the event waives the penalty it costs just 2: it plays
# with only 2 ready resources left (proving the waiver), leaving 0.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
WithP1Hand: SOR_095
WithP1Hand: LAW_264

## WHEN
# Only SOR_095 remains in hand after playing the event, so the play-choice auto-resolves.
- P1>PlayHand:1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1RESAVAILABLE:0
