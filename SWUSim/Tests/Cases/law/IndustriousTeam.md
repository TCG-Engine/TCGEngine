# DefeatLowHpUnit
#// LAW_124 Industrious Team (4/7, cost 8) — When Played: you may defeat a non-leader unit with 4 or less
#// remaining HP. SEC_080 (3/3, remaining 3) qualifies; the Team (7 HP) does not.
#// COVERAGE: offer=WhenPlayed_OfferLowRemainingHpNonLeadersOnly (pending SELECTABLEEXACT across both
#//           arenas + both sides) · decline=WhenPlayed_DeclineDefeat · boundary=remaining-HP 4-in/5-out
#//           pair inside WhenPlayed_OfferLowRemainingHpNonLeadersOnly (damaged SOR_164 in, undamaged
#//           SOR_164 out; deployed damaged leader excluded as leader) · control=N/A (one-shot defeat
#//           keyed off remaining HP; no controller-sensitive wording) · reqboundary=offer section holds
#//           the choice PENDING across the end-state read (serialized-decision path)

## GIVEN
CommonSetup: bbw/bgw/{myResources:8}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_124

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:LAW_124

---

# WhenPlayed_OfferLowRemainingHpNonLeadersOnly
#// Intended: the offer is exactly the non-leader units with 4 or less REMAINING HP, from both arenas
#// and both sides. In: friendly SOR_128 (3/1), enemy SEC_080 (3/3), enemy SOR_164 with 1 damage
#// (5 HP - 1 = 4 remaining, the inclusive boundary), enemy SOR_237 (2/3, space). Out: undamaged enemy
#// SOR_164 (5 remaining, one over the boundary), friendly LOF_069 (2/7, space), LAW_124 itself (7 HP),
#// and P2's deployed Vader leader unit at 5 damage (3 remaining — excluded as a LEADER, not by HP).
#// The decision is left PENDING so the EXPECT reads the live offer.

## GIVEN
CommonSetup: bbw/bgw/{myResources:8; theirLeader:SOR_010:1:1:0:5}
WithP1GroundArena: SOR_128:1:0
WithP1SpaceArena: LOF_069:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_164:1:1 SOR_164:1:0]
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_124

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# WhenPlayed_DeclineDefeat
#// Intended: the defeat is "you may" — declining leaves every unit in play. Two legal targets are
#// seeded (friendly SOR_128, enemy SEC_080) so the offer genuinely prompts instead of auto-resolving.

## GIVEN
CommonSetup: bbw/bgw/{myResources:8}
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_124

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:1
P1DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SEC_080

---

# WhenPlayed_DefeatFriendlyUnit
#// Intended: a FRIENDLY qualifying unit is a legal choice — defeating own SOR_128 (3/1) sends it to
#// P1's discard while the enemy SEC_080 stays.

## GIVEN
CommonSetup: bbw/bgw/{myResources:8}
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_124

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_128
P2GROUNDARENACOUNT:1
