# ASH_195 Helgait defeated via JTL_043 No Glory, Only Results — "Take control of a non-leader unit, then
# defeat it." I take their Helgait (it becomes mine), then defeat it, so its When Defeated fires under MY
# control and distributes its power (6) Advantage among MY units. Here I spread the 6 across two of my
# Battlefield Marines (4 + 2), proving the take-control-then-defeat frame is consistent (controller ==
# defeating player == me) and the distribution lands on my side, divided as I choose.
## GIVEN
CommonSetup: yyk/yyk/{myResources:13;handCardIds:JTL_043}
WithP1GroundArena: SOR_095:1:0          # my Marine A — receives 4 Advantage
WithP1GroundArena: SOR_095:1:0          # my Marine B — receives 2 Advantage
WithP2GroundArena: ASH_195:1:0          # their Helgait (6/4)
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0:4,myGroundArena-1:2
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:4
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:2
P1GROUNDARENAUNIT:0:POWER:7
