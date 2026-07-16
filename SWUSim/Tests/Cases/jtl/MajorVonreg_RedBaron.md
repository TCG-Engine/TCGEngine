# OnAttack_BuffsAnotherUnitInArena
#// JTL_011 Major Vonreg deployed as a PILOT — the host gains "On Attack: You may give another unit in
#// this arena +1/+0 for this phase." Host (SOR_225 @0) attacks the base; buffs the other friendly
#// space unit JTL_069 (4/7 -> 5/7).

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_011;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: [SOR_225:1:0 JTL_069:1:0]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1SPACEARENAUNIT:1:POWER:5

---

# PlayVehicle_BuffsAnother
#// JTL_011 Major Vonreg (leader) — Action [Exhaust]: Play a Vehicle unit from your hand (paying its
#// cost). If you do, give another unit +1/+0 for this phase. P1 plays SOR_225 (TIE/ln, Villainy Vehicle,
#// cost 1) and then buffs the OTHER unit SEC_080 (3/3 → 4/3); the just-played TIE is excluded.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_225
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED

---

# PlayVehicle_NoOtherUnit_NoBuff
#// JTL_011 Major Vonreg (leader) — the +1/+0 is given to ANOTHER unit. With no other unit in play after
#// the Vehicle enters, the buff has no target and fizzles: the played TIE keeps its printed 2 power.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_225
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:POWER:2
P1LEADER:EXHAUSTED
