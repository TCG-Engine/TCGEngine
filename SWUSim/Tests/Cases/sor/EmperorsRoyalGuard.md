# NoPalpatine_NoHP
#// SOR_082 Emperor's Royal Guard (3/4) — P1's leader is NOT Palpatine, so the
#// +0/+1 does not apply. Reads its printed 3/4.
#// (Absence guard — passes pre-implementation; stays meaningful once the buff exists.)
#// COVERAGE: offer=N/A (both abilities are static — no choose pool ever exists; the Sentinel
#//           behavior pair below is the target-legality proof) · decline=N/A (nothing optional) ·
#//           boundary pair=NoOfficial_NoSentinel_EnemyMayAttackBase (absence) +
#//           OfficialInPlay_GuardGainsSentinel_BaseAttackForcedOntoGuard (presence) · reqboundary=
#//           OfficialInPlay_* (state crosses the pass→enemy-attack request boundary) · control=N/A
#//           (both clauses read the GUARD's controller live; no lingering per-unit marker — no
#//           upstream control scenario exists)

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4

---

# PalpatineLeader_GetsHP
#// SOR_082 Emperor's Royal Guard (3/4) — "While you control Emperor Palpatine
#// (as a leader or unit), this unit gets +0/+1." P1's leader is Palpatine (SOR_006),
#// so the Guard reads 3/5. (The separate "Official → Sentinel" clause is already
#// implemented; no Official unit here, so only the +0/+1 is active.)

## GIVEN
CommonSetup: ggk/grw/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5

---

# NoOfficial_NoSentinel_EnemyMayAttackBase
#// Intended: with NO friendly Official unit, the Guard has no Sentinel — the enemy Marine is free
#// to attack my base past it (base takes 3).

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1BASEDMG:3

---

# OfficialInPlay_GuardGainsSentinel_BaseAttackForcedOntoGuard
#// Intended: "While you control an OFFICIAL unit, this unit gains Sentinel." With General Tagge
#// (Imperial Official, seeded so his When Played never fires) beside the Guard, the enemy Marine
#// can no longer reach my base — its base attack is forced onto the Sentinel Guard instead: the
#// base takes 0, the Guard takes 3, and the Marine dies to the Guard's 3 power. Only the GUARD
#// gains Sentinel, not the Official himself.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0
WithP1GroundArena: SOR_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
P1BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0

---

# OfficialInPlay_EnemyAttacksGuard_Trades
#// Intended: the Sentinel Guard IS a legal target — the Marine (3/3) attacks it: the Guard takes
#// 3 and its 3 power defeats the Marine (to its owner's discard).

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0
WithP1GroundArena: SOR_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095

---

# PalpatineUnit_GetsHP
#// Candidate #4 fix guard: "you control Emperor Palpatine (as a leader or UNIT)" — the UNIT
#// printing SOR_135 satisfies the aura by TITLE (the old check matched leader CardID SOR_006
#// only). Leader here is NOT Palpatine; the Guard still reads 3/5.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0
WithP1GroundArena: SOR_135:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5

---

# PalpatineOtherLeaderPrinting_GetsHP
#// Candidate #4 fix guard, printing #2: ASH_015 Emperor Palpatine is a different LEADER printing —
#// the aura matches by title, not by the SOR_006 CardID.

## GIVEN
CommonSetup: ggk/grw/{
  myLeader:ASH_015
}
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5

---

# EnemyPalpatineUnit_NoHP
#// Negative: "while YOU control" — an ENEMY Emperor Palpatine unit does not buff P1's Guard.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0
WithP2GroundArena: SOR_135:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
