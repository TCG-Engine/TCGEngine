#// TWIN SUNS HOUSE RULES — the 4th counter: "Take Credit 4th Action" / "Take Restore 4th Action"
#// (docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md). With either rule a fourth counter joins Initiative,
#// Blast and Plan. Anyone may take it, under the same one-counter-per-seat-per-round rule, and taking it ends your round
#// like Blast or Plan. Credit: one normal Credit token. Restore: heal your own base by N.
#// Its state is the SWU_FOURTH_COUNTER variable (AVAILABLE | P{n}); FOURTHCOUNTER reads SWUFourthCounterState(), which
#// is '' whenever neither rule is in play.
# Credit_TakingItGivesACreditTokenAndEndsYourRound
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: takeCredit
## WHEN
- P1>TakeCounter:credit
## EXPECT
FOURTHCOUNTER:P1
P1CREDITCOUNT:1
TURNPLAYER:2
P1FOURTHAVAIL:0
P2FOURTHAVAIL:0
LOGCONTAINS:took the credit counter

---

# Restore_HealsYourOwnBaseByTheRoomsValue
## GIVEN
CommonSetup3P: bbk/bbk/bbk/{myBaseDamage:5}
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: takeRestore=3
## WHEN
- P1>TakeCounter:restore
## EXPECT
FOURTHCOUNTER:P1
P1BASEDMG:2
TURNPLAYER:2
LOGCONTAINS:took the restore counter

---

# BeforeTaking_EverySeatSeesItAvailable
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: takeRestore=1
## WHEN
## EXPECT
FOURTHCOUNTER:AVAILABLE
P1FOURTHAVAIL:1
P2FOURTHAVAIL:1
P3FOURTHAVAIL:1

---

# NoRule_ThereIsNoFourthCounter
#// Without either rule the counter does not exist: never offered, and a forged take changes nothing.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>TakeCounter:credit
## EXPECT
FOURTHCOUNTER:
P1FOURTHAVAIL:0
P1CREDITCOUNT:0
TURNPLAYER:1

---

# WrongKind_IsRefused
#// The room chose Credit; a Restore take is refused and does not end the round.
## GIVEN
CommonSetup3P: bbk/bbk/bbk/{myBaseDamage:5}
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: takeCredit
## WHEN
- P1>TakeCounter:restore
## EXPECT
FOURTHCOUNTER:AVAILABLE
P1BASEDMG:5
TURNPLAYER:1

---

# OneCounterPerSeat_ABlastTakerCannotAlsoTakeIt
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: takeCredit
## WHEN
- P1>TakeCounter:blast
## EXPECT
FOURTHCOUNTER:AVAILABLE
P1FOURTHAVAIL:0
P2FOURTHAVAIL:1

---

# FourSeat_TheLastSeatMustTakeItInsteadOfPassing
#// "Instead of Pass being the 4th action": at four seats the fourth player used to be left with only Pass once
#// Initiative, Blast and Plan were gone. With the 4th counter still in the centre, Pass is NOT available (CR §12.6.1.a:
#// you may only pass when no counter is left) — they take it, and that ends the round.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: takeCredit
WithP3Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Claim
- P2>TakeCounter:blast
- P3>TakeCounter:plan
- P3>AnswerDecision:PASS
## EXPECT
TURNPLAYER:4
P4FOURTHAVAIL:1
P4PASSAVAIL:0

---

# FourSeat_TakingItEndsTheRound
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: takeCredit
WithP3Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Claim
- P2>TakeCounter:blast
- P3>TakeCounter:plan
- P3>AnswerDecision:PASS
- P4>TakeCounter:credit
## EXPECT
FOURTHCOUNTER:P4
P4CREDITCOUNT:1
PHASEISNOT:MAIN

---

# FourSeat_NoRule_TheLastSeatPassesAsBefore
#// The control: no house rule, same table — the fourth seat may only pass.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP3Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Claim
- P2>TakeCounter:blast
- P3>TakeCounter:plan
- P3>AnswerDecision:PASS
## EXPECT
TURNPLAYER:4
P4FOURTHAVAIL:0
P4PASSAVAIL:1

---

# RoundRollover_ItIsBackInTheCentre
#// Same rollover idiom as core/MultiSeat_RoundTransitionResets.md: everyone passes, then everyone declines the regroup
#// resource prompt. The new action phase puts the 4th counter back.
## GIVEN
CommonSetup3P: grw/ggk/ggk
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithHouseRules: takeCredit
WithP3Deck: SOR_095
WithP3Deck: SOR_095
WithP3Deck: SOR_095
## WHEN
- P1>TakeCounter:credit
- P2>Pass
- P3>Pass
- P1>AnswerDecision:-
- P2>AnswerDecision:-
- P3>AnswerDecision:-
## EXPECT
FOURTHCOUNTER:AVAILABLE
P1FOURTHAVAIL:1
P1CREDITCOUNT:1

---

# Elimination_ReturnsItToTheCentre
#// CR §11.3.4: an eliminated seat's counters go back to the centre (SWUEliminateSeat). Seat 2 takes the credit counter,
#// then is eliminated directly.
#// ⚠ EliminateSeat, NOT a Blast kill: in one process, a base-kill elimination run straight after
#// ConcedeEliminatesInsteadOfEndingTheGame.md loops forever — reproduced at HEAD with no house rule (P2 Claim, P3 Blast
#// kills P2). A pre-existing cross-section leak, reported, not this feature's to fix.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 2
WithHouseRules: takeCredit
## WHEN
- P2>TakeCounter:credit
- P3>EliminateSeat:2
## EXPECT
SEATLIVE:2:false
FOURTHCOUNTER:AVAILABLE

---

# Credit_WithMoffJerjerrod_TheTurnWaitsForTheTakersChoice
#// Creating the Credit token can ask its taker a question (ASH_094 Moff Jerjerrod: defeat him to create twice as many).
#// The round-ending pass must wait behind that question — passing at once handed the turn to the next seat while the
#// taker was still choosing (the owner-reported Plan bug of 2026-09-22, which SWU_PLAN_PASS fixed for Plan).
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: takeCredit
WithP1GroundArena: ASH_094:1:0
## WHEN
- P1>TakeCounter:credit
## EXPECT
FOURTHCOUNTER:P1
P1HASDECISION
TURNPLAYER:1

---

# Credit_WithMoffJerjerrod_AnsweringPassesTheTurn
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: takeCredit
WithP1GroundArena: ASH_094:1:0
## WHEN
- P1>TakeCounter:credit
- P1>AnswerDecision:NO
## EXPECT
P1CREDITCOUNT:1
P1NODECISION
TURNPLAYER:2
