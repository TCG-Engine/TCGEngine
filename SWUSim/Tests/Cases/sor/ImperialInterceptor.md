# Deals3ToSpace
#// SOR_132 Imperial Interceptor (Space, cost 4) — When Played: you may deal 3 to a
#// space unit. P2's Restored ARC-170 (SOR_044, 2/3, space) is chosen and takes 3 → defeated.
#// COVERAGE: offer=Offer_SpaceUnitsOnly_IncludingSelf (pending SELECTABLEEXACT: every space
#//           unit both sides INCLUDING the Interceptor itself; ground units excluded) ·
#//           reqboundary=Deals3ToSpace (the target answer arrives in a separate request from
#//           the play) · control=Deals3ToSpace (the pool crosses the seat line and resolves
#//           onto an enemy unit) · boundary pair=Deals3ToSpace (3 ≥ 3 HP → defeat) +
#//           SelfTarget_DefeatsItself (3 vs its own 2 HP) · decline=DeclinesTheDamage
#//           ("you may" answered '-')

## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:SOR_132}
P1OnlyActions: true
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0

---

# Offer_SpaceUnitsOnly_IncludingSelf
#// Intended: "a space unit" — the pool is every unit in the space arena on BOTH sides,
#// including the just-played Interceptor itself; ground units on either side are excluded.
#// Decision left pending so the exact pool can be inspected.

## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:SOR_132}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0     # friendly space — idx 0 (Interceptor seats at idx 1)
WithP1GroundArena: SOR_095:1:0    # friendly ground — excluded
WithP2SpaceArena: SOR_225:1:0     # enemy space — included
WithP2GroundArena: SOR_046:1:0    # enemy ground — excluded

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:mySpaceArena-0&mySpaceArena-1&theirSpaceArena-0

---

# SelfTarget_DefeatsItself
#// Intended: the Interceptor is a legal target for its own When Played — picking itself
#// deals 3 to its 2 HP and it is defeated straight to the discard.

## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:SOR_132}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1

---

# DeclinesTheDamage
#// "You MAY deal 3" — the trigger can be declined ('-'): no unit anywhere takes damage and
#// the Interceptor stays in play.

## GIVEN
CommonSetup: rrk/rrk/{myResources:4;handCardIds:SOR_132}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:1:DAMAGE:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0
