# ASH_094 — control: with no Jerjerrod in play, SEC_191's "create 2 Spy tokens" resolves normally and no
# doubling offer is made (no dangling decision). Final P1 ground = SEC_191 + 2 Spy = 3.
## GIVEN
CommonSetup: yyk/yyk/{myResources:5;handCardIds:SEC_191}
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:3
