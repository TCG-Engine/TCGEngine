# Attach_VehicleNeverOffered
#// SOR_137 Fallen Lightsaber — Upgrade, cost 3, [Aggression,Villainy], +3/+3, Item/Weapon/Lightsaber.
#// "Attach to a non-Vehicle unit. / If attached unit is a Force unit, it gains: 'On Attack: Deal 1
#//  damage to each ground unit the defending player controls.'"
#// COVERAGE: offer=Attach_HostOffer_BothSidesNonVehicle (host pool left PENDING) +
#//           Attach_VehicleNeverOffered (single legal host → auto-attach, Vehicle untouched) ·
#//           decline=N/A (no "you may" on either clause; the attach pick is mandatory once played) ·
#//           boundary=ForceHost_AttackUnit_AOEHitsDefenderAndBystander (1-HP bystander dies to the AOE)
#//           vs EnemySaber_AOEFollowsHostController (2-HP unit survives at 1) ·
#//           control=EnemySaber_AOEFollowsHostController (enemy-hosted saber: the granted On Attack
#//           belongs to the HOST's controller and hits THAT attack's defending player) ·
#//           reqboundary=SaberDefeated_GrantGone (boundary between the Confiscate and the attack)
#// P1's Snowspeeder (Vehicle) is not a legal host, so the Marine is the only candidate and the attach
#// auto-resolves onto it — the untouched Vehicle is the proof it was never offered.

## GIVEN
CommonSetup: rrk/grw/{myResources:3;myhandCardIds:SOR_137}
P1OnlyActions: true
WithP1GroundArena: [SOR_244:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_137
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION
P1RESAVAILABLE:0

---

# Attach_HostOffer_BothSidesNonVehicle
#// "Attach to a non-Vehicle unit" has no "friendly" qualifier — the host pool is every non-Vehicle
#// unit on BOTH sides, and both sides' Vehicles are excluded. Left pending to assert the offer.

## GIVEN
CommonSetup: rrk/grw/{myResources:3;myhandCardIds:SOR_137}
P1OnlyActions: true
WithP1GroundArena: [SOR_061:1:0 SOR_244:1:0]
WithP2GroundArena: [SOR_095:1:0 SOR_232:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# ForceHost_OnAttack_AOEHitsEnemyGroundOnly
#// Guardian of the Whills (Force, 2/2 → 5/5 with the saber) attacks the base: the granted On Attack
#// deals 1 to EACH ground unit the defending player controls — and nothing else. Enemy space unit,
#// P1's own units, and P1's base are all untouched; the base takes the full 5.

## GIVEN
CommonSetup: rrk/grw/{}
P1OnlyActions: true
WithP1GroundArena: [SOR_061:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:SOR_137
WithP2GroundArena: [SOR_244:1:0 SOR_164:1:0]
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P1BASEDMG:0

---

# NonForceHost_NoAOE
#// The grant is conditional: "IF attached unit is a Force unit". On a non-Force host (Battlefield
#// Marine, 3/3 → 6/6) the saber is stats only — the attack deals 6 to the base and no ground unit
#// takes AOE damage.

## GIVEN
CommonSetup: rrk/grw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_137
WithP2GroundArena: [SOR_244:1:0 SOR_164:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# SaberDefeated_GrantGone
#// The granted On Attack lives on the ATTACHMENT: once the saber is defeated (Confiscate, auto-target —
#// it is the only upgrade in play), the Force host attacks as a plain 2/2 and no AOE fires.

## GIVEN
CommonSetup: rrk/grw/{myResources:1;myhandCardIds:SOR_251}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1GroundArenaUpgrade: 0:SOR_137
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# ForceHost_AttackUnit_AOEHitsDefenderAndBystander
#// Attacking a UNIT: the AOE resolves before combat damage and hits every defender-side ground unit —
#// including the defender itself and a 1-HP bystander (Death Star Stormtrooper dies to the AOE alone).
#// Wampa (4/5) takes 1 AOE + 5 combat and dies; its 4 counter leaves the 5/5 Guardian at 4 damage.
#// No Overwhelm anywhere → base untouched.

## GIVEN
CommonSetup: rrk/grw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1GroundArenaUpgrade: 0:SOR_137
WithP2GroundArena: [SOR_164:1:0 SOR_128:1:0]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:0

---

# EnemySaber_AOEFollowsHostController
#// The saber on P2's Force unit works for P2: when P2's Guardian (5/5) attacks P1's base, the granted
#// On Attack hits each ground unit the DEFENDING player (P1) controls — the 2-HP SpecForce Soldier
#// survives at 1 damage. P2's own board and base are untouched.

## GIVEN
CommonSetup: rrk/grw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_137
WithP1GroundArena: [SOR_095:1:0 SOR_140:1:0]

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:5
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
