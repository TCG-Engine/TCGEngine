# Front_DrawThenPutACardOnTop
#// IC27_008 Princess Leia (On a Diplomatic Mission) — 6 cost, 4/7, Cunning+Heroism, Rebel/Official.
#// Front: "Action [1 resource, Exhaust]: Draw a card, then put a card from your hand on the top or
#//   bottom of your deck."
#// Epic Action: deploy at 6+ resources (generic — the threshold IS the leader's printed cost).
#// Deployed: "On Attack: Draw a card, then put a card from your hand on the top or bottom of your deck."
#// Clause verbatim from TWI_004 Yoda (Sensing Darkness).
#// Deck is top-first [SOR_095 SOR_046]: the draw takes SOR_095, then SEC_080 goes back on TOP.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3;myLeader:IC27_008;myhandCardIds:SEC_080}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:Top

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SEC_080
P1HANDCOUNT:1
P1RESAVAILABLE:2
P1LEADER:EXHAUSTED

---

# Front_DrawThenPutACardOnBottom
#// The DISCRIMINATING PAIR for Top vs Bottom: same board, same pick, opposite placement. Choosing
#// Bottom leaves SOR_046 (the card the draw exposed) on top instead.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3;myLeader:IC27_008;myhandCardIds:SEC_080}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:Bottom

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_046
P1HANDCOUNT:1
P1RESAVAILABLE:2

---

# Front_Unaffordable_IsACompleteNoOp
#// COST GATE: with no ready resource the [1 resource] cost cannot be paid, so the action must be a
#// complete no-op — the leader is NOT exhausted, nothing is drawn, and the player keeps their action.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:IC27_008;myhandCardIds:SEC_080}
P1OnlyActions: true
WithP1Resources: 1:SOR_046:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1HANDCOUNT:1
P1DECKCOUNT:2
P1NODECISION

---

# Front_EmptyHandAfterDraw_StillDrawsAndNoPlacement
#// NO-VALID-TARGET on the second half: the draw is unconditional, and if the drawn card is the only
#// one in hand it is still a legal thing to put back — so the real edge is an EMPTY deck, where the
#// draw yields nothing and there is nothing to place. Must not crash or hang.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3;myLeader:IC27_008}
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1HANDCOUNT:0
P1DECKCOUNT:0

---

# Deployed_OnAttack_DrawThenPutOnTop
#// THE DEPLOYED SIDE — a separate ability set that must clear the bar on its own. Driven through the
#// REAL path: actually DeployLeader (Epic, 6+ resources) and then attack, rather than dropping the
#// leader unit into the arena as a fixture.
#// The deployed On Attack has NO resource cost, so the 6 resources are untouched by it.

## GIVEN
CommonSetup: yyw/yyw/{myResources:6;myLeader:IC27_008;myhandCardIds:SEC_080}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:Top

## EXPECT
P1LEADER:DEPLOYED
P2BASEDMG:4
P1DECKCOUNT:2
P1DECKTOPCARD:SEC_080
P1HANDCOUNT:1
P1RESAVAILABLE:6

---

# Deployed_OnAttack_PutOnBottom
#// The deployed side's Top/Bottom choice is genuinely its own — proven by exercising the opposite
#// branch through the deploy+attack path.

## GIVEN
CommonSetup: yyw/yyw/{myResources:6;myLeader:IC27_008;myhandCardIds:SEC_080}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:Bottom

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_046
P1HANDCOUNT:1
