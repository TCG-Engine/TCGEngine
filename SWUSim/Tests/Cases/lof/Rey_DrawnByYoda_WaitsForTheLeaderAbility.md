# YodaPutBack_ComesBeforeReysTrigger
#// ★ USER RULING 2026-09-11 (SSOT #4 retro). TWI_004 Yoda - Sensing Darkness (leader): "Action [Exhaust]: If a
#// unit left play this phase, draw a card, then put a card from your hand on the top or bottom of your
#// deck." LOF_148 Rey - With Palpatine's Power: "When you draw this card during the action phase: If you
#// control an Aggression leader or base, you may reveal this card from your hand. If you do, deal 2 damage
#// to a unit and 2 damage to a base."
#// The LEADER ABILITY resolves first: Rey's draw trigger waits until Yoda's "then put a card …" is done.
#// Before the fix, drawing queued Rey's reveal prompt AHEAD of Yoda's put-back choice.
#// Rey is P1's top card, a unit left play this phase (SWU_FRIENDLY_LEFT_PLAY), and the Aggression base
#// (aspect string "rbw") satisfies Rey's condition. After Yoda's draw, the pending prompt is Yoda's.

## GIVEN
CommonSetup: rbw/rrk/{myLeader:TWI_004;myhandCardIds:SEC_080}
P1OnlyActions: true
WithP1GlobalEffect: SWU_FRIENDLY_LEFT_PLAY
WithP1Deck: [LOF_148 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:2
P1DECISIONTOOLTIP:Put_a_card_on_the_top_or_bottom_of_your_deck

---

# KeepTheDrawnRey_RevealResolvesAfterThePutBack
#// P1 keeps the drawn Rey and puts SEC_080 on the bottom. THEN Rey's trigger resolves: reveal → 2 damage to
#// P2's only unit (auto-picked) and 2 to P2's base.

## GIVEN
CommonSetup: rbw/rrk/{myLeader:TWI_004;myhandCardIds:SEC_080}
P1OnlyActions: true
WithP1GlobalEffect: SWU_FRIENDLY_LEFT_PLAY
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
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2
P1NODECISION

---

# PutTheDrawnReyOnTheBottom_NoTrigger_EvenWithAnotherReyInHand
#// The legality nuance: the trigger belongs to the Rey that was DRAWN. P1 already holds a copy of Rey (hand
#// index 0); Yoda draws the second copy (index 1) and P1 puts THAT copy on the bottom. It is no longer in
#// hand, so its trigger can't resolve — and the OTHER Rey in hand can't be revealed in its place. No
#// prompt, no damage; the old Rey stays in hand.
#// (Also pins Yoda's put-back moving exactly the CHOSEN copy: it used to re-find the card by CardID, which
#// moved the first Rey in hand — the wrong one.)

## GIVEN
CommonSetup: rbw/rrk/{myLeader:TWI_004;myhandCardIds:LOF_148}
P1OnlyActions: true
WithP1GlobalEffect: SWU_FRIENDLY_LEFT_PLAY
WithP1Deck: [LOF_148 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:Bottom

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:LOF_148
P1DECKCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1NODECISION

---

# PutTheOldReyOnTheBottom_TheDrawnReyTriggersOnce
#// The mirror: P1 puts the copy it ALREADY held (index 0) on the bottom and keeps the drawn one, which
#// shifts to index 0. The drawn Rey is still in hand, so its trigger resolves — exactly once (the old copy
#// was never drawn, so it has no trigger of its own).

## GIVEN
CommonSetup: rbw/rrk/{myLeader:TWI_004;myhandCardIds:LOF_148}
P1OnlyActions: true
WithP1GlobalEffect: SWU_FRIENDLY_LEFT_PLAY
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
P1DECKCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2
P1NODECISION

---

# KeepTheDrawnRey_ActionEndsOnceAfterTheTrigger
#// The action boundary. When Rey's trigger fires, Yoda's action is closed BEHIND it (a queued terminator),
#// not inline — otherwise the turn would pass with Rey's prompts still open. Without P1OnlyActions (which
#// hides TURNPLAYER), the turn passes to P2 exactly once, after the damage.

## GIVEN
CommonSetup: rbw/rrk/{myLeader:TWI_004;myhandCardIds:SEC_080}
WithP1GlobalEffect: SWU_FRIENDLY_LEFT_PLAY
WithP1Deck: [LOF_148 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:Bottom
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1NODECISION
TURNPLAYER:2

---

# PutTheDrawnReyOnTheBottom_ActionEndsOnce
#// The no-trigger path keeps the inline close: the turn passes to P2 exactly once.

## GIVEN
CommonSetup: rbw/rrk/{myLeader:TWI_004;myhandCardIds:LOF_148}
WithP1GlobalEffect: SWU_FRIENDLY_LEFT_PLAY
WithP1Deck: [LOF_148 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:Bottom

## EXPECT
P2BASEDMG:0
P1NODECISION
TURNPLAYER:2

---

# PutTheOldReyOnTheBottom_SurvivesTheRequestBoundaries
#// The waiting trigger lives in SWUVars (serialized), not a PHP global: Yoda's put-back spans two prompts,
#// each its own request in a live game. Same as PutTheOldReyOnTheBottom_TheDrawnReyTriggersOnce with a
#// request boundary after every answer.

## GIVEN
CommonSetup: rbw/rrk/{myLeader:TWI_004;myhandCardIds:LOF_148}
P1OnlyActions: true
WithP1GlobalEffect: SWU_FRIENDLY_LEFT_PLAY
WithP1Deck: [LOF_148 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Bottom
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0

## EXPECT
P1HANDCARD:0:LOF_148
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2
P1NODECISION
