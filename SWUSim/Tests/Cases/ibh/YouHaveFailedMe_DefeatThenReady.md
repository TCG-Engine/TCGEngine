# IBH_095 You Have Failed Me (Event, cost 4, Aggression/Villainy) — Defeat a friendly unit. If you do,
#   ready a friendly unit with 5 or less power. P1 sacrifices a ready 3/3; the remaining exhausted 3/1
#   (power 3 ≤ 5) is readied (auto, only one candidate left).

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: IBH_095
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_128:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1GROUNDARENAUNIT:0:READY
P1NODECISION
