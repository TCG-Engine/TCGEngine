# HealNothing_NoSelfDamage
#// SOR_052 — "up to 8" permits healing nothing: the player assigns 0, so there is no self-damage and
#// the damaged unit stays damaged. Redemption enters at full HP.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1SPACEARENAUNIT:0:CARDID:SOR_052
P1SPACEARENAUNIT:0:DAMAGE:0

---

# HealUnitAndBase_SelfDamage
#// SOR_052 Redemption (Unit, Space, 6/9, Sentinel) — When Played: heal up to 8 total damage from any
#// number of units and/or bases, then deal that much (the ACTUAL healed) to itself. P1 heals 4 from a
#// damaged ground unit (4→0) + 2 from its base (3→1) = 6 total, so Redemption self-damages 6 (partial:
#// 6 of the 8 pool). Sentinel is auto-wired and not tested here.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052;myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:4    # 3/7 with 4 damage → healed to 0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:4,myBase-0:2

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:1
P1SPACEARENAUNIT:0:CARDID:SOR_052
P1SPACEARENAUNIT:0:DAMAGE:6

---

# NoDamagedTargets_Fizzle
#// SOR_052 — no damaged units or bases anywhere: the heal has no targets, so no decision is queued
#// and Redemption simply enters at full HP. Absence guard for the empty-target path.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_052
P1SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# SelfDamageIsActualHealed
#// SOR_052 — the self-damage equals the ACTUAL healed, not the amount assigned. A unit with only 2
#// damage is over-assigned 6 heal; OnHealUnit clamps the heal to 2, so Redemption self-damages 2 (not
#// 6 and not the pool 8). Guards that "deal that much" reads actual-healed, not the assignment string.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2    # 3/7 with 2 damage

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:6

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:2

---

# TwinSuns_CanHealAFarSeatsBase
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — and this one needed BOTH halves of the card fixed.
#// The applier (SOR_052#0) resolved a picked base with the my/their ternary, i.e. seat 2. But the OFFER
#// was the real blocker: it listed exactly ['myBase-0', 'theirBase-0'], so a far seat's damaged base was
#// never even presented — and the bare 'theirBase-0' token does not name WHICH seat it means.
#// Fixing only the applier left this section red, which is how the offer half was found.
#// Seat 4's base starts on 6 damage and is healed by 3 → 3. Seat 2's base is untouched at 0.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:6
WithP4Base: SOR_019:6
WithP1Resources: 8
WithP1Hand: SOR_052
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p4Base-0:3
## EXPECT
SEATCOUNT:4
P4BASEDMG:3
P2BASEDMG:0

---

# Sentinel_EnemySpaceAttacksAreForcedOntoRedemption
#// SOR_052 Redemption — the FIRST clause, previously untested ("Sentinel is auto-wired and not tested
#// here"). Per CR: "Units in this arena can't attack your non-Sentinel units or your base."
#// COVERAGE: offer=this section + Redemption_HealPoolSpansBOTHSIDES (attack pool via ATTACKTARGETS;
#//           the heal pool is an MZSPLITASSIGN, which exposes no selectable list, so its scope is
#//           asserted through the end state instead) · decline=HealNothing_NoSelfDamage (the "up to"
#//           lower bound — assign 0, no heal, no self-damage) · control=N/A (a one-shot When Played
#//           that resolves during the play ceremony; nothing persists to survive a control change,
#//           and Sentinel is a printed keyword read off the arena unit) ·
#//           boundary=HealsTheFullEight_SelfDamageEightLeavesOneHP (the cap, 8) vs
#//           SelfDamageIsActualHealed (below the cap) · reqboundary=N/A (the split-assign resolves
#//           inside the play ceremony; MZSPLITASSIGN round-trip is covered generically in the
#//           MZSplitAssign harness cases)
#//
#// Both halves in one board. Redemption sits in P1's SPACE arena next to a non-Sentinel space unit
#// (SOR_237), and P1 also holds a ground unit.
#//   • P2's SPACE unit may attack EXACTLY ONE thing — Redemption. Not the non-Sentinel SOR_237 next to
#//     it, and not P1's base. (Control measurement: swap Redemption for a vanilla space unit and the
#//     same board gives that attacker 3 targets, so the 1 here is load-bearing.)
#//   • P2's GROUND unit is in a DIFFERENT arena, so Sentinel does not reach it: it keeps its normal 2
#//     targets (P1's ground unit + P1's base). This is the negative that proves "in this arena" is
#//     doing work rather than the keyword blanket-locking the whole board.

## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_052:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
ATTACKTARGETS:2:S:0:1
ATTACKTARGETS:2:G:0:2

---

# HealsTheFullEight_SelfDamageEightLeavesOneHP
#// SOR_052 — the UPPER boundary of "up to 8". Every prior section heals strictly less than the pool
#// (0, 2, 6), so nothing pinned what happens AT the cap. A 9/9 AT-AT (SOR_088) carrying 8 damage is
#// healed by the whole 8 → 0 damage, and Redemption then takes all 8 back. Redemption is 6/9, so 8 is
#// exactly one short of lethal: it must SURVIVE at 8 damage, not be defeated by its own ability.
#// (Paired with SelfDamageIsActualHealed, which sits below the cap at 2.)

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052}
P1OnlyActions: true
WithP1GroundArena: SOR_088:1:8    # 9/9 carrying 8 damage — the full pool is available to heal

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:8

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_052
P1SPACEARENAUNIT:0:DAMAGE:8
P1SPACEARENAUNIT:0:HP:9

---

# Redemption_HealPoolSpansBOTHSIDES
#// SOR_052 — SCOPE. "Heal up to 8 total damage from any number of units and/or bases" names no
#// controller, so per the unqualified-target reading the pool spans BOTH sides: an ENEMY unit and the
#// ENEMY base are legal heal targets, and healing them still costs Redemption the self-damage.
#// Every existing section heals only P1-side objects, so a friendly-only implementation would pass
#// them all. Here P2's damaged 3/7 is healed by 4 (4→0) and P2's base by 2 (3→1) = 6 healed, so
#// Redemption self-damages 6.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052;theirBaseDamage:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:4    # 3/7 with 4 damage — an ENEMY unit in the heal pool

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:4,theirBase-0:2

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:1
P1BASEDMG:0
P1SPACEARENAUNIT:0:CARDID:SOR_052
P1SPACEARENAUNIT:0:DAMAGE:6
