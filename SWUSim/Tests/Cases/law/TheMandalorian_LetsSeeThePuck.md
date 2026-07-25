# DrawSelfShield
#// LAW_052 The Mandalorian (6/5) — When Played: Draw a card. + "When you draw 1+ cards during the action
#// phase: Give a Shield token to this unit." His own When-Played draw (in the action phase) self-shields him.

## GIVEN
CommonSetup: brw/bgw/{myResources:6}
WithP1Deck: SOR_237
WithP1Hand: LAW_052

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_052
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:0

---

# DrawMultipleAtOnce_OnlyOneShield
#// LAW_052 The Mandalorian — "When you draw 1 or more cards during the action phase: give a Shield token to
#// this unit." Drawing SEVERAL cards at once is a single draw event, so it grants only ONE Shield. With the
#// Mandalorian already in play, P1 plays TWI_175 Strategic Analysis (Draw 3) during the action phase; the
#// Mandalorian ends with exactly 1 Shield and P1's hand grows by 3.

## GIVEN
CommonSetup: brw/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_052:1:0
WithP1Hand: TWI_175
WithP1Deck: [SOR_237 SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_052
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1HANDCOUNT:3

---

# OpponentDraws_NoShield
#// LAW_052 The Mandalorian — the Shield trigger is "when YOU draw", so an OPPONENT drawing gives him
#// nothing. The Mandalorian is in P1's arena; P2 plays TWI_175 Strategic Analysis (Draw 3) on their turn.
#// The Mandalorian gains no Shield.

## GIVEN
CommonSetup: brw/rbw/{}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 5
WithP1GroundArena: LAW_052:1:0
WithP2Hand: TWI_175
WithP2Deck: [SOR_237 SOR_095 SOR_128]

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_052
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2HANDCOUNT:3

---

# RegroupDraw_NoShield
#// LAW_052 The Mandalorian — the Shield trigger is limited to the ACTION phase. The cards each player draws
#// during the regroup phase must NOT shield him. With the Mandalorian in play, both players pass to the
#// regroup phase (where the standard draw happens); he ends the regroup with no Shield.

## GIVEN
CommonSetup: brw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_052:1:0
WithP1Deck: [SOR_237 SOR_095 SOR_128]
WithP2Deck: [SOR_237 SOR_095 SOR_128]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_052
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
