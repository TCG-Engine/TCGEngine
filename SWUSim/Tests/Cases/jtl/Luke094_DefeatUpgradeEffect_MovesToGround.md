# JTL_094 Luke — the "if would be defeated" replacement also covers a "defeat an upgrade" EFFECT (host
# survives). P2 plays JTL_175 System Shock to defeat the upgrade on SEC_214 (Luke) and deal 1 to that
# unit. Instead of being defeated, Luke moves to the ground arena as an exhausted unit; SEC_214 stays in
# play with 1 damage and no upgrade.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 4
WithP2Hand: JTL_175
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_094

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:JTL_094
P1GROUNDARENAUNIT:1:EXHAUSTED
P1DISCARDCOUNT:0
