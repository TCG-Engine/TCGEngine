# AsUpgrade_Decline
#// JTL_148 Frisk — the "you may defeat an upgrade" is optional. P1 plays Frisk as a Pilot onto SOR_237
#// but DECLINES the host pick (AnswerDecision:-), so SOR_069 stays attached.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_148
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:0

---

# AsUpgrade_DefeatCheapUpgrade
#// JTL_148 Frisk — Piloting + "When played as an upgrade: You may defeat an upgrade that costs 2 or
#// less." Played as a Pilot onto the friendly SOR_237, P1 defeats SOR_069 (cost 1) on the enemy SOR_046.
#// SEC_080 carries SOR_054 (cost 3) which is NOT offered (proves the cost<=2 filter), so the host pick
#// has only SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_148
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_069
WithP2GroundArenaUpgrade: 1:SOR_054

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2DISCARDCOUNT:1
