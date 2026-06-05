# SOR_241 Wing Leader (Space, 2/1) — When Played: give 2 Experience tokens to another
# friendly REBEL unit. P1's Battlefield Marine (SOR_095, Rebel, 3/3) is the only other
# Rebel → auto-receives +2/+2 (→ 5/5).

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;handCardIds:SOR_241}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
