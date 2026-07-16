# AttachCost4OrLess
#// TS26_079 Underestimated (Upgrade +2/+1, cost 1) — Attach to a unit that costs 4 or less. Only SEC_080
#// (cost 3) is a valid host; LAW_124 (cost 8) is excluded, so the upgrade auto-attaches to SEC_080 (3 → 5
#// power).
## GIVEN
CommonSetup: yyk/rrk/{myResources:1;handCardIds:TS26_079}
WithP1GroundArena: [SEC_080:1:0 LAW_124:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
