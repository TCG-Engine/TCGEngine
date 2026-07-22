# AttackExhausted_NoBases
#// JTL_123 Dogfight — Attack with a unit even if it's exhausted; it can't attack bases this attack. The
#// already-exhausted SOR_063 (power 2) attacks the only legal target, the enemy unit SOR_095, for 2.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_123
WithP1Resources: 1
WithP1GroundArena: SOR_063:0:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NoEnemyUnitToAttack_PlayAnyway
#// JTL_123 Dogfight — the chosen unit "can't attack bases this attack", so it must attack an enemy UNIT.
#// With the only enemy unit in a different arena (P1's attacker is ground, the enemy is in space), there is
#// no legal unit target, so Dogfight does nothing and is played anyway (to the discard); the base is unhurt.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_123
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P2BASEDMG:0
P1DISCARDCOUNT:1
