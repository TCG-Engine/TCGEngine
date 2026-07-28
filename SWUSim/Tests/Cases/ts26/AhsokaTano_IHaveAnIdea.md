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

---

# BlankedEventStillTriggersAhsoka
#// TS26_08 Ahsoka "When you play an event" fires on the ACT of playing an event, even when the event is
#// Relentless-blanked (SOR_089) — playing a blanked event is still "playing an event"; only the event's own
#// ability is lost. Same as FrontEventPeekPlayTop but P2 controls Relentless, so P1's first event this round
#// (Confiscate SOR_251) is blanked. Ahsoka must STILL trigger: exhausting her plays SEC_080 from the deck
#// top. (Before the preamble was moved out of the blanked/Galen gate, Ahsoka did NOT trigger on a blanked
#// event and this outcome was unreachable.)

## GIVEN
CommonSetup: yyw/rrk/{myLeader:TS26_08;myResources:12;handCardIds:SOR_251}
SkipPreGame: true
P1OnlyActions: true
WithP2SpaceArena: SOR_089:1:0
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Play

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1LEADER:EXHAUSTED
