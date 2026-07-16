# WhenPlayed_DealsOneToEachOther
#// IBH_072 Avenger (Space, 8/6, Vigilance/Villainy, cost 8) — When Played: deal 1 damage to each OTHER
#//   unit (including friendly). A friendly ground, an enemy ground, and an enemy space unit each take 1;
#//   Avenger itself is unaffected.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IBH_072
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:CARDID:IBH_072
P1SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION
