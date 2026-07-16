# PlayedAsPilot_GrantsSentinelToHost
#// JTL_058 Academy Graduate — Piloting [2 Vigilance] + "Attached unit gains Sentinel." Played as a Pilot

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: JTL_058
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_058
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# PlayedAsUnit_HasSentinel
#// JTL_058 Academy Graduate — played as a normal Unit (no friendly Vehicle to pilot), it carries its own

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: JTL_058

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_058
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel