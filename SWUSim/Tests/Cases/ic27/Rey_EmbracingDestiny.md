# Action_DiscardThenReyAttacksWithPlusTwo
#// IC27_120 Rey (Ground 6/6, cost 5, [Aggression][Heroism]) — "Action [1 resource, discard a card from your hand]:
#// Attack with a unit. It gets +2/+0 for this attack." The cost is 1 resource + a discard (the lone hand card
#// auto-resolves); the only unit that can attack is Rey herself, and she hits P2's base for 6 + 2 = 8.
#// "for this attack": her power reads 6 afterwards.
#// Fixture: CommonSetup rrw = Aggression base + Aggression/Heroism leader.
#// COVERAGE: offer=Offer_AttackerPool_UnitsThatCanAttackNow (pending SELECTABLEEXACT: Rey, another ready unit, a
#//           space unit; an exhausted unit and the enemy excluded) · decline=N/A (the attack is mandatory and the
#//           cost is a cost — the only "no" is not activating) · boundary=CannotPay_EmptyHand /
#//           CannotPay_NoResource (each half of the cost at zero) · control=N/A (Rey's ability is used by her
#//           controller on that player's units; no owner-scoped zone) ·
#//           reqboundary=RequestBoundary_AttackerPickInAFreshRequest
#//           modes=2P only ("attack with a unit" is always a unit you control; no friendly/enemy wording)

## GIVEN
CommonSetup: rrw/rrk/{myResources:1;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: IC27_120:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2BASEDMG:8
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:6
P1NODECISION
NOEXTRAACTION

---

# Offer_AttackerPool_UnitsThatCanAttackNow
#// The attacker pick is left PENDING. Offered: Rey, the ready Battlefield Marine and the ready X-Wing (space).
#// Excluded: the exhausted Imperial Dark Trooper, and P2's units (not yours).

## GIVEN
CommonSetup: rrw/rrk/{myResources:1;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: IC27_120:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:0:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# AnotherUnitAttacks_ReyStaysReady_ThenUsesItAgain
#// No [Exhaust] in the cost: Rey stays READY after activating, and the action can be used again. First the Marine
#// attacks for 3 + 2 = 5; then, with the Marine exhausted, Rey is the only attacker and hits for 6 + 2 = 8. Two
#// resources and two cards are spent. The Marine's bonus was for that attack only (power 3 afterwards).

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;myhandCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP1GroundArena: IC27_120:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-1
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2BASEDMG:13
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1DISCARDCOUNT:2
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# CannotPay_EmptyHand_ActionUnavailable
#// The discard is part of the cost: with no card in hand the action cannot be used — nothing is paid, nobody
#// attacks.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: IC27_120:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2BASEDMG:0
P1RESAVAILABLE:1
P1GROUNDARENAUNIT:0:READY
P1NODECISION

---

# CannotPay_NoResource_ActionUnavailable
#// No ready resource and no Credit: the action cannot be used — the hand card stays.

## GIVEN
CommonSetup: rrw/rrk/{myResources:0;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: IC27_120:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2BASEDMG:0
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1NODECISION

---

# ACreditPaysTheResource
#// A Credit token pays an ability's resource cost (CR 3.13): 0 ready resources + 1 Credit, and the action resolves.

## GIVEN
CommonSetup: rrw/rrk/{myResources:0;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: IC27_120:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1CREDITCOUNT:0
P2BASEDMG:8
P1HANDCOUNT:0

---

# NoUnitCanAttack_CostIsStillPaid_ActionResolvesToNothing
#// Rey is exhausted and so is the only other unit: nothing can attack. The cost changes the game state, so the action
#// is still usable — the resource and the card are spent and the turn passes (no attack).

## GIVEN
CommonSetup: rrw/rrk/{myResources:1;myhandCardIds:SOR_095}
WithActivePlayer: 1
WithP1GroundArena: IC27_120:0:0
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2BASEDMG:0
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
TURNPLAYER:2
P1NODECISION
NOEXTRAACTION

---

# AlternatingTurn_TheAttackClosesTheActionOnce
#// Without P1OnlyActions: Rey's action and its attack are ONE action — the turn passes to P2 exactly once.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1;myhandCardIds:SOR_095}
WithActivePlayer: 1
WithP1GroundArena: IC27_120:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2BASEDMG:8
TURNPLAYER:2
NOEXTRAACTION

---

# RequestBoundary_AttackerPickInAFreshRequest
#// Two attackers (Rey, the Marine) and two hand cards: both picks are answered after a fresh request. The Marine
#// attacks for 5.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1;myhandCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP1GroundArena: IC27_120:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-1
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:5
P1DISCARDUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY

---

# BlankedRey_ActionUnavailable
#// Rey under SOR_138 Force Lightning ("loses all abilities this phase") has no Action to use.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: IC27_120:1:0:SOR_138

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2BASEDMG:0
P1RESAVAILABLE:1
P1HANDCOUNT:1
P1NODECISION
