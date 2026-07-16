# FrozenInCarbonite_CantReadyThroughRegroup
#// SHD_193 Frozen in Carbonite — "Attached unit can't ready." An exhausted host wearing SHD_193 does NOT
#// ready at the regroup ready step, while an identical exhausted unit without the upgrade does.

## GIVEN
CommonSetup: yyk/yyk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:SHD_193
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY

---

# FrozenInCarbonite_WhenPlayed_Exhaust
#// SHD_193 Frozen in Carbonite — "When Played: Exhaust attached unit." Played onto a ready SOR_046 → the
#// host becomes exhausted.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_193

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
