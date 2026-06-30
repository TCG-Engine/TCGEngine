# JTL_007 Admiral Holdo (leader) — the +2/+2 lasts only "for this phase". After P1 buffs JTL_099 and
# both players pass (action phase ends → regroup runs the centralized turn-effect expiry), the buff is
# gone and JTL_099 is back to its printed 2/1.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: JTL_099:1:0
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_099
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:1
