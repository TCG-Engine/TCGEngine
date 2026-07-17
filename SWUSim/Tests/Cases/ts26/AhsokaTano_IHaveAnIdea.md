# DeployedAttackEndPeekPlay
#// TS26_08 Ahsoka Tano (leader deployed, 3/6) — Raid 1 + When Attack Ends: look at the top card; play it
#// (costs 1 less), discard it, or leave it. Deployed Ahsoka attacks the enemy base (Raid 1 → 4), then plays
#// SEC_080 from the top of the deck.
## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08:1:1;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Play
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:1:CARDID:SEC_080

---

# FrontEventPeekPlayTop
#// TS26_08 Ahsoka Tano (leader front) — When you play an event: you may exhaust this leader; if you do,
#// look at the top card of your deck and play it (paying its cost), discard it, or leave it. Playing the
#// neutral event Confiscate triggers Ahsoka; exhausting her plays SEC_080 from the top of the deck.
## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08;myResources:12;handCardIds:SOR_251}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Play
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1LEADER:EXHAUSTED
