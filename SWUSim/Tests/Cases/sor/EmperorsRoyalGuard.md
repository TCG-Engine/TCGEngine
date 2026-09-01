# NoPalpatine_NoHP
#// SOR_082 Emperor's Royal Guard (3/4) — P1's leader is NOT Palpatine, so the
#// +0/+1 does not apply. Reads its printed 3/4.
#// (Absence guard — passes pre-implementation; stays meaningful once the buff exists.)
#// COVERAGE: offer=N/A (both abilities are static — no choose pool ever exists; the Sentinel
#//           behavior pair below is the target-legality proof) · decline=N/A (nothing optional) ·
#//           boundary pair=NoOfficial_NoSentinel_EnemyMayAttackBase (absence) +
#//           OfficialInPlay_GuardGainsSentinel_BaseAttackForcedOntoGuard (presence) · reqboundary=
#//           OfficialInPlay_* (state crosses the pass→enemy-attack request boundary) ·
#//           control=PalpatineTakenAway_BuffsEndImmediately (green) +
#//           PalpatineTakenAway_HPRevertsAndGuardDies (RED). The Guard's OWN control is inert here
#//           (both clauses read his controller live and leave no per-unit marker to strand), but
#//           the ENABLER's is not: taking control of the Emperor Palpatine unit must end BOTH
#//           clauses for the seat that lost him, and when the +0/+1 goes away the CR state check
#//           has to defeat a Guard whose damage now meets his reverted HP. The revert happens; the
#//           state check does not · negatives, one per clause: EnemyOfficial_NoSentinel (clause 1)
#//           and EnemyPalpatineUnit_NoHP (clause 2); combined-clause case=
#//           PalpatineUnit_SatisfiesBothClausesAtOnce (SOR_135 carries the OFFICIAL trait, so one
#//           card fires both)

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

---

# EnemyOfficial_NoSentinel
#// SOR_082 Emperor's Royal Guard — Intended: "While YOU control an OFFICIAL unit". The negative for
#// the FIRST clause, which no section had (the existing EnemyPalpatineUnit_NoHP is the negative for
#// the second): an OFFICIAL unit on the OPPONENT's board (General Tagge, seeded so his When Played
#// never fires) grants the Guard nothing. With no Sentinel, P2's Battlefield Marine walks past him
#// into my base for 3, and the Guard still reads its printed 3/4.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0
WithP2GroundArena: SOR_080:1:0    # ENEMY Official — idx 0
WithP2GroundArena: SOR_095:1:0    # the attacker — idx 1

## WHEN
- P1>Pass
- P2>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
P1BASEDMG:3

---

# PalpatineUnit_SatisfiesBothClausesAtOnce
#// SOR_082 Emperor's Royal Guard — Intended: the Emperor Palpatine UNIT (SOR_135) carries the
#// OFFICIAL trait, so one card satisfies BOTH clauses simultaneously — Sentinel from the first and
#// +0/+1 from the second. The existing PalpatineUnit_GetsHP measures only the stat half on a static
#// board; here the combined state is exercised: the Guard reads 3/5 WITH Sentinel, P2's Marine can
#// no longer reach my base (its declared base attack auto-resolves onto the Guard, the only legal
#// target), the Guard survives the 3 on 5 HP and his 3 power defeats the Marine. Palpatine himself
#// gains no Sentinel — only the Guard has the ability.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0
WithP1GroundArena: SOR_135:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
P2GROUNDARENACOUNT:0

---

# PalpatineTakenAway_BuffsEndImmediately
#// SOR_082 Emperor's Royal Guard — Intended: both clauses read "While YOU CONTROL …" live, so they
#// end the instant control of the enabling unit does. P2 plays No Glory, Only Results (JTL_043) on
#// P1's Emperor Palpatine unit: taking control of him strips BOTH conditions from P1's side at
#// once, so the Guard immediately loses Sentinel and his HP drops from 5 back to his printed 4.
#// The Guard carries 3 damage, which is survivable on either HP, so this section isolates the
#// REVERT itself. It is the N-1 half of the pair with PalpatineTakenAway_HPRevertsAndGuardDies
#// (4 damage — the same revert, now lethal) and the passing control that proves that fixture.

## GIVEN
CommonSetup: grw/bbk/{theirResources:5}
SkipPreGame: true
WithP1GroundArena: SOR_082:1:3
WithP1GroundArena: SOR_135:1:0
WithP2Hand: JTL_043

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_082
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_135

---

# PalpatineTakenAway_HPRevertsAndGuardDies
#// SOR_082 Emperor's Royal Guard — Intended, per the CR state check ("a unit with damage equal to
#// or greater than its remaining HP is defeated", applied continuously): identical to
#// PalpatineTakenAway_BuffsEndImmediately with ONE more damage on the Guard. He sits on 4 damage,
#// survivable only because Palpatine's +0/+1 makes him 3/5. P2 steals Palpatine with No Glory, Only
#// Results (JTL_043), the +0/+1 ends and the Guard's HP reverts to its printed 4 — his 4 damage is
#// now lethal, so he must be defeated on the spot. P1's board should empty and P1's discard should
#// hold BOTH his own units (Palpatine returns to his owner's discard when the event defeats him),
#// while the event itself goes to P2's discard.
#// The revert half is already proven green by PalpatineTakenAway_BuffsEndImmediately (HP 4,
#// Sentinel gone), so what this section isolates is the state check that must follow it.

## GIVEN
CommonSetup: grw/bbk/{theirResources:5}
SkipPreGame: true
WithP1GroundArena: SOR_082:1:4
WithP1GroundArena: SOR_135:1:0
WithP2Hand: JTL_043

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:JTL_043
