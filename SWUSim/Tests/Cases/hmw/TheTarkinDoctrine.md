# WhenPlayed_WithTarkin_GivesEnemyMinus3_NoSelfExhaust
#// HMW_206 The Tarkin Doctrine (Fortify, cost 1) — "When Played: If you control Grand Moff Tarkin, give an
#// enemy unit -3/-0 for this phase." P1's leader is HMW_004 Grand Moff Tarkin. The enemy SEC_080 (3/3)
#// drops to 0/3. It is placed READY and must STAY ready — proving the base-grant ("play a Fortification
#// upgrade → exhaust an enemy") does NOT self-trigger when The Tarkin Doctrine itself is played (its trait
#// is Law, not Fortification).

## GIVEN
CommonSetup: yyk/rrk/{myLeader:HMW_004;myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_206
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:3
P2GROUNDARENAUNIT:0:READY

---

# WhenPlayed_WithoutTarkin_NoDebuff
#// The gate: without Grand Moff Tarkin, the -3/-0 does not happen (the leader here is Thrawn, yk).

## GIVEN
CommonSetup: yyk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_206
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:3

---

# GrantedBaseAbility_PlayingAFortificationUpgradeExhaustsAnEnemyUnit
#// "Attached base gains: 'When you play a Fortification upgrade: Exhaust an enemy unit.'" With The Tarkin
#// Doctrine already on the base, playing HMW_095 Carbonite Chamber (a Fortification-trait upgrade) exhausts
#// the lone enemy unit.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_206
WithP1Hand: HMW_095
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# GrantedBaseAbility_NotActiveWithoutTheTarkinDoctrine
#// Control: the same Fortification-upgrade play with NO Tarkin Doctrine on the base does not exhaust anyone
#// (the grant is only present while HMW_206 is attached).

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_095
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:READY
