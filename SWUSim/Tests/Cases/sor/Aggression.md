# Modal_DefeatUpgradesCrossUnit
#// SOR_155 Aggression — "Defeat up to 2 upgrades" now spans DIFFERENT units (two chained "may defeat 1"
#// flows, each re-reading the board). SEC_080 holds SOR_120 and SOR_095 holds SOR_069; DefeatUpgrades
#// removes one upgrade from EACH (impossible with the old host-scoped single flow). The second mode is
#// Draw. Aggression,Aggression is fully off-aspect for SOR_009 → cost 8.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_155
WithP1Resources: 8
WithP1Deck: SOR_237
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 1:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DefeatUpgrades
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:Draw

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:3

---

# Modal_DrawAndDeal4
#// SOR_155 Aggression (event, cost 4) — Draw a card + Deal 4 to a unit. P1 draws (hand 0→1) and deals 4
#// to the only unit (LAW_124, a 4/7, survives at 4). Aggression is off-aspect for SOR_009 → cost 6.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_155
WithP1Resources: 8
WithP1Deck: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Draw
- P1>AnswerDecision:Deal4

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4
P1DISCARDCOUNT:1

---

# Modal_ReadyAndDeal4
#// SOR_155 Aggression — Ready a unit with ≤3 power + Deal 4 to a unit. SEC_080 (exhausted, 3 power) is
#// readied (only it qualifies, ≤3 power); then 4 damage is dealt to LAW_124 (4 power, so not a Ready
#// target). (The DefeatUpgrades mode is smoke-verified separately — its TempZone picker is covered by
#// SOR_251/SOR_170; the in-process regression harness can't drive a TempZone MZMULTICHOOSE nested in the
#// modal, though it resolves correctly through the live engine.)

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_155
WithP1Resources: 8
WithP1GroundArena: SEC_080:0:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ready
- P1>AnswerDecision:Deal4
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:4
P1DISCARDCOUNT:1
