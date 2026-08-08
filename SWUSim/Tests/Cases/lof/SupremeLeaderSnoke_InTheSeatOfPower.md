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

---

# Leader_TieSelection
#// LOF_006 Supreme Leader Snoke (front) — Action [1 resource, Exhaust]: give an Experience token to the unit
#// with the MOST power among friendly Villainy units; on a tie the player chooses. Count Dooku (SOR_038,
#// Villainy 5) and Darth Maul (TWI_135, Villainy 5) tie for the most; Plo Koon (LOF_050, Heroism 6) is
#// higher-power but NOT Villainy, so it is not eligible. Only the two Villainy 5s are selectable. Intended: #// "should make the player choose between Villainy cards with most power".

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
WithP1GroundArena: TWI_135:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# Leader_NoVillainy_UseAnyway
#// LOF_006 Supreme Leader Snoke (front) — with no friendly Villainy unit the ability has no legal target, but
#// it may still be used ("use it anyway"): the cost (1 resource + exhaust) is paid and no Experience is
#// given. P1's only unit is a non-Villainy Wampa (SOR_164). Intended: "should not give any experience if there
#// isn't any Villainy unit".

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:LOF_006;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_164:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Deployed_TieSelection
#// LOF_006 deployed On-Attack tie-break: two Villainy units tied at max power (Count Dooku SOR_038=5, Darth
#// Maul TWI_135=5, both above deployed Snoke's 4) → the deployed side PROMPTS the player to choose (mirrors
#// the front's Leader_TieSelection). Snoke deploys at index 2 (behind the two seated units) and attacks;
#// pick SOR_038 → it gets the Experience (+1/+1), TWI_135 gets none.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:LOF_006;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 7
WithP1GroundArena: SOR_038:1:0
WithP1GroundArena: TWI_135:1:0
## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:2:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_038
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:TWI_135
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
