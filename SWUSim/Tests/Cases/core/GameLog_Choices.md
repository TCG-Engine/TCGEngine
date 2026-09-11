# Garindan_TheNamedCardIsLogged_WithItsSource
#// Game-log sweep, phase 1 — player CHOICES (2026-09-11). Reported: after SEC_186 Garindan names a card,
#// the named card never reached the game log. Name-a-card logging used to live in each card file, and 4 of
#// the 9 NAMECARD cards (Garindan, Stolen Starpath Unit, Inspector's Shuttle, Zuckuss) had none. It is now
#// logged ONCE, centrally, when the NAMECARD answer is applied (GameOnDecisionAnswered), with the ability
#// it belongs to as a suffix: "P1 named Battlefield Marine ([[SEC_186|Garindan]])". The source comes from
#// the log-source context, set here by Garindan's own CUSTOM continuation (SEC_186#1 queues the NAMECARD).
#// Garindan: [Cunning][Villainy], 2 — a Cunning/Villainy base + Thrawn keeps it at printed cost.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_186
WithP2Hand: [SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine

## EXPECT
LOGCONTAINS:P1 named Battlefield Marine
LOGCONTAINS:named Battlefield Marine ([[SEC_186|Garindan]])
LOGCOUNT:1:named Battlefield Marine

---

# RegionalGovernor_NamedOnce_NoLeftoverPerCardLine
#// SOR_062 Regional Governor ALREADY logged its name from its own card file. With the central log in
#// place that per-card line must be gone, or the player sees the same choice twice.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_062

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine

## EXPECT
P1GROUNDARENACOUNT:1
LOGCOUNT:1:named Battlefield Marine
LOGCONTAINS:named Battlefield Marine ([[SOR_062|Regional Governor]])

---

# Outmaneuver_TheChosenModeIsLogged
#// OPTIONCHOOSE answers are choices too: SOR_221 Outmaneuver "Choose an arena (ground or space)".

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_221
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
LOGCONTAINS:P1 chose Ground ([[SOR_221|Outmaneuver]])
LOGCOUNT:1:P1 chose Ground

---

# TwinSuns_TheChosenOpponentIsLogged
#// "Look at AN opponent's hand": with two opponents holding cards the seat is a real OPTIONCHOOSE
#// (P2&P3). The pick is logged like any other option, then the name.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1Hand: SEC_186
WithP2Hand: SOR_095
WithP3Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3
- P1>AnswerDecision:Imperial Dark Trooper

## EXPECT
SEATCOUNT:3
LOGCONTAINS:P1 chose P3 ([[SEC_186|Garindan]])
LOGCONTAINS:P1 named Imperial Dark Trooper ([[SEC_186|Garindan]])
