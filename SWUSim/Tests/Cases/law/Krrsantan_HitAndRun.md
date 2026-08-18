# ActionDiscardTwoBounce
#// LAW_084 Krrsantan (7/7, Ambush, Overwhelm) — Action [discard 2 cards from your hand]: return this
#// unit to your hand. Discard SEC_080 + SOR_237; Krrsantan returns to hand.

## GIVEN
CommonSetup: ryk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_084:1:0
WithP1Hand: SEC_080
WithP1Hand: SOR_237

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:2

---

# ActionUnavailableNotEnoughCards
#// LAW_084 — the return Action costs [discard 2 cards]. With only 1 card in hand the cost cannot be paid,
#// so the ability is unavailable and Krrsantan stays in the ground arena.

## GIVEN
CommonSetup: ryk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_084:1:0
WithP1Hand: SEC_080

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_084
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1

---

# ReturnToOwnersHandWhenOpponentControlled
#// LAW_084 Krrsantan — "Action [discard 2]: Return this unit to your hand" returns it to its OWNER's hand,
#// even when an opponent controls it. P1 controls a Krrsantan OWNED by P2 (the end state after a Change of
#// Heart / control-take, seated directly via WithP1GroundArenaControlled). P1 uses the action, discards 2,
#// and Krrsantan returns to P2's hand (not P1's).

## GIVEN
CommonSetup: grw/rrk/{}
P1OnlyActions: true
WithP1GroundArenaControlled: LAW_084:2
WithP1Hand: [SOR_095 SOR_128]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:1
P2HANDCARD:0:LAW_084

---

# ReturnedToHand_HisUpgradesAreLeftBehindInTheDiscard
#// LAW_084 Krrsantan — the Action returns the UNIT to hand, and an upgrade cannot follow a card out of the
#// arena, so any upgrade on him is defeated in the process. Krrsantan wears an Academy Training when he
#// bounces: he goes to hand, the upgrade goes to the discard alongside the two cards spent on the cost, and
#// nothing is left in the arena. None of the existing sections puts an upgrade on him.

## GIVEN
CommonSetup: ryk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_084:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1Hand: [SEC_080 SOR_237]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:LAW_084
P1DISCARDCOUNT:3
