#// The turn must stay with the acting player until everything their action set off has resolved. The house rule
#// is pinned in twinsuns/TurnDoesNotAdvanceWhileASeatDecides.md (reported 2026-08-26), including its two-seat
#// control, which keeps the turn on P1 while P2 assigns indirect damage.
#//
#// FIXED 2026-09-13 (all RED sections green; full suite 11895/0): _SWUCombatFinishAction (CombatLogic.php) queues
#//   the action end BEHIND the acting player's own queued RESOLVE_TRIGGER entries instead of closing inline. The
#//   other-seat case is unchanged.
#//
#// FOUND BY: sweep retro #8 of run 2 (2026-09-13): CROSS Search_top_cards ← TOPDECKSEARCH_FINALIZE (46×), LOF_057
#//   Owen Lars' When Defeated in the Maul list. Probing it without P1OnlyActions (which hides TURNPLAYER) showed
#//   the gap.
#//
#// ★ WAS RED (2 sections) — the deferral in that file is keyed on OTHER seats owing a decision. When the decision is
#//   the ACTOR'S OWN, here the When Defeated of the attacker killed by the counter-damage, nothing holds the turn:
#//   it passes to P2 while P1 is still answering their own When Defeated. Measured 2026-09-13 with a search
#//   (Owen Lars, TOPDECKSEARCH) and a yes/no (Karis, YESNO), so it is not about the decision type. Same
#//   consequence as the 08-26 report: P2's seat says it is their turn while P1 is still resolving.
#//   (A related shape was NOT written up: Owen defeated on P2's turn leaves P1 a bare "CUSTOM RESOLVE_TRIGGER|
#//   WhenDefeated" that the SchemaTest harness never drains. The sweep reached that search 46 times on the
#//   non-turn seat with no stall, so it looks like a harness limit rather than an engine one.)
#//
#// Cards: LOF_057 Owen Lars 0/3 ("When Defeated: Search the top 5 cards of your deck for a Force unit, reveal it,
#//   and draw it.") · LOF_031 Karis 2/4 seeded with 3 damage ("When Defeated: You may use the Force…") · SOR_046
#//   Consular Security Force 3/7 (the counter-damage) · SEC_028 Trayus Acolyte (Force) in the top 5.
#//
# RED_OwenAttacksAndDiesToTheCounter_HisSearchIsPending_TheTurnIsStillP1s
## GIVEN
CommonSetup: ggw/rrk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1GroundArena: LOF_057:1:0
WithP1Deck: [SOR_095 SEC_028 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DECISIONTOOLTIP:Search_top_cards
TURNPLAYER:1

---

# RED_KarisAttacksAndDiesToTheCounter_HerYesNoIsPending_TheTurnIsStillP1s
## GIVEN
CommonSetup: ggw/rrk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1Force: true
WithP1GroundArena: LOF_031:1:3
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DECISIONTOOLTIP:Use_the_Force_to_give_a_unit_-2/-2?
TURNPLAYER:1

---

# CONTROL_OwenSearchAnswered_TheDrawLands_AndTheTurnPassesOnce
## GIVEN
CommonSetup: ggw/rrk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1GroundArena: LOF_057:1:0
WithP1Deck: [SOR_095 SEC_028 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SEC_028
## EXPECT
P1HANDCOUNT:1
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION
