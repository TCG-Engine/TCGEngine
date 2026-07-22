# SentinelLoseSaboteur
#// JTL_077 In the Heat of Battle — Each unit gains Sentinel and loses Saboteur for this phase. The
#// Saboteur unit SHD_147 gains Sentinel and loses Saboteur.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_077
WithP1Resources: 6
WithP1GroundArena: SHD_147:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# AffectsBothSides
#// JTL_077 In the Heat of Battle — "EACH unit" spans both players. P1's Saboteur unit (SHD_147) gains
#// Sentinel and loses Saboteur; the enemy SOR_095 also gains Sentinel.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_077
WithP1Resources: 6
WithP1GroundArena: SHD_147:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_147
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
