# OnAttack_DamagedUnitBuff
#// JTL_238 Sith Trooper — On Attack: +1/+0 for each damaged unit the defending player controls. P2 has
#// two damaged units, so the Sith Trooper (power 3) attacks for 3+2=5 to the enemy base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_238:1:0
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5

---

# SimulateRequestBoundary_SecondTrooperStillCounts
#// JTL_238 Sith Trooper — the On Attack buff has no decision of its own, but each attack is its own
#// request in production. Two Sith Troopers attack the enemy base in the same phase with the boundary
#// between them: P2 controls two damaged units throughout, so BOTH attacks must be 3+2=5 (10 total).
#// If any of the attack/On-Attack bookkeeping lived only in memory the second attack would come out
#// unbuffed (8) or not at all.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_238:1:0
WithP1GroundArena: JTL_238:1:0
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:10

---

# TwinSuns_CountsOnlyTheDEFENDINGPlayersDamagedUnits
#// "On Attack: +1/+0 for this attack for each damaged unit THE DEFENDING PLAYER controls." The count
#// walked GetGroundArena(OtherPlayer($player)) — seat 2 for seat 1, whoever the attack was aimed at.
#//
#// Seat 4 (the defender) fields TWO damaged units; seat 2 fields THREE. Correct bonus is +2, so seat 4's
#// base takes 3 power + 2 = 5. The legacy count gives +3 = 6. Seat 3 also fields damaged units, so a
#// naive "their<Arena>" style fan-out fix would produce yet a third number (+5 = 8) — all three readings
#// are distinguishable from the single assertion.

## GIVEN
CommonSetup: bbk/bbk/{theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: JTL_238:1:0
WithP2GroundArena: [SOR_046:1:2 SOR_046:1:2 SOR_046:1:2]
WithP3GroundArena: [SOR_046:1:2 SOR_046:1:2 SOR_046:1:2]
WithP4GroundArena: [SOR_046:1:2 SOR_046:1:2]

## WHEN
- P1>AttackGroundArena:0:P4B

## EXPECT
SEATCOUNT:4
P4BASEDMG:5
P2BASEDMG:0
