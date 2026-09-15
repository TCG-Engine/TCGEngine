# KeepTheDrawnRey_PutAnotherCardBack_ReyTriggersAfterTheAction
#// ★ USER RULING 2026-09-11 (see lof/Rey_DrawnByYoda_WaitsForTheLeaderAbility.md) applied to IC27_008 Princess
#// Leia - On a Diplomatic Mission, whose Action is Yoda's verbatim: "Draw a card, then put a card from your
#// hand on the top or bottom of your deck." Found converting her put-back to the SSOT #2 deck funnel: it
#// re-found the chosen card by CardID and never told the waiting draw trigger which hand card left, so a
#// drawn LOF_148 Rey lost her trigger whenever ANY card before her was put back.
#// P1 holds SEC_080, draws Rey, puts SEC_080 on the bottom; Rey's trigger resolves after the Action.

## GIVEN
CommonSetup: ryw/rrk/{myResources:3;myLeader:IC27_008;myhandCardIds:SEC_080}
P1OnlyActions: true
WithP1Deck: [LOF_148 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:Bottom
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P1HANDCARD:0:LOF_148
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2
P1NODECISION

---

# PutTheOldReyBack_TheDrawnReyTriggersOnce
#// P1 already holds a Rey (index 0) and draws a second (index 1); P1 puts the OLD one on the bottom. The
#// drawn Rey is still in hand, so her trigger resolves — once.

## GIVEN
CommonSetup: ryw/rrk/{myResources:3;myLeader:IC27_008;myhandCardIds:LOF_148}
P1OnlyActions: true
WithP1Deck: [LOF_148 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:Bottom
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:LOF_148
P2BASEDMG:2
P1NODECISION

---

# PutTheDrawnReyBack_NoTrigger_EvenWithAnotherReyInHand
#// The mirror: P1 puts the DRAWN Rey (index 1) on the bottom. Her trigger can't resolve, and the old Rey in
#// hand can't be revealed in her place.

## GIVEN
CommonSetup: ryw/rrk/{myResources:3;myLeader:IC27_008;myhandCardIds:LOF_148}
P1OnlyActions: true
WithP1Deck: [LOF_148 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:Bottom

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:LOF_148
P2BASEDMG:0
P1NODECISION
