# DeployedOnAttack
#// LOF_006 Supreme Leader Snoke (deployed, Villainy) — On Attack: give an Experience token to the highest-
#// power friendly Villainy unit (herself, the only one) → 5/6.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:LOF_006;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5

---

# ExpHighestVillainy
#// LOF_006 Supreme Leader Snoke — Action [1 resource, Exhaust]: Give an Experience token to the unit with the
#// most power among friendly Villainy units. SOR_038 (Villainy, power 5) is the only Villainy unit → +1/+1.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:LOF_006;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_038:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1RESAVAILABLE:0
