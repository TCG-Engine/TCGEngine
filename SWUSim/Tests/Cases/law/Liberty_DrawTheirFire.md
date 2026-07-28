# OnAttackExhaustReturnUpgrades
#// LAW_224 Liberty (9/7, space, Sentinel) — When Played/On Attack: exhaust an enemy unit and return all
#// upgrades on it that cost 4 or less to their owners' hands. Attacks the base; exhaust SEC_080 and
#// return SOR_120 (cost 2) to P2's hand.

## GIVEN
CommonSetup: yyw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_224:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# WhenPlayedExhaustReturn
#// LAW_224 also has a When Played half. Play Liberty from hand; exhaust the enemy SEC_080 and return its
#// cost-3 upgrade SEC_176 to P2's hand.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SEC_176
WithP1Hand: LAW_224

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# WhenPlayedFiltersUpgradeOverFour
#// LAW_224 only returns upgrades costing 4 or less. SEC_080 carries Fulcrum (LAW_150, cost 5) and Sudden
#// Ferocity (SEC_176, cost 3): Fulcrum stays, Sudden Ferocity returns to hand.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:LAW_150
WithP2GroundArenaUpgrade: 0:SEC_176
WithP1Hand: LAW_224

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:1

---

# WhenPlayedAlreadyExhausted
#// LAW_224 still works on an already-exhausted unit: it stays exhausted and its cost-4 Mastery (LAW_129)
#// returns to hand.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
WithP2GroundArena: SEC_080:0:0
WithP2GroundArenaUpgrade: 0:LAW_129
WithP1Hand: LAW_224

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# WhenPlayedTokenUpgradeRemovedNotReturned
#// LAW_224 removes a cost-0 token upgrade (Experience SOR_T01) rather than returning it to hand — the unit
#// is left with no upgrades and P2's hand does not grow.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP1Hand: LAW_224

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:0
