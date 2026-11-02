# PlayAnotherResistanceUnit_PlusOne
#// IC27_115 Poe Dameron — Hero of the Resistance (2/4 Ground, Aggression/Heroism, Resistance, unique)
#// "When you play another Resistance card: This unit gets +1/+0 for this phase."
#// A case in SWUCollectOwnPlayReactions (the SOR_143 Fighters for Freedom shape: "another" by UniqueID, the
#// trait read off the PLAYED card, any card type — a pilot played as an upgrade is a card too). Mandatory, so it
#// applies inline to Poe's live object, as a phase STAT_BUFF with a unique ^IC27115_<n> suffix so repeated plays
#// STACK (AddTurnEffect de-dupes identical tokens — the SEC_081 Partagaz shape).
#// PREVIEW JUDGEMENT CALLS: "Resistance card" = any type with the Resistance trait (no Resistance event or upgrade
#// exists yet, so the event/upgrade branches are structurally unreachable today); each play stacks.
#//
#// COVERAGE: offer=N/A (no choice) · decline=N/A (no "you may") ·
#//           boundary=TwoResistancePlays_Stack (1 play +1, 2 plays +2) ·
#//           control=OpponentPlaysAResistanceCard_NoBuff ("you play" — the opponent's play is not yours) ·
#//           reqboundary=TwoResistancePlays_Stack (a SimulateRequestBoundary between the two plays — the stack
#//           count is read back from the persisted TurnEffects) ·
#//           modes=2P only ("you play" — self-only)
#//
#// Poe is on the board; P1 plays Kaydel Connix (Resistance; Vigilance off-aspect here: 2 + 2). Poe is 3/4.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_115:1:0
WithP1Hand: IC27_032

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4

---

# NonResistanceCard_NoBuff
#// A Battlefield Marine (Rebel) is not a Resistance card.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_115:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:POWER:2

---

# PoesOwnPlay_DoesNotCount
#// "ANOTHER Resistance card": Poe is Resistance, but his own play does not buff him.

## GIVEN
CommonSetup: rrw/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_115

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_115
P1GROUNDARENAUNIT:0:POWER:2

---

# TwoResistancePlays_Stack
#// Each play is its own trigger: Black One (space, Resistance) and then — after a request boundary — Kaydel
#// Connix. Poe is 4/4.

## GIVEN
CommonSetup: rrw/bbk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_115:1:0
WithP1Hand: [IC27_116 IC27_032]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:POWER:4

---

# ResistancePilot_PlayedAsAnUpgrade_Counts
#// A Resistance card need not be a unit play: JTL_046 (Resistance, Pilot) played as a PILOT onto Black One is
#// still "another Resistance card" — Poe gets +1/+0.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_115:1:0
WithP1SpaceArena: IC27_116:1:0
WithP1Hand: JTL_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:3

---

# BuffExpires_AtTheEndOfThePhase

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_115:1:0
WithP1Hand: IC27_032
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass

## EXPECT
PHASEISNOT:MAIN
P1GROUNDARENAUNIT:0:POWER:2

---

# OpponentPlaysAResistanceCard_NoBuff
#// "When YOU play": P2 playing a Resistance card does nothing for P1's Poe.

## GIVEN
CommonSetup: rrw/bbw/{theirResources:4}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: IC27_115:1:0
WithP2Hand: IC27_032

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:POWER:2

---

# Blanked_NoBuff
#// A Poe that has lost all abilities (SOR_138 marker) does not react.

## GIVEN
CommonSetup: rrw/bbk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_115:1:0:SOR_138
WithP1Hand: IC27_032

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:POWER:2
