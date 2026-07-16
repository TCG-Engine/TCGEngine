# WhenPlayed_ExpPerGround
#// SEC_089 PreMor Personnel Carrier (Ground, 6/6) — Overwhelm + When Played: give itself an Experience
#//   token for each ground unit you control (including itself). With 1 other ground unit → 2 ground → 2 Exp → 8/8.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_089

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_089
P1SPACEARENAUNIT:0:POWER:7
P1NODECISION
