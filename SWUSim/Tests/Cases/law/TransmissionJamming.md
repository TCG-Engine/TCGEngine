# NamedCantBePlayed
#// LAW_243 Transmission Jamming (Cunning event, cost 1) — "Name a card. Cards with that name can't be
#// played this phase." P1 names Battlefield Marine; P2's attempt to play SOR_095 is blocked (stays in hand).

## GIVEN
CommonSetup: yyw/ggw/{myResources:1;theirResources:3}
WithActivePlayer: 1
WithP1Hand: LAW_243
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# OtherCardsStillPlayable
#// LAW_243 Transmission Jamming — only cards with the NAMED title are locked. P1 names Wampa; P2 can still
#// play a differently-named card (SOR_095 Battlefield Marine) normally this phase.

## GIVEN
CommonSetup: yyw/ggw/{myResources:1;theirResources:3}
WithActivePlayer: 1
WithP1Hand: LAW_243
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Wampa
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2HANDCOUNT:0

---

# BlockExpiresNextPhase
#// LAW_243 Transmission Jamming — the name lock lasts only THIS phase. P1 names Battlefield Marine; after
#// the action phase ends and the next begins, P2 can play SOR_095 Battlefield Marine again.

## GIVEN
CommonSetup: yyw/ggw/{myResources:1;theirResources:3}
WithActivePlayer: 1
WithP1Deck: SOR_237
WithP1Deck: SOR_095
WithP2Deck: SOR_237
WithP2Deck: SOR_095
WithP1Hand: LAW_243
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Pass
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1

---

# NamedCardCantBePlayedAsPilot
#// LAW_243 Transmission Jamming — the name-lock is enforced at the play-from-hand entry, BEFORE the
#// unit-vs-pilot branch, so a named Piloting card can't be played as a pilot either. P1 names "Dagger
#// Squadron Pilot" (JTL_196); attempting to play it (which would otherwise offer a Unit/Pilot choice onto
#// the friendly AT-ST) is blocked — it stays in hand and the AT-ST gains no upgrade.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1Hand: LAW_243
WithP1Hand: JTL_196

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Dagger Squadron Pilot
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# UnnamedPilotStillPlayable
#// LAW_243 Transmission Jamming — control: with no name-lock on it, JTL_196 Dagger Squadron Pilot plays
#// normally as a pilot onto the friendly AT-ST (upgrade count 1). Confirms the block above is real, not a
#// pilot-play limitation.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1Hand: JTL_196

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
