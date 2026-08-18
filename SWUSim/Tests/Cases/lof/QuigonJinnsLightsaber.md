# CombinedCostExhaust
#// LOF_201 Qui-Gon Jinn's Lightsaber — When Played: if the attached unit is Qui-Gon Jinn, exhaust any number
#// of units with combined cost 6 or less. Attached to LOF_200 (Qui-Gon Jinn), it exhausts SOR_059 (cost 1)
#// and SOR_063 (cost 3), total 4 ≤ 6 — both taken in ONE weighted multi-select (a single modal with a
#// live "N of 6 cost left" counter that greys out whatever no longer fits), resolved by one Confirm.

## GIVEN
CommonSetup: yyw/ggk/{myResources:8;handCardIds:LOF_201}
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly Qui-Gon host (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:CARDID:LOF_200

---

# LeaderHost_CombinedCostExhaust
#// LOF_201 Qui-Gon Jinn's Lightsaber — the "attached unit is Qui-Gon Jinn" check also matches the deployed
#// Qui-Gon LEADER (LOF_016). Attached to the deployed leader, the When-Played ability exhausts SOR_059 (cost 1)
#// and SOR_063 (cost 3), combined 4 ≤ 6. The leader keeps the saber attached.

## GIVEN
CommonSetup: yyw/ggk/{myResources:8;handCardIds:LOF_201;myLeader:LOF_016:1:1}
P1OnlyActions: true
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly Qui-Gon host (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# CombinedCostGate_LeaderUsesFullBudget
#// LOF_201 Qui-Gon Jinn's Lightsaber — combined cost of exhausted units must be 6 or less. Selecting the cost-6
#// Qui-Gon leader alone consumes the entire budget, so no other unit can be added and the choice finalizes with
#// only the leader exhausted; the two enemy units (SOR_059, SOR_063) stay ready. In the modal the other
#// two grey out the moment the leader is picked; here the Confirm simply carries the single pick.

## GIVEN
CommonSetup: yyw/ggk/{myResources:8;handCardIds:LOF_201;myLeader:LOF_016:1:1}
P1OnlyActions: true
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the deployed Qui-Gon leader (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:myGroundArena-0    # exhaust the leader (cost 6 = full budget)

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:READY

---

# ChooseNothing_NoExhaust
#// LOF_201 Qui-Gon Jinn's Lightsaber — the exhaust is a "may ... any number". Attached to Qui-Gon (LOF_200),
#// P1 confirms with nothing selected: no unit is exhausted, and the saber stays attached.

## GIVEN
CommonSetup: yyw/ggk/{myResources:8;handCardIds:LOF_201}
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly Qui-Gon host (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# NonQuiGonHost_NoTrigger
#// LOF_201 Qui-Gon Jinn's Lightsaber — the When-Played exhaust only fires if the attached unit is Qui-Gon Jinn.
#// Played on Battlefield Marine (SOR_095), nothing happens: no exhaust prompt and the enemy unit stays ready.
#// The saber is still attached.

## GIVEN
CommonSetup: yyw/ggk/{myResources:8;handCardIds:LOF_201}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly (non-Qui-Gon) host (enemy is now a legal host too, CR 2.e)

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:READY

---

# Offer_PoolAndPerUnitCostWeights
#// LOF_201 — the offer itself, and the "~BUDGET~<total>~<label>~<mzID>=<weight>…" side channel that
#// carries each unit's COST to the modal (Core/MZMultiChooseUI.js) so it can grey out what no longer
#// fits after each pick. Every READY unit whose own cost is 6 or less is offered — both sides, since
#// "any number of units" names no controller — and SOR_232 (AT-ST, cost 6) sits at exactly the budget.
#// The one exclusion is the saber's own host, Qui-Gon Jinn (LOF_200, cost 7): over the budget on its own,
#// so it could never be legal, and dropping it from the pool is what proves the budget is applied there.
#// The decision is left unanswered so it is still pending to read.

## GIVEN
CommonSetup: yyw/ggk/{myResources:8;handCardIds:LOF_201}
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly Qui-Gon host

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P1DECISIONTOOLTIP:Exhaust_any_number_of_units_with_6_or_less_combined_cost~BUDGET~6~cost~theirGroundArena-0=1~theirGroundArena-1=6

---

# OverBudgetPicks_AreDroppedServerSide
#// LOF_201 — the modal's budget is UX, never enforcement: the schema harness hands an answer straight to
#// the handler, and so could a hand-built request. Submitting the cost-6 AT-ST (SOR_232) AND the cost-1
#// SOR_059 totals 7, over the budget. The resolver re-measures and re-applies the budget in submitted
#// order — 6 fits and spends the lot, 1 no longer does — so only the AT-ST is exhausted and SOR_059
#// stays ready. Without this re-validation every "answer + assert the outcome" section here would pass
#// even with no cap at all.

## GIVEN
CommonSetup: yyw/ggk/{myResources:8;handCardIds:LOF_201}
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly Qui-Gon host
- P1>AnswerDecision:theirGroundArena-1&theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:READY
