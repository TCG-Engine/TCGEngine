# VaughnsPutOnTop_ComesBeforeReysTrigger
#// ★ USER RULING 2026-09-11: "Rey triggers, but won't resolve til after. Follow the CR." CR 7.6.11: "After
#// resolving a triggered ability 'A', if any new abilities were triggered while resolving it, the new
#// abilities are considered 'nested abilities' and must be resolved before any other abilities triggered
#// at the same time as ability 'A'." — and CR 7.6.8: resolving a triggered ability "never interrupts an
#// action or ability that is currently resolving."
#// TS26_39 Captain Vaughn: "When Defeated: Search the top 3 cards of your deck for a card and draw it. Then,
#// put a card from your hand on top of your deck." Drawing LOF_148 Rey triggers her, but she resolves only
#// after Vaughn's ability — so after the draw the pending prompt is Vaughn's put-on-top, not Rey's reveal.
#// Aggression base ("rbw") for Rey's condition. (Fixture from ts26/CaptainVaughn_SearchTheTunnels.md.)

## GIVEN
CommonSetup: rbw/rrk/{handCardIds:SEC_080}
WithP1GroundArena: TS26_39:1:1
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [LOF_148 SOR_046 SOR_128]
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:LOF_148

## EXPECT
P1HANDCOUNT:2
P1DECISIONTOOLTIP:Put_a_card_from_your_hand_on_top_of_your_deck

---

# KeepTheDrawnRey_RevealResolvesAfterVaughn
#// P1 puts SEC_080 on top and keeps Rey; THEN her nested trigger resolves: reveal → 2 to P2's only unit
#// (auto-picked) and 2 to P2's base.

## GIVEN
CommonSetup: rbw/rrk/{handCardIds:SEC_080}
WithP1GroundArena: TS26_39:1:1
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [LOF_148 SOR_046 SOR_128]
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:LOF_148
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P1DECKTOPCARD:SEC_080
P1HANDCARD:0:LOF_148
P2BASEDMG:2
P1NODECISION

---

# PutTheDrawnReyOnTop_HerTriggerCantResolve
#// P1 puts the drawn Rey herself on top of the deck. She is no longer in hand to reveal, so her trigger
#// can't resolve: no prompt, no damage.

## GIVEN
CommonSetup: rbw/rrk/{handCardIds:SEC_080}
WithP1GroundArena: TS26_39:1:1
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [LOF_148 SOR_046 SOR_128]
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:LOF_148
- P1>AnswerDecision:myHand-1

## EXPECT
P1DECKTOPCARD:LOF_148
P1HANDCARD:0:SEC_080
P2BASEDMG:0
P1NODECISION
