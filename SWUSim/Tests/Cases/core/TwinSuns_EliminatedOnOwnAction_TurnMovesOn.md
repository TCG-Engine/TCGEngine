#// CORE — a seat eliminated DURING ITS OWN ACTION must not keep the turn.
#//
#// Found 2026-10-01 by the Twin Suns self-play harness (DevTools/SWUSimTwinSunsSelfPlay.php, 3 seats,
#// seed ts-19): P1 at 1 HP (30-HP red base, 29 damage) played TWI_146 Steela Gerrera and took her "you may deal 2 damage to your
#// base". That eliminated P1 mid-action. Elimination empties the seat's decision queue — including the
#// rest of the action and its close — so nothing ever swapped the turn, and the table sat on a dead seat
#// with nobody able to act. A human taking the same option would freeze the game the same way.
#//
#// ⚠ THREE SEATS, and TURNPLAYER pinned to the EXACT next seat (see core/ExhaustSkipsNextPlayer*): a
#// double swap would land on P3 and skip P2.
# SelfDamageElimination_TurnPassesToNextLiveSeat
## GIVEN
CommonSetup3P: rrw/bbk/bbk/{myBaseDamage:29; myResources:4; handCardIds:TWI_146}
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
SEATLIVE:1:false
TURNPLAYER:2

---

# SelfDamageElimination_NextSeatCanAct
#// The table is really unstuck: P2 takes an action (plays a unit) after the elimination.
## GIVEN
CommonSetup3P: rrw/bbk/bbk/{myBaseDamage:29; myResources:4; handCardIds:TWI_146}
SkipPreGame: true
WithActivePlayer: 1
WithP2Resources: 8
WithP2Hand: [SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>PlayHand:0
## EXPECT
SEATLIVE:1:false
P2GROUNDARENACOUNT:1
TURNPLAYER:3

---

# Control_SurvivingTheDamage_NormalSwap
#// Control: at 3 HP the same play leaves P1 alive at 1, and the action closes the ordinary way.
## GIVEN
CommonSetup3P: rrw/bbk/bbk/{myBaseDamage:27; myResources:4; handCardIds:TWI_146}
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
SEATLIVE:1:true
P1BASEDMG:29
TURNPLAYER:2
