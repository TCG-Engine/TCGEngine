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
