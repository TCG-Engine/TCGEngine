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

---

# AttackReadyUnit_StillCantAttackBases
#// JTL_123 Dogfight — "Attack with a unit, EVEN IF it's exhausted" permits an exhausted attacker but does
#// not require one: a READY unit is an equally legal choice, and the "can't attack bases for this attack"
#// rider still applies to it. The ready SOR_063 (power 2) is chosen and must attack the enemy UNIT
#// SOR_095 (its only legal target) for 2; the enemy base stays clean and the attacker ends up exhausted
#// from attacking. Companion to AttackExhausted_NoBases, which covers the exhausted half.

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
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:0
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# SimulateRequestBoundary_CantAttackBasesSurvivesRoundTrip
#// JTL_123 Dogfight — with TWO enemy units on the board the attack-target pick is a genuine MZCHOOSE
#// (pool theirGroundArena-0 & theirGroundArena-1), which ends the request in production: the chosen
#// attacker, the in-progress attack, and the "can't attack bases for this attack" rider must all survive a
#// fresh process. The already-exhausted SOR_063 (power 2) attacks SOR_046 for 2; the base stays clean.

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
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# Offer_AttackerPoolIsFriendlyUnitsIncludingExhausted
#// JTL_123 Dogfight — "Attack with a unit, EVEN IF it's exhausted." The attacker pool must be P1's own
#// units regardless of readiness or arena, and must never contain an enemy unit. Seeded so the pool
#// discriminates: the exhausted friendly SOR_063 and the ready friendly SOR_095 (ground) plus the
#// friendly SOR_237 (space) all belong; the three enemy units (two ground, one space) must all be
#// excluded. The decision is left PENDING so the offer itself is asserted.

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
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0
