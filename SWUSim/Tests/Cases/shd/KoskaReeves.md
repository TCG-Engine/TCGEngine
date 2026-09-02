# Upgraded_OnAttack_DealsTwoToAGroundUnit
#// COVERAGE: offer=Offer_GroundUnitsOnly_SpansBOTHSides_ExcludesTheSpaceArena
#//           decline=Decline_NoDamageIsDealt · boundary=Upgraded_OnAttack_DealsTwoToAGroundUnit /
#//           NotUpgraded_NoPromptAtAll (0 vs 1 upgrades IS the threshold, written as a pair)
#//           control=N/A - STRUCTURAL: "this unit is upgraded" is self-referential and "a ground unit" is
#//           a board pool; no owner-scoped zone appears anywhere in the text.
#//           reqboundary=SimulateRequestBoundary_TheTargetPickSurvives
#//           modes=2P only - no player reference and no friendly/enemy wording.
#//
#// SHD_150 Koska Reeves - 4-cost 4/5 Ground, Heroism/Aggression.
#//   "On Attack: If this unit is upgraded, you may deal 2 damage to a ground unit."
#// This card had NO test file at all until 2026-09-01.
#//
#// THE POSITIVE. Koska wears SOR_120 Academy Training (+2/+2) so she is upgraded, attacks the base, and
#// her On Attack puts 2 on an enemy ground unit.

## GIVEN
CommonSetup: rrw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NotUpgraded_NoPromptAtAll
#// THE NEGATIVE that proves the "if this unit is upgraded" gate is load-bearing. Identical board with
#// the upgrade removed: she still attacks, but nothing is offered.
#// Without this the positive passes for a card whose On Attack is unconditional.
## GIVEN
CommonSetup: rrw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# ShieldTokenCountsAsAnUpgrade
#// VALUE-CLASS VARIANT, and the one most likely to be missed: "upgraded" is about SUBCARDS, not about
#// stat-bearing upgrades. A Shield token contributes no power or HP of its own, so it isolates the
#// CONDITION exactly - she is a plain 4/5 here and the ability still fires.
## GIVEN
CommonSetup: rrw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Decline_NoDamageIsDealt
#// THE DECLINE BRANCH. "You MAY deal 2 damage" - refusing must leave the target untouched while the
#// attack itself still lands.
#// A "may" target does not auto-resolve even with one legal target, so this is a real answer.
## GIVEN
CommonSetup: rrw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Offer_GroundUnitsOnly_SpansBOTHSides_ExcludesTheSpaceArena
#// THE OFFER CELL - answering a target proves the branch, never the pool.
#// "A GROUND unit" carries an arena word but NO controller word, so the pool spans both sides and
#// excludes space. The board separates all three populations at once: P1's own ground unit (legal -
#// pointing this at your own board is unusual but the text permits it), the enemy ground unit (legal),
#// and an enemy SPACE unit that must not appear. Koska herself is a ground unit and the text says
#// nothing about "another", so she is in the pool too.
#// A pool narrowed to enemies, or one that forgot the arena filter, satisfies every other section here.
## GIVEN
CommonSetup: rrw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# SimulateRequestBoundary_TheTargetPickSurvives
#// THE REQUEST-BOUNDARY CELL. The On Attack is armed during combat and its target is answered
#// afterwards - in production two separate requests, with a gamestate write and re-parse between them.
#// Anything the handler needs in order to know what it was doing must be serialized; held in memory it
#// is gone by the time the answer arrives, and the failure is silent (no damage, as though the ability
#// had never fired).
#// Same board and outcome as the base case, with the boundary inserted before the answer.
## GIVEN
CommonSetup: rrw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_150:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:0:DAMAGE:2
