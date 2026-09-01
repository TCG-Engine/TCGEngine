# Attacked_Readies
#// SOR_196 Chewbacca (unit) — "When this unit is attacked: Ready him." (On Defense window per CR 15.c)
#// Chewbacca starts EXHAUSTED. P1 attacks him (Sentinel forces the attack onto Chewbacca). His
#// On Defense readies him; combat still resolves (both survive). Proves the trigger fires AND readies
#// the correct unit (the defender, not the attacker — the OnDefense mzID frame fix).
#// COVERAGE: offer=Sentinel_TheBaseIsNotAttackable + Sentinel_NonSentinelFriendlyIsNotAttackable
#//           (asserted by the REDIRECT, not by P1SELECTABLEEXACT — Sentinel narrows the attack pool
#//           to exactly one target, so the engine resolves inline with no picker to inspect and the
#//           declared-but-illegal target being ignored is the proof) · decline=N/A (neither clause
#//           carries a "you may": Sentinel is a static restriction and "Ready him" is mandatory and
#//           automatic — no decision is ever queued, see AnotherSentinelIsAttacked_ChewbaccaNot-
#//           Readied) · boundary=Attacked_Readies (3 damage on 6 HP → survives readied) vs
#//           DefeatedByTheAttack_ReadyDoesNotSaveHim (exactly 6 → defeated anyway) ·
#//           control=ReadiedByTheAttack_CanAttackOnHisOwnTurn (the ready is granted by the
#//           ATTACKING seat's combat resolution but must land on the DEFENDING controller's unit
#//           and be usable by that controller on their own turn — the cross-seat frame the OnDefense
#//           mzID conversion exists for) · reqboundary=ReadiedByTheAttack_CanAttackOnHisOwnTurn
#//           (the ready is written in P1's request and read back in P2's, across a serialization)

## GIVEN
CommonSetup: ggw/yyw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_196:0:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_196
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1

---

# NotAttacked_StaysExhausted
#// SOR_196 Chewbacca — On Defense fires ONLY when HE is attacked.
#// Absence guard: a SPACE combat elsewhere doesn't ready the exhausted Chewbacca sitting in the ground arena.

## GIVEN
CommonSetup: ggw/yyw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_196:0:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_196
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# Sentinel_TheBaseIsNotAttackable
#// SOR_196 Chewbacca — the FIRST clause, Sentinel: "Units in this arena can't attack your
#// non-Sentinel units or your base." P2's only ground unit is Chewbacca, so P1's Consular Security
#// Force has exactly ONE legal ground target and P2's base is not among them. The attack is declared
#// at the BASE and still resolves against Chewbacca — with the pool narrowed to a single target the
#// engine resolves it inline, so the redirect IS the assertion that the base was never offered.
#// P2's base ends on 0 damage; Chewbacca takes 3 and deals 3 back.

## GIVEN
CommonSetup: ggw/yyw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0    # Consular Security Force (3/7)
WithP2GroundArena: SOR_196:1:0    # Chewbacca (Sentinel, 3/6)

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:CARDID:SOR_196
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Sentinel_NonSentinelFriendlyIsNotAttackable
#// The other half of the Sentinel clause: a NON-Sentinel friendly unit is protected too. P2 fields
#// Chewbacca (idx 0) alongside a Battlefield Marine (idx 1); the attack is declared at the Marine
#// and still lands on Chewbacca, because Chewbacca is again the only legal target. The Marine ends
#// undamaged — proof it was never in the pool.

## GIVEN
CommonSetup: ggw/yyw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_196:1:0    # Chewbacca (Sentinel) — idx 0
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine (non-Sentinel) — idx 1

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_196
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:DAMAGE:0
P2BASEDMG:0

---

# Sentinel_DoesNotProtectTheOtherArena
#// Intended: Sentinel is scoped to "units in THIS arena" — a ground Chewbacca constrains ground
#// attackers only. P1's Alliance X-Wing attacks from the space arena and reaches P2's base for its
#// full 2 power, with Chewbacca standing untouched in the ground arena. The load-bearing negative
#// for the arena word in the reminder text.

## GIVEN
CommonSetup: ggw/yyw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0     # Alliance X-Wing (2/3)
WithP2GroundArena: SOR_196:1:0    # Chewbacca (Sentinel, ground)

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:CARDID:SOR_196
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# AnotherSentinelIsAttacked_ChewbaccaNotReadied
#// Intended: the second clause fires on "when THIS unit is attacked", not on any attack into his
#// arena. P2 fields an exhausted Chewbacca beside a Cell Block Guard (SOR_229, 3/3, also Sentinel),
#// so BOTH are legal targets and the attacker can pick the other one. P1's Regional Governor
#// (1 power) attacks the Cell Block Guard: Chewbacca is untouched and STAYS EXHAUSTED. Contrast
#// Attacked_Readies, where the same board state with Chewbacca as the defender readies him.

## GIVEN
CommonSetup: ggw/yyw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_062:1:0    # Regional Governor (1/4)
WithP2GroundArena: SOR_196:0:0    # Chewbacca, exhausted — idx 0
WithP2GroundArena: SOR_229:1:0    # Cell Block Guard (Sentinel, 3/3) — idx 1

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_196
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# ReadiedByTheAttack_CanAttackOnHisOwnTurn
#// Intended: the ready is a REAL ready, not a cosmetic flag — the whole point of the ability is that
#// Chewbacca is available again on his controller's next action. P1 attacks the exhausted Chewbacca
#// (his On Defense readies him, he takes 3 and deals 3), the turn passes, and P2 then attacks P1's
#// base with him for 3. He ends exhausted again, this time from his own attack.

## GIVEN
CommonSetup: ggw/yyw/{}
WithP1GroundArena: SOR_046:1:0    # Consular Security Force (3/7)
WithP2GroundArena: SOR_196:0:0    # Chewbacca, exhausted

## WHEN
- P1>AttackGroundArena:0:0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P2GROUNDARENAUNIT:0:CARDID:SOR_196
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# DefeatedByTheAttack_ReadyDoesNotSaveHim
#// Intended: "Ready him" is not a defeat-prevention. An AT-ST (SOR_232, 6/7) attacks the exhausted
#// Chewbacca: the On Defense readies him, and the 6 combat damage still meets his 6 HP exactly, so
#// he is defeated. Boundary partner of Attacked_Readies (3 damage, four short of lethal → he
#// survives readied). The AT-ST's Overwhelm spills nothing because 6 damage is exactly lethal.

## GIVEN
CommonSetup: ggw/yyw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0    # AT-ST (6/7)
WithP2GroundArena: SOR_196:0:0    # Chewbacca, exhausted

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3
