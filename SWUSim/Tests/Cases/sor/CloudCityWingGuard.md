# AttachedUnitLosesAbilities
#// SHD_072 (upgrade: "Attach to a non-leader unit. Attached unit loses its current abilities and can't
#// gain abilities.") A debuff played onto the ENEMY SOR_063 Cloud City Wing Guard (Sentinel): the host
#// carries the upgrade (UPGRADECOUNT:1) but no longer counts as having Sentinel.
#// COVERAGE (this file is SOR_063 Cloud City Wing Guard's — the printed Sentinel keyword — plus the
#//           SHD_072 Imprisoned interaction that blanks it):
#//           offer=Sentinel_NarrowsTheOFFEREDAttackPool_NotJustItsSize (the exact legal SET, read off a
#//           real pending decision — SOR_012 IG-88's "attack with a unit" routes the target pick through
#//           the decision queue — with two Sentinels in and a non-Sentinel bystander and theirBase-0
#//           out), backed by Sentinel_IsTheOnlyLegalGroundTarget +
#//           TwoSentinels_BothStayInThePool_TheRestStayOut (the same pool measured by SIZE with
#//           ATTACKTARGETS, from both seats' attackers; the two-Sentinel fixture keeps it from
#//           collapsing to a single auto-target) ·
#//           boundary=TwoSentinels_BothStayInThePool_TheRestStayOut (1 Sentinel → 1 target / 2 →
#//           2) + Sentinel_DoesNotReachTheOtherArena (ground Sentinel, space pool unchanged at 2) ·
#//           decline=N/A (a printed keyword, not an ability — nothing to take or decline) ·
#//           control=N/A (Sentinel is a restriction on ATTACKERS reading the defending side's arena,
#//           so it is already evaluated from whoever controls the unit; both directions are asserted
#//           in the same section — ATTACKTARGETS is checked for P1's attacker AND P2's) ·
#//           reqboundary=N/A (a static, continuously-recomputed keyword: there is no written state to
#//           carry across a request, and Imprisoned's blanking round-trip is covered on the upgrade's
#//           own file rather than duplicated on the host's)

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_063:1:0
WithP1Hand: SHD_072

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# Sentinel_IsTheOnlyLegalGroundTarget
#// SOR_063 Cloud City Wing Guard (Vigilance, cost 3, 2/4, Fringe/Trooper) — "Sentinel (Units in this
#// arena can't attack your non-Sentinel units or your base.)" OFFER axis: P1 fields the Sentinel at
#// idx 0 and a NON-Sentinel SOR_095 at idx 1, plus an undamaged base. The enemy ground unit's legal
#// attack-target pool is therefore exactly ONE body — the Sentinel — with both the bystander and the
#// base excluded. P1's own attacker is unaffected: it still sees the enemy unit and the enemy base.

## GIVEN
CommonSetup: bbk/bbk/{}
SkipPreGame: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
ATTACKTARGETS:2:G:0:1
ATTACKTARGETS:1:G:0:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel

---

# TwoSentinels_BothStayInThePool_TheRestStayOut
#// SOR_063 Cloud City Wing Guard — the offer pair that makes the previous section discriminating. With
#// TWO Sentinels on P1's board the enemy attacker has a genuine choice of exactly 2 targets (not an
#// auto-resolve down to one), and the non-Sentinel bystander and the base are STILL excluded. The
#// enemy attacks the Sentinel at idx 1 to prove the second one really is reachable; the bystander at
#// idx 2 and P1's base end untouched.

## GIVEN
CommonSetup: bbk/bbk/{}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:1

## EXPECT
ATTACKTARGETS:2:G:0:2
P1BASEDMG:0
P1GROUNDARENAUNIT:2:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Sentinel_DoesNotReachTheOtherArena
#// SOR_063 Cloud City Wing Guard — "Units in THIS ARENA". The scope exclusion: a GROUND Sentinel does
#// nothing about the space arena, so an enemy space unit still sees both P1's space unit AND P1's base
#// (2 targets) and can hit the base directly. Its attack lands on the base for 2.

## GIVEN
CommonSetup: bbk/bbk/{}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_063:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE

## EXPECT
ATTACKTARGETS:2:S:0:2
P1BASEDMG:2
P1SPACEARENAUNIT:0:DAMAGE:0

---

# Imprisoned_BlanksSentinel_BaseBecomesAttackable
#// SHD_072 Imprisoned on SOR_063 Cloud City Wing Guard — the consequence half of
#// AttachedUnitLosesAbilities. Losing the Sentinel KEYWORD has to change what is legal to attack, not
#// just what the keyword assertion reads: P1 plays Imprisoned onto the enemy Sentinel, and P1's ground
#// unit — which had exactly one legal target before — can now reach the enemy base and hits it for 3.
#// (Two non-leader units are on the board, so Imprisoned's host pick is a genuine choice and must
#// be answered rather than auto-resolving onto the enemy Sentinel.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_072
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:3
ATTACKTARGETS:1:G:0:2

---

# Sentinel_NarrowsTheOFFEREDAttackPool_NotJustItsSize
#// SOR_063 Cloud City Wing Guard — OFFER axis, upgraded from a count to the exact SET. ATTACKTARGETS
#// elsewhere in this file proves HOW MANY bodies an attacker may reach; it cannot prove WHICH, so a
#// Sentinel filter that kept the right number of wrong targets would read as correct. Here the attack
#// target is a real pending decision — SOR_012 IG-88's leader action ("[Exhaust]: Attack with a unit")
#// routes the pick through the decision queue, the same MZCHOOSE a player clicks — so the pool itself
#// can be read. It is left PENDING and asserted exactly. N+1 fixture: P2 fields TWO Sentinels (so the
#// pick cannot collapse to a single auto-target) plus a NON-Sentinel bystander at index 2 and an
#// undamaged base; the legal set must be precisely the two Sentinels, with both the bystander and
#// theirBase-0 absent.

## GIVEN
CommonSetup: rrk/bbk/{
  myLeader:SOR_012;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
ATTACKTARGETS:1:G:0:2
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:2:NOTKEYWORD:Sentinel
P2BASEDMG:0
