# Deployed_Passive_CreatureBuff
#// LOF_004 Kanan Jarrus (deployed, 3/6) — passive: while you control another Creature or Spectre
#// unit, this unit gets +2/+2. With a Creature (LOF_254) in play → Kanan is 5/8.

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:LOF_004:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_254:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:8

---

# Deployed_Passive_NoCreature_NoBuff
#// LOF_004 Kanan Jarrus (deployed) — passive fizzle: with no other Creature/Spectre unit, no buff
#// (stays 3/6).

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:LOF_004:1:1:1
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:6

---

# ShieldCreature
#// LOF_004 Kanan Jarrus — Action [1 resource, Exhaust]: Give a Shield token to a Creature or Spectre unit.
#// LOF_044 (a Creature) gets a Shield; the resource is spent.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:LOF_004;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: LOF_044:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1RESAVAILABLE:0
