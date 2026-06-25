# LAW_015 Jabba (deployed leader unit) — Action: Play an Underworld unit from your hand. With NO
# Credit defeated while paying (the player has no Credit tokens), the played unit does NOT gain Ambush,
# so it just enters play and makes no entry attack.
# Jabba (ground idx 0) plays SOR_247 (cost 2, vanilla Underworld) at full cost. P2's SOR_247 is untouched.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LAW_015:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_247
WithP2GroundArena: SOR_247:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_247
P1GROUNDARENAUNIT:1:NOTKEYWORD:Ambush
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:0
