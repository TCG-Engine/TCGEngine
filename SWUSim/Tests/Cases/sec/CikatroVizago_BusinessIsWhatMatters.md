# OnAttack_OpponentDeclines_Draw
#// SEC_218 Cikatro Vizago (Ground, 3/4) — On Attack: reveal the top card of your deck. An opponent may
#//   pay 1 resource. If they don't, draw that card. P2 declines → P1 draws the revealed card.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_218:1:0
WithP1Deck: SOR_095
WithP2Resources: 3

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:NO

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1

---

# OnAttack_OpponentPays_NoDraw
#// SEC_218 — if the opponent pays 1 resource, the attacker does NOT draw the revealed card (it stays on
#//   top of the deck). P2 pays → P1 hand stays empty, deck still holds the card.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_218:1:0
WithP1Deck: SOR_095
WithP2Resources: 3

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:0
P1DECKCOUNT:1

---

# OnAttack_EmptyDeck_Skipped
#// SEC_218 — with an empty deck there is no card to reveal, so the On Attack ability is skipped entirely
#//   (no reveal, no opponent prompt). The attack just deals its combat damage.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_218:1:0
WithP2Resources: 3

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:0
P1NODECISION
P2NODECISION

---

# OnAttack_OpponentCannotPay_AutoDraw
#// SEC_218 — if the opponent has no ready resources they cannot pay the 1, so no choice is offered and
#//   the attacker draws the revealed card automatically (no phantom YESNO the opponent can't act on).

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_218:1:0
WithP1Deck: SOR_095
WithP2Resources: 0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1
P1NODECISION
P2NODECISION

---

# OnAttack_OpponentPaysWithACreditToken
#// SEC_218 Cikatro Vizago — "an opponent may pay 1 resource" is an ordinary cost, so a Credit token pays
#// it just like a ready resource. P2 holds NO ready resources but one Credit; P2 spends the Credit and
#// P1 does not draw the revealed card.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_218:1:0
WithP1Deck: SOR_095
WithP2Resources: 3:SOR_046:0
WithP2Credits: 1

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:0
P2CREDITCOUNT:0

---

# TwinSuns_TheBROKESeatStaysInThePicker
#// ⚠ THE ELIGIBILITY CELL — added 2026-08-24. Asserts the MENU.
#// "AN opponent may pay 1 resource. If they don't, draw that card." TWO QUESTIONS THE OLD CODE CONFLATED:
#//   • WHO may be picked → EVERY live opponent. Whichever seat you name the ability fully resolves (they
#//     pay, or you draw). Naming a BROKE opponent GUARANTEES the draw — a materially different play from
#//     naming a rich one, so filtering to "opponents who can pay" would delete the most reliable line.
#//   • IS THE CHOICE MEANINGFUL → only if at least ONE opponent can pay. If nobody can, every answer
#//     collapses to "the attacker draws" — genuinely degenerate, so no prompt is raised at all.
#// Seats 2 and 3 have resources; SEAT 4 HAS NONE and must still be offered.
#// Mutation check: filter $eligible to payers and P1OPTIONHAS:P4 reds.

## GIVEN
CommonSetup: yyk/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1GroundArena: SEC_218:1:0
WithP1Deck: [SOR_095 SOR_046]
WithP2Resources: 3
WithP3Resources: 3
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>AttackGroundArena:0:P3B

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P1
