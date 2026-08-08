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
