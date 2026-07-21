# GrantsRestore
#// JTL_045 Hera Syndulla (pilot) — Attached unit gains Restore 1. The host (SOR_237 + pilot, power 4)
#// attacks the base for 4 and Restore 1 heals P1's base from 3 to 2.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_045

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:4
P1BASEDMG:2

---

# AsUnit_Restore1
#// JTL_045 Hera Syndulla — as a UNIT she has Restore 1 herself. Hera (2/3) attacks the enemy base for 2 and
#// heals P1's base from 3 to 2.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_045:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1BASEDMG:2

---

# GrantStacksWithHostRestore
#// JTL_045 Hera Syndulla (pilot) — the granted Restore 1 STACKS with the attached host's OWN printed
#// Restore. Host SOR_044 Restored ARC-170 (2/3 Space, innate Restore 1) has Hera attached, so it heals
#// TWO on attack (its own 1 + Hera's 1). Hera the pilot also adds +2 power, so the host attacks the base
#// for 4, and Restore 2 heals P1's base from 3 down to 1.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0
WithP1SpaceArenaUpgrade: 0:JTL_045

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Restore
P2BASEDMG:4
P1BASEDMG:1

---

# GrantOnlyAttachedHost
#// JTL_045 Hera Syndulla (pilot) — the Restore 1 grant is LOCAL to the attached host only; it does NOT
#// leak to other friendly units. Hera is attached to X-Wing idx 0 (which does NOT attack). A SECOND
#// vanilla X-Wing (idx 1, no pilot) attacks the base for 2, and because it never gained Restore, P1's
#// base stays at 3 damage (no heal).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_045

## WHEN
- P1>AttackSpaceArena:1:BASE

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Restore
P1SPACEARENAUNIT:1:NOTKEYWORD:Restore
P2BASEDMG:2
P1BASEDMG:3

---

# ReRegisterAfterLeaveAndReEnter
#// JTL_045 Hera Syndulla (pilot) — the granted Restore keyword UNREGISTERS when Hera leaves the arena and
#// RE-REGISTERS when she is replayed onto the host. Hera starts attached to the X-Wing (host has Restore).
#// P1 plays SOR_199 Bamboozle on the X-Wing, which returns each upgrade (Hera) to P1's hand — the host now
#// has NO Restore. P1 then replays Hera from hand as a Pilot back onto the same X-Wing, and the Restore 1
#// grant is registered afresh (host HASKEYWORD:Restore again).

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_045
WithP1Hand: SOR_199

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Restore
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_045
