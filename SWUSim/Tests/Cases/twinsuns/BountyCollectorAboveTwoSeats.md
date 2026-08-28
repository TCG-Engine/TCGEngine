# Bounty_AtFourSeats_ThePlayerWhoDEFEATEDItCollects
#// Bug report #983 (game 3608): "Guavian Antagonizer bounty seems not to give me a card."
#//
#// 3608 is a FOUR-SEAT game — its telemetry carries turns for seats 1-4 — which is the whole point of
#// the report. "Bounty" reads "your opponent collects its bounty", and that is unambiguous only at two
#// seats. Above two, SWUBountyCollector() owns the rule: the player who DEFEATED the unit collects
#// (user ruling 2026-08-23).
#//
#// The 2-player path is already guarded by shd/GuavianAntagonizer.md. NOTHING covered the 3+ seat path,
#// which is exactly where this report lands.
#//
#// SEAT 3 attacks SEAT 2's Guavian Antagonizer (SHD_134, 2/3). SOR_095 Battlefield Marine is a 3/3, so
#// 3 damage is exactly lethal and Guavian deals 2 back. Seat 3 — not seat 1, and not the controller —
#// must be offered the bounty and draw.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 3
WithInitiativePlayer: 3
WithP3GroundArena: [SOR_095:1:0]
WithP2GroundArena: [SHD_134:1:0]
WithP3Deck: [SOR_046]

## WHEN
- P3>AttackGroundArena:0:P2G0
- P3>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P3HANDCOUNT:1
P3DECKCOUNT:0
P3GROUNDARENAUNIT:0:DAMAGE:2

---

# Bounty_AtFourSeats_SeatOneDoesNotCollectSomeoneElsesKill
#// THE DISCRIMINATING NEGATIVE, and the one that cannot pass at two seats.
#//
#// The historical failure mode here is OtherPlayer(), which answers 2 for seat 1 and **1 for every
#// other seat** — so with seat 2 as the defeated unit's controller a legacy reading hands the bounty to
#// SEAT 1, who had nothing to do with the kill. Seat 3 does the killing and seat 1 is a bystander with
#// a seeded deck, so a misrouted bounty is visible as seat 1 drawing a card it should never see.
#//
#// Asserting BOTH seats is the point: "seat 3 drew" alone is satisfied by an implementation that hands
#// the card to everyone.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 3
WithInitiativePlayer: 3
WithP3GroundArena: [SOR_095:1:0]
WithP2GroundArena: [SHD_134:1:0]
WithP1Deck: [SOR_046 SOR_046]
WithP3Deck: [SOR_046]

## WHEN
- P3>AttackGroundArena:0:P2G0
- P3>AnswerDecision:YES

## EXPECT
P3HANDCOUNT:1
P3DECKCOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# Bounty_AtFourSeats_TheControllerNeverCollectsTheirOwnBounty
#// A bounty is collected by an OPPONENT of the defeated unit's controller — never by the controller
#// themselves, at any seat count. Seat 2 owns Guavian and must end with its deck and hand untouched
#// while seat 3 takes the reward.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 3
WithInitiativePlayer: 3
WithP3GroundArena: [SOR_095:1:0]
WithP2GroundArena: [SHD_134:1:0]
WithP2Deck: [SOR_046 SOR_046]
WithP3Deck: [SOR_046]

## WHEN
- P3>AttackGroundArena:0:P2G0
- P3>AnswerDecision:YES

## EXPECT
P3HANDCOUNT:1
P2DECKCOUNT:2
P2HANDCOUNT:0

---

# Bounty_AtFourSeats_TheCollectorMayDECLINE
#// Bounty is a "may" — collecting is optional, and declining must leave the deck alone rather than
#// drawing anyway or stranding the prompt. This is also the control that proves the YESNO really is
#// reaching seat 3's queue: a bounty offered to the WRONG seat would leave seat 3's NO unconsumed and
#// a decision pending.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 3
WithInitiativePlayer: 3
WithP3GroundArena: [SOR_095:1:0]
WithP2GroundArena: [SHD_134:1:0]
WithP3Deck: [SOR_046]

## WHEN
- P3>AttackGroundArena:0:P2G0
- P3>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P3HANDCOUNT:0
P3DECKCOUNT:1
P3NODECISION

---

# Bounty_AtFourSeats_AttackerDiesToTheCounter_TheDEFENDERCollects
#// ★ THE BRANCH THE OTHER SECTIONS CANNOT REACH, and the one that fits this card's actual job.
#// Guavian Antagonizer has SABOTEUR, so it is a unit you ATTACK with — which means the common way it
#// dies is to the defender's counter-damage, during ITS OWN CONTROLLER'S action.
#//
#// That inverts the collector rule's first test: activePlayer == defeatedController, so "the player who
#// defeated it" cannot simply be the active seat (that is the controller, who may never collect their
#// own bounty). SWUBountyCollector falls through to SWUCurrentDefendingSeat — the seat that was being
#// attacked — and that fall-through is reached by no other section here.
#//
#// Seat 2 swings Guavian (2/3) into seat 3's SOR_046 Consular Security Force (3/7): the counter deals 3
#// and kills Guavian outright, while SOR_046 survives on 2 damage. Seat 3 collects.
#// ⚠ Note the collector is NOT the acting player, so this also exercises a bounty prompt landing on a
#// seat that is not otherwise taking an action.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2GroundArena: [SHD_134:1:0]
WithP3GroundArena: [SOR_046:1:0]
WithP1Deck: [SOR_046 SOR_046]
WithP3Deck: [SOR_046]

## WHEN
- P2>AttackGroundArena:0:P3G0
- P3>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P3GROUNDARENAUNIT:0:DAMAGE:2
P3HANDCOUNT:1
P3DECKCOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:2
