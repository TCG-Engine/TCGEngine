# AttackEndTakeControl
#// LAW_054 Maul (6/8, Overwhelm) — When Attack Ends: if this unit dealt combat damage to a player's
#// base, you may take control of a non-leader unit that player controls. Maul attacks the base; take
#// control of the enemy SEC_080.
#// COVERAGE: offer=steal target answered from the live pool in AttackEndTakeControl / MultipleSteals
#//           (an out-of-pool answer throws); offer-absence proven by the three NoTrigger* sections ·
#//           decline=not encoded — the steal is a may-choose and no scenario declines it (Intended:
#//           no decline branch exists to port; the NoTrigger* family covers the no-offer side) ·
#//           control=core effect; revert-on-leave = StolenUnitRevertsWhenMaulDefeated /
#//           ...ReturnedToHand / MultipleStealsAllRevertWhenMaulLeaves · boundary=NoTriggerNoBaseDamage
#//           + NoTriggerIfBaseDamagedOnPreviousAttack (timing) + NoTriggerIfDefeatedDuringAttack
#//           (dead attacker) vs TriggerViaOverwhelm (excess-to-base counts) · reqboundary=attack ->
#//           steal-answer crosses a request boundary in every positive section

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_054:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0

---

# TriggerViaOverwhelm
#// LAW_054 Maul (6/8, Overwhelm) — attacking an enemy UNIT still triggers the take-control if the excess
#// Overwhelm damage reaches the base. Maul attacks Wampa (SOR_164, 4/5): 6 power - 5 HP = 1 excess to the
#// base, so Maul dealt combat damage to the base → take control of the remaining enemy (SOR_232 AT-ST).

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_054:1:0
WithP2GroundArena: [SOR_164:1:0 SOR_232:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0

---

# NoTriggerNoBaseDamage
#// LAW_054 Maul — if he deals NO combat damage to a base, the take-control does not fire. Maul (6 power)
#// attacks AT-ST (SOR_232, 6/7): 6 damage into 7 HP leaves no Overwhelm excess, so the base is untouched
#// → no steal. Both units survive; the second enemy stays under P2's control.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_054:1:0
WithP2GroundArena: [SOR_232:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_054
P2GROUNDARENACOUNT:2

---

# NoTriggerIfDefeatedDuringAttack
#// LAW_054 Maul — the take-control requires Maul to SURVIVE the attack. Maul attacks Ravenous Rathtar
#// (LOF_168, 8/5): Maul's 6 kills the Rathtar and 1 Overwhelm excess reaches the base, but the Rathtar's
#// 8 power defeats Maul (8 HP) simultaneously → no steal even though the base was damaged.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_054:1:0
WithP2GroundArena: [LOF_168:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2BASEDMG:1

---

# NoTriggerIfAnotherFriendlyDamagesBase
#// LAW_054 Maul — only Maul's OWN combat damage to a base arms the take-control. A different friendly unit
#// (SOR_095 Battlefield Marine) attacks the base while Maul sits idle → no steal.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_054:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2BASEDMG:3

---

# StolenUnitRevertsWhenMaulDefeated
#// LAW_054 Maul — the take-control lasts only "until Maul leaves the arena". Maul attacks the base and
#// steals Wampa (SOR_164); Maul is then defeated (Vanquish, SOR_078, targeting Maul), and Wampa reverts
#// to its owner.

## GIVEN
CommonSetup: bbw/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_054:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: SOR_078

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164

---

# StolenUnitRevertsWhenMaulReturnedToHand
#// LAW_054 Maul — "leaves the arena" also covers being bounced. Maul steals Wampa (SOR_164), then Waylay
#// (SOR_222) returns Maul to its owner's hand → Wampa reverts to the opponent.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_054:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: SOR_222

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164

---

# NoTriggerIfBaseDamagedOnPreviousAttack
#// LAW_054 Maul — the take-control checks damage dealt to a base on THIS attack, not earlier this phase.
#// Attack 1: Maul hits the base and steals Wampa (SOR_164). Bravado (SHD_182) re-readies Maul. Attack 2:
#// Maul attacks AT-ST (SOR_232, 6/7) dealing no Overwhelm excess → no base damage this attack → no second
#// steal, even though a base WAS damaged earlier this phase.

## GIVEN
CommonSetup: rrk/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_054:1:0
WithP2GroundArena: [SOR_164:1:0 SOR_232:1:0]
WithP1Hand: SHD_182

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232

---

# MultipleStealsAllRevertWhenMaulLeaves
#// LAW_054 Maul — steals from multiple attacks all revert together when Maul leaves. Attack 1 (base)
#// steals Wampa (SOR_164); Bravado (SHD_182) re-readies Maul; attack 2 (base) steals AT-ST (SOR_232).
#// Waylay (SOR_222) then bounces Maul → BOTH stolen units return to the opponent at once.

## GIVEN
CommonSetup: ryk/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: LAW_054:1:0
WithP2GroundArena: [SOR_164:1:0 SOR_232:1:0]
WithP1Hand: [SHD_182 SOR_222]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P2GROUNDARENACOUNT:2

---

# TriggerViaDirectOverwhelm_DefenderDeadBeforeCombatDamage
#// LAW_054 Maul — CR step 4.c: when the defending unit is already out of play as combat damage would be
#// dealt, an attacker with Overwhelm still deals it, and per §9.11 / 7.f ALL of that damage becomes excess
#// onto the enemy base. Maul wears SHD_177 Vambrace Flamethrower (+1/+1, so 7 power) and attacks SOR_128
#// Death Star Stormtrooper (3/1); the Vambrace's On Attack kills the Stormtrooper BEFORE combat damage, so
#// there is no defender left to subtract and Maul's full 7 lands on the base. That is combat damage dealt
#// to a player's base, so the take-control arms and Maul steals the remaining enemy AT-ST.
#// Distinct from TriggerViaOverwhelm, where the defender SURVIVED to absorb part of the hit: there the
#// excess is power-minus-defender-HP, here there is no subtraction at all — an implementation that
#// computes excess only as "power - defender remaining HP" finds nothing to spill and never arms the
#// steal, which no other section on this card would catch.
#// (This scenario was previously recorded as deferred; the technique it needs is the same one
#// ChirrutImwe_IDontNeedLuck::DirectOverwhelm_DefenderAlreadyDeadWhenCombatDamageLands already uses.)

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_054:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: [SOR_128:1:0 SOR_232:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:3
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:7
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_232
P2GROUNDARENACOUNT:0

---

# StealPool_NonLeaderOfTheDamagedPlayerAnyArena
#// COVERAGE (sharpens the ledger in AttackEndTakeControl, which recorded offer only as "answered from the
#//           live pool"): offer=StealPool_NonLeaderOfTheDamagedPlayerAnyArena asserts the pool EXACTLY —
#//           the damaged player's non-leader units in BOTH arenas, with their deployed leader and P1's own
#//           units excluded; offer-absence stays proven by the three NoTrigger* sections.
#// LAW_054 Maul — "you may take control of A NON-LEADER UNIT THAT PLAYER CONTROLS", where "that player" is
#// whoever's base Maul just damaged. Two restrictions and one deliberate absence, each with a witness on
#// the board: P2's DEPLOYED LEADER at theirGroundArena-2 must be OUT on "non-leader"; P1's own SOR_095 must
#// be OUT because the scope is the DAMAGED player's units, not "any unit"; and P2's SPACE SOR_225 must be
#// IN because the text names no arena — Maul is a ground attacker but the steal reaches across arenas.
#// The offer is an MZMAYCHOOSE, so it stays pending even though several targets are legal.

## GIVEN
CommonSetup: grk/bgw/{theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: [LAW_054:1:0 SOR_095:1:0]
WithP2GroundArena: [SOR_164:1:0 SEC_080:1:0]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P2GROUNDARENAUNIT:2:ISLEADERUNIT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0
