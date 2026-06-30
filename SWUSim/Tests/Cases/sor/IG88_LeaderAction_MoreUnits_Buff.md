# SOR_012 IG-88 — Leader Action [Exhaust]: Attack with a unit. If you control more units than
# the defending player, the attacker gets +1/+0 for this attack. P1 controls 1 unit, P2 controls 0
# → bonus applies. The 3-power unit attacks the base for 3+1=4. The +1 is one-shot (POWER stays 3).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_012;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:POWER:3
P1LEADER:EXHAUSTED
