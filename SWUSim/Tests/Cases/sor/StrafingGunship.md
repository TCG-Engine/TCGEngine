# AttacksGround_DefenderDebuffed
#// SOR_212 Strafing Gunship (3/4 Space) — "This unit can attack units in the ground arena. While this
#// unit is attacking a ground unit, the defender gets -2/-0." The space Gunship attacks an enemy GROUND
#// unit (SEC_080 3/3): it deals 3 (defeating SEC_080), and SEC_080's counter is reduced from 3 to 1 by
#// the -2 power debuff, so the Gunship takes only 1 damage.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackSpaceArena:0:G0

## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:EXHAUSTED

---

# AttacksSpace_NoDebuff
#// SOR_212 Strafing Gunship — the -2/-0 applies only while attacking a GROUND unit. Attacking a SPACE
#// unit normally, the defender (SOR_237 2/3) deals its full 2 counter-damage, so the Gunship takes 2.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_212:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:2

---

# AttackOffer_GroundArenaJoinsThePoolForThisUnitOnly
#// SOR_212 Strafing Gunship — the OFFER for clause one, "This unit can attack units in the ground
#// arena." The two existing sections both answer an attack that was already declared, which proves the
#// branch and never the POOL: a permission that also leaked onto every other space unit, or that
#// silently worked in reverse, passes them both.
#// COVERAGE: offer=this section (attack pool read straight off the target enumerator, with both
#//           load-bearing negatives on the same board) · decline=N/A (a static attack permission and a
#//           static while-attacking debuff — neither clause is a "you may" and neither queues a
#//           decision) · control=N/A (both clauses are keyed off the unit's own CardID and read at
#//           attack-declaration time from the arena object, so a control change carries them intact;
#//           nothing is stored per-seat) · boundary=AttacksGround_DefenderDebuffed (a 3-power defender
#//           counters for 1) vs GroundDefenderKeepsItsHP_DebuffClampsPowerAtZero (a 1-power defender
#//           counters for 0, not -1) · reqboundary=N/A (no interactive decision is ever queued by
#//           either clause)
#//
#// One board, three readings:
#//   • The Gunship (P1 space index 0) sees 4 targets: the enemy space unit, BOTH enemy ground units,
#//     and the enemy base. The grant widens the pool rather than replacing it.
#//   • The plain space unit beside it (P1 space index 1) sees only 2 — the enemy space unit and the
#//     base. Intended: the permission is printed on THIS unit, so it must not leak to the arena.
#//   • The enemy's ground unit (P2 ground index 0) also sees only 2 — P1's ground unit and P1's base.
#//     Intended: the permission is one-directional. "This unit can attack units in the ground arena"
#//     says nothing about ground units reaching into space, so the Gunship is not attackable from there.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_212:1:0
WithP1SpaceArena: SOR_237:1:0    # plain space unit — must NOT gain the ground permission
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN

## EXPECT
ATTACKTARGETS:1:S:0:4
ATTACKTARGETS:1:S:1:2
ATTACKTARGETS:2:G:0:2

---

# GroundDefenderKeepsItsHP_DebuffClampsPowerAtZero
#// SOR_212 Strafing Gunship — the two halves of "-2/-0" that the existing ground section cannot see.
#// AttacksGround_DefenderDebuffed uses a 3/3 defender: it dies, so its HP is never observed, and its
#// counter of 1 is the same number a "-2/-2" reading would produce.
#// Here the defender is a 1/4 Warrior Drone:
#//   • POWER: 1 - 2 is negative. The counter-damage must clamp at 0, so the Gunship ends the attack
#//     UNDAMAGED — it must not be healed or take "-1". Paired with the 3-power defender's counter of 1,
#//     this brackets the debuff at the point it bottoms out.
#//   • HP: the "-0" half. The Gunship's 3 damage lands on a 4-HP defender, so it SURVIVES at 3 damage.
#//     A "-2/-2" implementation would knock it to 2 HP and defeat it, which is what makes this board
#//     discriminating rather than a restatement.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: TWI_057:1:0    # 1/4 vanilla — power bottoms out, HP must be untouched

## WHEN
- P1>AttackSpaceArena:0:G0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TWI_057
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:HP:4
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:EXHAUSTED
