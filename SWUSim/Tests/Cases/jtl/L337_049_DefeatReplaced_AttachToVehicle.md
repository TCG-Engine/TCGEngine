# JTL_049 L3-37 — If this unit would be defeated, you may instead attach her as an upgrade to a friendly
# Vehicle without a Pilot. P2 plays SOR_077 Takedown to defeat L3-37 (3/3, ≤5 HP). Instead of being
# defeated, L3-37's controller (P1) chooses to attach her as a pilot upgrade on the friendly Vehicle
# SEC_214 — so she leaves the unit slot, is NOT discarded, and SEC_214 gains her as an upgrade.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 6
WithP2Hand: SOR_077
WithP1GroundArena: JTL_049:1:0
WithP1GroundArena: SEC_214:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_049
P1DISCARDCOUNT:0
