# WhenPlayed_LookAtHand
#// SEC_239 Viper Probe Droid (Ground, 3/2, cost 2) — When Played: look at an opponent's hand.
#//   Informational only — the card enters play and the opponent's hand is unchanged.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP2Hand: SOR_095
WithP1Hand: SEC_239

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:1

---

# WhenPlayed_ACTUALLYSHOWSTheOpponentsHand
#// BUG #1028 (game 4186): "Viper Probe not doing anything."
#//
#// SEC_239 is a REPRINT of SOR_228 and the two printings had DIFFERENT implementations. SOR_228 does the
#// real work — pick an opponent, log the look, and present their hand as an acknowledge popup. SEC_239's
#// handler was a stub that only wrote a game-log line:
#//     AddGameLogEntry('ABILITY', "P… looked at an opponent's hand");
#// so the player who played it saw nothing at all. The card's ONLY printed effect is the look, so a
#// missing reveal is the whole card — hence "not doing anything", which is an exact description.
#//
#// ⚠ WHY THE EXISTING SECTION ABOVE COULD NOT CATCH IT. It asserts that the unit enters play and that
#// the opponent's hand is unchanged — both true of a card that does NOTHING. A "look" has no game-state
#// footprint, so the only observable is the POPUP, and the section never looked for one. This is the
#// documented trap for this whole family: the guard has to leave the popup PENDING and assert its
#// tooltip. Answering it with `AnswerDecision:OK` instead makes a section merely TOLERATE the popup —
#// an OK against no pending decision is silently absorbed, so such a section stays green with the
#// reveal deleted.
#//
#// ⚠ P2 HOLDS TWO CARDS, not one. The popup no-ops on an empty hand, and a one-card fixture is the
#// weakest form of this family's other trap.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_239
WithP2Hand: SOR_095
WithP2Hand: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
#// The reveal popup is PENDING and unanswered — that is the assertion the bug fails.
P1HASDECISION
P1DECISIONTOOLTIP:Opponent's_hand
#// …and the look is informational only: nothing is discarded, the unit is on the board.
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_239
P2HANDCOUNT:2
P2DISCARDCOUNT:0
LOGCONTAINS:looked at

---

# WhenPlayed_AcknowledgeClearsIt
#// The popup is an acknowledge, not a choice: answering OK closes it and leaves no dangling decision,
#// and still nothing has changed on either board. Mirrors sor/ViperProbeDroid.md::WhenPlayed_LookAtHand
#// so the two printings are held to the SAME behaviour — the divergence is what caused the bug.
## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_239
WithP2Hand: SOR_095
WithP2Hand: SEC_080
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:OK
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:0

---

# EmptyEnemyHand_NoPopupAndNoDanglingDecision
#// THE BOUNDARY. With nothing to look at the unit must still play cleanly and raise NO popup — a
#// decision left hanging here would stall the turn. Same edge sor/ViperProbeDroid.md pins for SOR_228.
## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_239
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_239
P2HANDCOUNT:0

---

# Bug1028_TheReportedBoard_Game4186
#// THE ACTUAL REPORTED BOARD (game 4186), trimmed to what the clause touches. The sections above pin the
#// fix on a minimal board; this one pins it on the board the player was actually looking at when they
#// wrote "Viper Probe not doing anything".
#// P1 holds SEC_239 with 2 ready resources; P2 holds THREE cards (LOF_091, ASH_097, ASH_049), so the
#// popup has real content and the count is worth asserting — a reveal that showed the wrong hand, or a
#// truncated one, would not be caught by a 2-card fixture.
#// ⚠ Left PENDING on purpose, for the same reason as the first new section: an answered popup only
#// proves the section TOLERATES one.
## GIVEN
CommonSetup: ngw/ngw/{
  myLeader:LOF_001:true:false:true:0;
  myBase:JTL_024;
  myBaseDamage:19;
  theirLeader:LAW_008:true:false:true:0;
  theirBase:SEC_019;
  theirBaseDamage:5;
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2:SOR_095:1,7:SOR_095:0
WithP1Hand: [SEC_239 ASH_048]
WithP2Hand: [LOF_091 ASH_097 ASH_049]
WithP2GroundArena: [ASH_079:0:0]
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Opponent's_hand
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_239
#// informational only — the three cards stay exactly where they were
P2HANDCOUNT:3
P2DISCARDCOUNT:0
