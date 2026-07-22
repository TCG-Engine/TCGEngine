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
