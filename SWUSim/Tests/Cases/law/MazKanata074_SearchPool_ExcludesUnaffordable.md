# LAW_074 Maz Kanata — "When Attack Ends: if this unit survived, search the top 5 for an Underworld unit
# and play it. It costs 4 resources less…" Same class of bug as Kelleran Beq (LOF_100): the offered pool
# included Underworld units the player couldn't afford at the −4 price, so picking one just fizzled (the
# resolve returns it to the deck bottom). The playable set must exclude unaffordable units.
#
# Maz (pre-placed, ready) attacks the enemy base and survives → her ability searches the top 5. P1 has 0
# resources, so only a net-0 unit is playable:
#   - LAW_257 Hidden Hand Supplier — cost 1 (neutral) → max(0, 1−4) = 0 net → affordable, MUST be offered.
#   - LAW_262 Bank Job Fugitives — cost 6 (neutral) → max(0, 6−4) = 2 net → UNaffordable, must NOT be offered.
# Both neutral → no aspect penalty, isolating the cost check. Decision left pending to read the offer.

## GIVEN
CommonSetup: ggw/ggw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LAW_074:1:0
WithP1Deck: LAW_257
WithP1Deck: LAW_262

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:LAW_257
P1SEARCHPLAYABLENOT:LAW_262
