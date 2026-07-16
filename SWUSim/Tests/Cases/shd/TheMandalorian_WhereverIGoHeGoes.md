# WhenPlayed_HealAllAnd2Shields
#// SHD_049 The Mandalorian (6-cost 5/6 ground) — Sentinel + "When Played: You may heal all damage from a
#// unit that costs 2 or less and give 2 Shield tokens to it." The friendly SHD_095 (cost 1, 2 damage) is
#// fully healed and gains 2 Shields.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_049
WithP1GroundArena: SHD_095:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:2
