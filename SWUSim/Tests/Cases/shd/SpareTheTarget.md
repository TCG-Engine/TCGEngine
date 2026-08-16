# BounceEnemyAndCollectBounty
#// SHD_206 Spare the Target (3-cost event, Cunning/Heroism) — "Return an enemy non-leader unit to its owner's
#// hand. Collect that unit's Bounties." The enemy SHD_095 (Bounty: Draw a card) is returned to P2's hand and
#// P1 collects its bounty, drawing a card.
#// COVERAGE: offer=Offer_EnemyNonLeaderUnitsInBothArenasOnly (pending P1SELECTABLEEXACT — a friendly unit,
#//           an enemy DEPLOYED LEADER and the bases are all outside the pool) ·
#//           boundary=BounceEnemyAndCollectBounty (a unit WITH a bounty) vs
#//           NoBounty_TheUnitStillReturnsAndNothingIsCollected (a unit WITHOUT one — the return is
#//           unconditional, the collection is not) ·
#//           control=NoBounty_TheUnitStillReturnsAndNothingIsCollected asserts the returned card lands in
#//           its OWNER's hand (P2's), not the caster's — the card changes zone across the seat boundary
#//           and the bounty reward stays with P1 ·
#//           decline=N/A (nothing on this card is a "you may"; the return and the collection are both
#//           mandatory — the YES/NO the engine raises for a bounty reward is the shared collect prompt,
#//           not a clause of SHD_206) ·
#//           reqboundary=N/A (the whole ability resolves inside one request: the target pick and the
#//           bounty reward are queued back-to-back with no state read across a serialization hop that
#//           any of these sections could observe).

## GIVEN
CommonSetup: yyw/yyw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_206
WithP2GroundArena: SHD_095:1:0
WithP1Deck: [SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1HANDCOUNT:1

---

# Offer_EnemyNonLeaderUnitsInBothArenasOnly
#// SHD_206 THE OFFER AXIS. "Return an ENEMY NON-LEADER unit" — the pool is every enemy unit in BOTH
#// arenas and nothing else. P1's own SOR_095 is on the board (friendly units are out), P2 has a ground
#// unit and a space unit (both in), and P2's leader is DEPLOYED as a ground unit (leader units are out).
#// Two legal targets keep the pick interactive, so the decision is left PENDING and the offer itself is
#// the assertion. A deployed leader seats at the END of P2's ground arena, so it is theirGroundArena-1.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SHD_206
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_027:1:0
WithP2SpaceArena: SHD_195:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# NoBounty_TheUnitStillReturnsAndNothingIsCollected
#// SHD_206 boundary partner to BounceEnemyAndCollectBounty: the return is unconditional, the collection
#// is not. SEC_080 has no Bounty at all, so the unit still goes back to its OWNER's hand (P2's, not the
#// caster's) and the ability closes with NO prompt — not a prompt with an empty reward. P1's deck is
#// seeded and left untouched, which is what proves no bounty draw slipped through.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_206
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2HANDCARD:0:SEC_080
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_206
P1NODECISION


---

# GrantedBounty_IsCollectedOnTheReturnToHand
#// SHD_206 — a Bounty fires when its unit is defeated, captured OR (here) returned to hand, and a
#// GRANTED Bounty counts the same as an innate one. The Wampa has no printed Bounty; SHD_176 Death Mark
#// grants it "Bounty - Draw 2 cards". Returning it collects that granted Bounty: P1 draws 2. The Wampa
#// goes to its OWNER's hand and the upgrade is defeated by CR 9.3 into P2's discard.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1Hand: SHD_206
WithP1Resources: 5
WithP2GroundArena: SOR_164:1:0
WithP2GroundArenaUpgrade: 0:SHD_176
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:1
P2DISCARDUNIT:0:CARDID:SHD_176

---

# TwoBounties_BothAreCollectedOnTheReturn
#// SHD_206 — CR 13.b: each Bounty is an independent ability, so a unit carrying BOTH an innate and a
#// granted Bounty yields TWO collections. SHD_195 Cartel Turncoat's printed "Bounty - Draw a card" plus
#// SHD_068 Public Enemy's granted "Bounty - Give a Shield token to a unit" both resolve: P1 draws 1 AND
#// puts a Shield on its own unit. Previously only ONE collection was ever queued.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1Hand: SHD_206
WithP1Resources: 5
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_195:1:0
WithP2GroundArenaUpgrade: 0:SHD_068
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
