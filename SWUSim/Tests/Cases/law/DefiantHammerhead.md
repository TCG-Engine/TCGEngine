# OnAttackBuffSelfDefeat
#// LAW_062 Defiant Hammerhead (6/6, space) — On Attack: if attacking a unit, you may give this unit
#// +4/+0 for this attack; if you do, defeat this unit after the attack. Attacks SOR_237 (2/3): +4 -> 10
#// power kills it; Hammerhead then self-defeats.
#// COVERAGE: offer=N/A (YES/NO "you may" prompt, not a target-choice — asserted positively via the
#//           YES branch and negatively via P1NODECISION on the base attack) · decline=
#//           OnAttack_DeclineBuff_SurvivesWithPrintedPower · boundary=unit-attack (prompt) vs
#//           base-attack (no prompt) pair: OnAttackBuffSelfDefeat + OnAttack_BaseAttack_NoPromptNoDefeat
#//           · control=N/A (self-buff/self-defeat only) · reqboundary=N/A (single YES/NO decision, no
#//           post-decision state re-read beyond the engine-standard attack pipeline)

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_062:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0

---

# OnAttack_DeclineBuff_SurvivesWithPrintedPower
#// Intended: the +4/+0 is "you may" — declining means the attack deals only the printed 6 and the
#// Hammerhead is NOT defeated afterwards. Defender LOF_069 (2/7) survives at 6 damage (it would die
#// to 10 with the buff), and the Hammerhead stays in play with the 2 counter damage.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_062:1:0
WithP2SpaceArena: LOF_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:6
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_062
P1SPACEARENAUNIT:0:DAMAGE:2

---

# OnAttack_BaseAttack_NoPromptNoDefeat
#// Intended: the ability only fires "if this unit is attacking a unit" — a base attack raises no
#// prompt at all, deals the printed 6 to the base, and the Hammerhead survives.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_062:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:6
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_062
P1NODECISION
