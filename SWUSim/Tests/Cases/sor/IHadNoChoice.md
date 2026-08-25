# NoUnits_Fizzle
#// COVERAGE: offer=Offer_BothSidesNonLeader_DeployedLeaderExcluded (pending SELECTABLEEXACT: both
#//           sides in, leader unit out) · decline=ChooseNothing_NothingHappens ("up to 2" includes
#//           zero) · boundary=OneUnit_ReturnsToHand (forced single: hand, no bury) + TwoUnits_
#//           OpponentChoosesOne (hand + bottom split) · control=MixedPick_BuriedFriendlyUnitDrops
#//           UpgradeToOwnersDiscard (opponent's pick decides the fate of the CASTER's own unit;
#//           upgrade falls to its owner's discard) · reqboundary=TwoUnits_OpponentChoosesOne (the
#//           cross-player second pick resolves on a separate serialized answer)
#// SOR_187 I Had No Choice — with no non-leader units in play the event fizzles cleanly (no decision)
#// and goes to the discard.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_187
WithP1Resources: 9

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1NODECISION

---

# OneUnit_ReturnsToHand
#// SOR_187 I Had No Choice — when the caster chooses only ONE unit, the opponent's "choose 1 of those"
#// is forced and there is no "other," so that unit just returns to its owner's hand (no deck-bottom).
#// P1 picks SEC_080; it returns to P2's hand, SOR_128 stays in play.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_187
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2HANDCOUNT:1
P2DECKCOUNT:1
P1DISCARDCOUNT:1

---

# TwoUnits_OpponentChoosesOne
#// SOR_187 I Had No Choice (event, cost 7) — "Choose up to 2 non-leader units. An opponent chooses 1
#// of those units. Return that unit to its owner's hand and put the other on the bottom of its owner's
#// deck." P1 picks both of P2's units; P2 chooses which is saved to hand (myGroundArena-0 = SEC_080),
#// so the other (SOR_128) is buried on the bottom of P2's deck. SOR_002 covers Villainy only, so the
#// Cunning aspect adds +2 (cost 9) — WithP1Resources:9.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_187
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2DECKCOUNT:2
P1DISCARDCOUNT:1

---

# Offer_BothSidesNonLeader_DeployedLeaderExcluded
#// SOR_187 I Had No Choice — "Choose up to 2 NON-LEADER units": the pick pool spans BOTH sides
#// (P1's own trooper is choosable) and excludes P2's deployed leader unit. The multi-pick is left
#// PENDING to pin the offer.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021;
  theirLeader:SOR_010:1:1
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_187
WithP1Resources: 9
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# ChooseNothing_NothingHappens
#// SOR_187 I Had No Choice — "up to 2" includes ZERO: declining the pick leaves every unit where
#// it was; no opponent choice fires; the event still goes to the discard.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_187
WithP1Resources: 9
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
P2NODECISION

---

# MixedPick_BuriedFriendlyUnitDropsUpgradeToOwnersDiscard
#// SOR_187 I Had No Choice — a FRIENDLY unit may be chosen, and a unit put on the bottom of its
#// owner's deck sheds its non-token upgrades to their owner's discard. P1 picks his own upgraded
#// trooper plus P2's SOR_128; P2 saves its OWN unit to hand, so P1's SEC_080 (wearing SHD_177) is
#// buried: P1's deck grows to 2 with the seeded SOR_095 still on TOP (the trooper went to the
#// bottom), and SHD_177 lands in P1's discard next to the spent event.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_187
WithP1Resources: 9
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: SOR_128:1:0
WithP1Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:2

---

# TwinSuns_CasterChoosesWhichOpponentDecides
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. "Choose up to 2 non-leader units. AN OPPONENT chooses 1 of
#// those units." OFFICIAL RULING (03/01/2024): "If there are multiple opponents, the controlling player
#// chooses which one will be 'an opponent.'"
#// ⚠ NO $eligible filter: the chosen opponent is only asked to pick between two units already named on
#// the table — nothing about their own board can make them unable to choose (taxonomy shape 3, same as
#// LOF_065 Watto). Note the units picked may not even belong to the deciding opponent.
#// P1 names two units and hands the decision to SEAT 3. SEAT 3 must own the choice; seat 2 — whom the old
#// code always asked — must have none.
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no choice to get wrong.
#// Mutation check: revert to OtherPlayer($caster) and this reds (the choice lands on seat 2).

## GIVEN
CommonSetup: bbk/brw/{myBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: SOR_187
WithP1Resources: 9
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-0&p3GroundArena-0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3HASDECISION
P2NODECISION
