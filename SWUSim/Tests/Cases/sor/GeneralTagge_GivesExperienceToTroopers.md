# SOR_080 General Tagge (2/2) — When Played: give an Experience token to each of
# up to 3 Trooper units. P1 controls two Troopers — Battlefield Marine (SOR_095,
# 3/3) and Scout Bike Pursuer (SOR_032, 1/4). Playing Tagge prompts a multi-select;
# choosing both gives each an Experience token (+1/+1): Marine → 4/4, Scout → 2/5.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_080}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0    # Battlefield Marine (Trooper, 3/3) — index 0
WithP1GroundArena: SOR_032:1:0    # Scout Bike Pursuer (Trooper, 1/4) — index 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENACOUNT:3
