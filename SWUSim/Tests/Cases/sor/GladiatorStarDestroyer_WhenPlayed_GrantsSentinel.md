# SOR_086 Gladiator Star Destroyer (5/6, Space) — When Played: Give a unit Sentinel for this
# phase. P1 plays it and chooses the friendly Battlefield Marine, which then has the Sentinel
# keyword (a phase-scoped TurnEffect grant). Uses the new HASKEYWORD/NOTKEYWORD assertions.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_086
WithP1GroundArena: SEC_080:1:0    # Battlefield Marine — idx 0, the Sentinel recipient

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
