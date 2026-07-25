# WhenDrawn_RevealDealDamage
#// LOF_148 — "When you draw this card during the action phase: if you control an Aggression leader or
#// base, you may reveal it. If you do, deal 2 damage to a unit and 2 damage to a base." P1 (Aggression
#// leader+base) plays SOR_111 (When Played: draw a card) with LOF_148 on top of the deck; drawing it
#// triggers the reveal → 2 to the enemy unit + 2 to the enemy base.

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: LOF_148
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2

---

# NoAggressionLeaderOrBase_DoesNothing
#// LOF_148 — the draw trigger requires "an Aggression leader OR base". P1 here has a Command base AND a
#// Command/Heroism leader (aspect string ggw), NO Aggression. Drawing Rey via SOR_111 (When Played: draw a
#// card) does NOT offer the reveal: no prompt, no damage, Rey sits in hand. (FT: "should do nothing if no
#// leader or base with Aggression aspect".)

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: LOF_148
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# AggressionLeaderOnly_Fires
#// LOF_148 — the trigger fires off an Aggression LEADER even when the base is not Aggression. Aspect string
#// grw = Command base + Aggression/Heroism leader. Drawing Rey → reveal → deal 2 to the enemy unit + 2 to
#// the enemy base. (FT: "should deal 2 damage ... with an Aggression leader".)

## GIVEN
CommonSetup: grw/ggw/{myResources:6;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: LOF_148
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2

---

# AggressionBaseOnly_Fires
#// LOF_148 — the trigger fires off an Aggression BASE even when the leader is not Aggression. Aspect string
#// rgw = Aggression base + Command/Heroism leader. Drawing Rey → reveal → deal 2 to the enemy unit + 2 to
#// the enemy base. (FT: "should deal 2 damage ... with an Aggression base".)

## GIVEN
CommonSetup: rgw/ggw/{myResources:6;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: LOF_148
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2

---

# DeclineReveal_Passable
#// LOF_148 — the reveal is a "you may": P1 has Aggression (rrk) and draws Rey, but DECLINES the reveal.
#// No damage is dealt and Rey stays in hand. (FT: "should be passable".)

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: LOF_148
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# DifferentCardDrawn_DoesNotTrigger
#// LOF_148 — "When you DRAW THIS card" only fires when Rey herself is the drawn card. Rey already sits in
#// P1's hand; P1 plays SOR_111 to draw a DIFFERENT card (SOR_046 on top of deck). Rey's trigger does not
#// fire, no damage, Rey remains in hand. (FT: "should do nothing if in hand and a different card is drawn".)

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:SOR_111,LOF_148}
P1OnlyActions: true
WithP1Deck: SOR_046
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# RegroupDraw_DoesNotTrigger
#// LOF_148 — the trigger is "during the ACTION phase" only. P1 has Aggression (rrk) and Rey on top of the
#// deck. Both players pass to reach the regroup phase, where P1 draws Rey as part of the regroup draw.
#// Because it's not the action phase, the reveal does NOT fire: no damage. (FT: "should do nothing if drawn
#// during the regroup phase".)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Deck: LOF_148 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1BASEDMG:0
