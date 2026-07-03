# SEC_101 Queen Amidala — EVENT/ability damage (SOR_172 Open Fire, "Deal 4 damage to a unit") with a SPY
# as the sacrifice. P1 Open-Fires P2's Amidala for 4 (lethal — she has 3 HP). The ability-damage funnel
# (SWUDealDamageToUnit) defers and offers P2 the prevention; P2 defeats a Spy token (SEC_T01, Official —
# shares a trait with Amidala) → Amidala takes 0 and survives, the Spy is gone. (Guards the non-combat
# single-target prevent path with a token sacrifice.)
## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:SOR_172}
P1OnlyActions: true
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
