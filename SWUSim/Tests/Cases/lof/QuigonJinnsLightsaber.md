# CombinedCostExhaust
#// LOF_201 Qui-Gon Jinn's Lightsaber — When Played: if the attached unit is Qui-Gon Jinn, exhaust any number
#// of units with combined cost 6 or less. Attached to LOF_200 (Qui-Gon Jinn), it exhausts SOR_059 (cost 1)
#// and SOR_063 (cost 3), total 4 ≤ 6.

## GIVEN
CommonSetup: yyw/ggk/{myResources:8;handCardIds:LOF_201}
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly Qui-Gon host (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

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
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# CombinedCostGate_LeaderUsesFullBudget
#// LOF_201 Qui-Gon Jinn's Lightsaber — combined cost of exhausted units must be 6 or less. Selecting the cost-6
#// Qui-Gon leader alone consumes the entire budget, so no other unit can be added and the choice finalizes with
#// only the leader exhausted; the two enemy units (SOR_059, SOR_063) stay ready.

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
#// P1 chooses nothing: no unit is exhausted, and the saber stays attached (the exhaust prompt lets you
#// choose nothing).

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
