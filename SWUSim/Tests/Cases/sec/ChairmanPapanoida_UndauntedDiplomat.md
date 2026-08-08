# OnDraw_Disclose_CreateSpy
#// SEC_159 Chairman Papanoida (Ground, 2/6, Aggression/Aggression) — When a player draws 1+ cards
#//   during the action phase: you may disclose AggressionAggression → create a Spy token.
#// SEC_159 in play. P1 plays SOR_111 (When Played: draw a card) → the draw fires SEC_159's reaction →
#// disclose two SEC_133 (Aggression each) → create a Spy token. Ground ends with SEC_159 + the Spy.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SEC_159:1:0
WithP1Hand: SOR_111
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP1Deck: [SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_111
P1GROUNDARENACOUNT:2
P1NODECISION

---

# OnDraw_Decline_NoSpy
#// SEC_159 Chairman Papanoida — the disclose is optional ("you may"). P1 plays SOR_111 (draw a card), the draw
#//   fires the reaction, and P1 declines the disclose → no Spy token is created (ground keeps only Papanoida).

## GIVEN
CommonSetup: rrw/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SEC_159:1:0
WithP1Hand: SOR_111
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP1Deck: [SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_111
P1GROUNDARENACOUNT:1
P1NODECISION

---

# OnDraw_CannotDisclose_NoSpy
#// SEC_159 Chairman Papanoida — with no two Aggression cards in hand the disclose cannot be paid, so no Spy is
#//   created. P1 plays SOR_111 (draw); the only card drawn is the non-Aggression SOR_095 → disclose impossible.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SEC_159:1:0
WithP1Hand: SOR_111
WithP1Deck: [SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_111
P1GROUNDARENACOUNT:1
P1NODECISION

---

# OnDraw_OpponentDrawsMultiple_OneSpy
#// SEC_159 Chairman Papanoida — the reaction fires when ANY player draws during the action phase, and a single
#//   multi-card draw yields only one Spy. P1 passes; P2 plays TWI_175 Strategic Analysis (draw 3) → P1's
#//   Papanoida triggers once → disclose two SEC_133 (Aggression) → exactly one Spy token joins P1's ground.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_159:1:0
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP2Resources: 5
WithP2Hand: TWI_175
WithP2Deck: [SOR_095, SOR_046, SOR_095]

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1GROUNDARENACOUNT:2
P1NODECISION

---

# OnDraw_RegroupPhaseDraw_NoTrigger
#// SEC_159 Chairman Papanoida — the reaction is scoped to "when a player draws 1+ cards DURING THE ACTION
#// PHASE". The regroup-phase draws (each player draws 2) must NOT fire it. Both players pass into regroup;
#// afterwards no Spy token exists and P1 still holds its disclose cards with no prompt pending.
## GIVEN
CommonSetup: rrw/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SEC_159:1:0
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_159
P1NODECISION
P2NODECISION

---

# OnDraw_OpponentDraws_StillTriggersForPapanoidasController
#// SEC_159 Chairman Papanoida — "when A PLAYER draws", not "when you draw", so an OPPONENT's action-phase
#// draw also offers the disclose to Papanoida's controller. P2 plays SOR_111 (When Played: draw a card);
#// P1 discloses two Aggression cards and gets the Spy token.
## GIVEN
CommonSetup: rrw/rrk/{theirResources:5}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SEC_159:1:0
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP2Hand: SOR_111
WithP2Deck: [SOR_095 SOR_095]
## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1
## EXPECT
P1GROUNDARENACOUNT:2
P1NODECISION

---

# OnDraw_UnderEnemyControl_TheNewControllerDisclosesAndGetsTheSpy
#// SEC_159 Chairman Papanoida — the reaction belongs to whoever controls him. P2 takes control with
#// SOR_122 Traitorous, then P1 draws (SOR_111's When Played): the disclose is offered to P2, who reveals
#// two Aggression cards from P2's OWN hand and gets the Spy token on P2's board.

## GIVEN
CommonSetup: rrw/ggk
WithActivePlayer: 2
WithP2Resources: 8
WithP1Resources: 5
WithP1GroundArena: SEC_159:1:0
WithP1Hand: SOR_111
WithP2Hand: SOR_122
WithP2Hand: SEC_133
WithP2Hand: SEC_133
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0&myHand-1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SEC_159
P2GROUNDARENAUNIT:1:CARDID:SEC_T01
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# BothPlayersDrawSimultaneously_TriggersOncePerDRAW_TwoSpies
#// SEC_159 Chairman Papanoida — "When A PLAYER draws 1 or more cards during the action phase" is per
#// DRAW EVENT, and one card that makes BOTH players draw produces TWO of them. P1 attacks with LAW_048
#// Chio Fain ("On Attack: you may choose 2 players. If you do, they each draw a card"): both decks go
#// 3 → 2, and Papanoida offers the AggressionAggression disclose TWICE, creating TWO Spy tokens.
#// Contrast OnDraw_OpponentDrawsMultiple_OneSpy, where several cards drawn in ONE event give a single Spy
#// — the trigger counts events, not cards, and this section is the other side of that.
#// (Disclose REVEALS rather than discards, so the same two Aggression cards pay for both discloses; P1's
#// hand ends at 3 = the two disclosed cards plus the one drawn.)

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_159:1:0
WithP1GroundArena: LAW_048:1:0
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1GROUNDARENACOUNT:4
P1HANDCOUNT:3
P1DECKCOUNT:2
P2DECKCOUNT:2
P1NODECISION
