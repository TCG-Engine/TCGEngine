# DefeatOnCombatDamage
#// JTL_120 Dorsal Turret — Attached Vehicle gains "When this unit deals combat damage to a unit while
#// attacking: defeat that unit." SOR_237 (with the turret) hits SOR_044 in combat; SOR_044 survives the
#// damage but is then defeated by the turret.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_120
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:1

---

# Defending_DoesNotDefeat
#// JTL_120 Dorsal Turret — the granted defeat is "while ATTACKING" only. When the turret-equipped SOR_237
#// is the DEFENDER (P2's SOR_044 attacks it), it deals its 2 counter damage but the turret does NOT defeat
#// the attacker; SOR_044 (2/3) survives with 2 damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_120
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P2>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_044
P2SPACEARENAUNIT:0:DAMAGE:2

---

# AttachRestriction_VehiclesOnly
#// JTL_120 Dorsal Turret "Attach to a Vehicle unit." No "friendly" qualifier → ANY Vehicle (friendly or
#// enemy) is a legal host; only non-Vehicles are excluded. P1 has SOR_095 (Trooper, non-Vehicle, ground-0),
#// SOR_244 (Snowspeeder, Vehicle, ground-1) and SOR_237 (X-Wing, Vehicle, space-0); the enemy SOR_044
#// (Vehicle, their space-0) is ALSO offered. Selectable = the two friendly Vehicles + the enemy Vehicle;
#// the friendly Trooper is excluded.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5;myhandCardIds:JTL_120}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_244:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&mySpaceArena-0&theirSpaceArena-0

---

# ShieldAbsorbs_NoCombatDamage_NoDefeat
#// JTL_120 Dorsal Turret defeats only on COMBAT damage DEALT. The turret-equipped SOR_237 (2/3) attacks
#// SOR_044 (2/3) which carries a Shield token (SOR_T02). The shield absorbs the whole 2-damage hit, so no
#// combat damage reaches SOR_044 → the turret's "deal combat damage → defeat" never triggers. SOR_044
#// survives with 0 damage and 0 shields; SOR_237 takes SOR_044's 2 counter-damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_120
WithP2SpaceArena: SOR_044:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_044
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:SHIELDCOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:2

---

# DefeatsDeployedLeaderUnit_ReturnsToLeaderForm
#// JTL_120 Dorsal Turret defeats a DEPLOYED LEADER-unit. P1's leader (SOR_010 Darth Vader, 5/8) is deployed
#// as a ground leader-unit; P2's SOR_244 Snowspeeder (3/6) carries the turret and attacks Vader. The turret
#// deals combat damage → defeats the leader-unit → it returns to leader form (NOT defeated to discard).
#// P1's ground arena empties and P1LEADER reads NOTDEPLOYED. (Snowspeeder power 3 < Vader HP 8, so only the
#// turret's defeat removes him, not combat HP.)

## GIVEN
CommonSetup: rrk/grw/{myResources:5;myLeaderDeployed:true;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2GroundArena: SOR_244:1:0
WithP2GroundArenaUpgrade: 0:JTL_120

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:NOTDEPLOYED

---

# StrikeTrue_AbilityDamage_DoesNotDefeat
#// JTL_120 Dorsal Turret triggers only on COMBAT damage dealt WHILE ATTACKING. SOR_127 Strike True makes
#// the turret-equipped SOR_237 (power 2) deal 2 damage to SOR_044 (2/3) as an ABILITY (the unit is not
#// attacking, this is not combat damage), so the turret does NOT fire. SOR_044 survives with 2 damage;
#// SOR_237 is untouched.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5;myhandCardIds:SOR_127}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_120
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_044
P2SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:DAMAGE:0

---

# AbilityDamage_DoesNotTriggerDefeat
#// JTL_120 Dorsal Turret triggers only on COMBAT damage, not on the host's own ON-ATTACK ABILITY damage.
#// SOR_237 carries the turret plus SOR_121 Hardpoint Heavy Blaster (+2/+2; "On Attack: ... deal 2 damage to
#// a unit in the defender's arena"). SOR_237 attacks SOR_044, which holds a Shield token that fully absorbs
#// the combat hit (so NO combat damage is dealt → the turret stays silent), while Hardpoint's ABILITY deals
#// 2 non-combat damage to a SECOND enemy unit SOR_178 (2/3). The turret does NOT defeat SOR_178: it survives
#// with 2 damage, and shielded SOR_044 survives with 0 damage / 0 shields. Nobody is defeated.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_120
WithP1SpaceArenaUpgrade: 0:SOR_121
WithP2SpaceArena: SOR_044:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-1

## EXPECT
P2SPACEARENACOUNT:2
P2SPACEARENAUNIT:0:CARDID:SOR_044
P2SPACEARENAUNIT:0:SHIELDCOUNT:0
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:1:CARDID:SOR_178
P2SPACEARENAUNIT:1:DAMAGE:2

---

# TargetDiesToCombatHP_BystanderSurvives
#// JTL_120 Dorsal Turret's deferred "Defeat that unit" captures the defender's UniqueID and re-locates it at
#// resolution. When the combat-damage target is ALSO defeated by the combat HP damage simultaneously, the
#// deferred defeat no-ops (its unit is already gone) instead of mis-hitting a bystander. SOR_237 (power 2)
#// attacks SOR_060 (2/1); the 2 combat damage defeats SOR_060 by HP. An untouched bystander SOR_178 (2/3)
#// shares the space arena and MUST survive — only the intended defender is gone.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_120
WithP2SpaceArena: SOR_060:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_178
P2SPACEARENAUNIT:0:DAMAGE:0
P2DISCARDUNIT:0:CARDID:SOR_060

---

# AttachToEnemyVehicle_Legal
#// JTL_120 Dorsal Turret — "Attach to a Vehicle unit" has NO "friendly" qualifier, so an ENEMY Vehicle is
#// a legal host. P1 plays Dorsal Turret from hand and attaches it to P2's Alliance
#// X-Wing (a Vehicle); the upgrade attaches to the enemy unit. (The granted "defeat that unit" combat
#// trigger is controller-agnostic, so an enemy-hosted turret is harmless but the attach itself is legal.)

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_120
WithP1Resources: 3
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_120
