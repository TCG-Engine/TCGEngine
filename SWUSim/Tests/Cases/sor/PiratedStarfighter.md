# WhenPlayed_ReturnsFriendly
#// SOR_209 Pirated Starfighter (2/4, Space, Raid 1) — When Played: Return a friendly non-leader
#// unit to its owner's hand (mandatory). P1 has one other friendly non-leader unit (Battlefield
#// Marine) which is returned to hand. (Raid 1 is an auto keyword; this tests only the return.)

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_209
WithP1GroundArena: SEC_080:1:0    # friendly non-leader unit — returned to hand

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1HANDCOUNT:1
