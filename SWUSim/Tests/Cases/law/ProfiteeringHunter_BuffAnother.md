# LAW_151 Profiteering Hunter (1/3) — When Played: another friendly unit gets +1/+1 for this phase.
# The only other friendly unit (SOR_095) auto-targets -> 4/4.

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_151

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
