# SHD_033 Synara San — a READY Synara shows NO Bounty badge (the conditional keyword is
# exhausted-only; Status 1 = ready, 0 = exhausted). Guards the Status check in
# HasConditionalKeyword_Bounty.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SHD_033:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Bounty
