# LOF_105 Oppo Rancisis — gains a keyword while another friendly unit has it. With LOF_044 (Sentinel) and
# SOR_164 (Overwhelm) in play, Rancisis gains both Sentinel and Overwhelm.

## GIVEN
CommonSetup: ggw/rrk
WithP1GroundArena: LOF_105:1:0
WithP1GroundArena: LOF_044:1:0
WithP1GroundArena: SOR_164:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
