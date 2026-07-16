# WhenPlayed_CaptureLesserHP
#// SEC_256 Moral Authority (Upgrade, Heroism, cost 3) — Attach to a friendly unique unit. When Played:
#//   attached unit captures an enemy non-leader unit with less remaining HP than it. Host SEC_065 (7 HP)
#//   captures SOR_095 (3 HP < 7).

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_065:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_256

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_065
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION
