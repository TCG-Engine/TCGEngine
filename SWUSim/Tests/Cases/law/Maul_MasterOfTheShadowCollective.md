# AttackEndTakeControl
#// LAW_054 Maul (6/8, Overwhelm) — When Attack Ends: if this unit dealt combat damage to a player's
#// base, you may take control of a non-leader unit that player controls. Maul attacks the base; take
#// control of the enemy SEC_080.

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
