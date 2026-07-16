# ControllerAssignsIndirect
#// JTL_171 Targeting Computer — "Attached unit gains: You assign all indirect damage dealt by this unit."
#// JTL_237 TIE Bomber (On Attack: 3 indirect to the defending player) carries the Targeting Computer.
#// Normally the damaged player (P2) would assign; with JTL_171, P1 (the controller) assigns instead — so
#// P1 answers the split in the "their" frame and dumps all 3 onto P2's SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: JTL_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_171
WithP2SpaceArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0:3

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
