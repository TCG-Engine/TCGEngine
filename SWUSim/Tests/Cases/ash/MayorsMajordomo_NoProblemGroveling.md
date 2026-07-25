# DiscardExhaust
#// ASH_217 Mayor's Majordomo (Ground, 1/4) — Action [Exhaust, discard a card from your hand]: exhaust a
#// unit. Majordomo discards SOR_095 (its only hand card) and exhausts the enemy SEC_080.
## GIVEN
CommonSetup: yyk/yyk/{handCardIds:SOR_095}
WithP1GroundArena: ASH_217:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_217
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# Action_ExhaustEnemyUnit
#// ASH_217 Mayor's Majordomo — Action [Exhaust, discard a card from hand]: exhaust a unit. Discarding SOR_095
#// as the cost, it exhausts the enemy SOR_046.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: ASH_217:1:0
WithP1Hand: SOR_095
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# ExhaustFriendlyUnit
#// ASH_217 Mayor's Majordomo — the Action [Exhaust, discard a card]: exhaust a unit can target a FRIENDLY
#// unit. Discarding SOR_095 as the cost, Majordomo exhausts the friendly SOR_046.
## GIVEN
CommonSetup: yyk/yyk/{handCardIds:SOR_095}
WithP1GroundArena: ASH_217:1:0
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_217
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# NoHandCard_AbilityUnavailable
#// ASH_217 Mayor's Majordomo — the Action's cost includes discarding a card from hand, so with an empty
#// hand the ability can't be paid for: attempting it exhausts nothing (neither Majordomo nor the enemy).
## GIVEN
CommonSetup: yyk/yyk/{}
WithP1GroundArena: ASH_217:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_217
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:READY
