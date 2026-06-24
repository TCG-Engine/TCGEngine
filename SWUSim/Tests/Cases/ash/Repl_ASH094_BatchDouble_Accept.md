# ASH_094 Moff Jerjerrod — "If you would create a number of tokens, you may defeat this unit. If you do,
# create twice that number of tokens instead." P1 controls Jerjerrod and plays SEC_191 (When Played:
# create 2 Spy tokens). The batch creation offers the doubling ONCE; P1 accepts → Jerjerrod is defeated
# and 2 MORE Spies are created (4 total). Final P1 ground = SEC_191 + 4 Spy = 5 (Jerjerrod gone).
## GIVEN
CommonSetup: yyk/yyk/{myResources:5;handCardIds:SEC_191}
WithActivePlayer: 1
WithP1GroundArena: ASH_094:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:5
