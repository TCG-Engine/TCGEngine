# RevealNonUnitExp
#// LAW_115 Rickety Quadjumper (1/3, space) — On Attack: you may reveal the top card of your deck. If
#// it's not a unit, give an Experience token to another unit (left on top). Top is SOR_251 (event) ->
#// Experience to SOR_095.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_115:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_251

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DECKCOUNT:1

---

# OfferPool_AnotherUnitEitherSide
#// LAW_115 Rickety Quadjumper — offer assertion for "give an Experience token to ANOTHER unit". The only
#// printed restriction is "another": no controller word and no arena word, so every unit in play except
#// the Quadjumper itself is legal. Discriminating board — the Quadjumper (mySpaceArena-0) must be OUT,
#// while a second friendly SPACE unit, a friendly GROUND unit, an enemy GROUND unit and an enemy SPACE
#// unit must all be IN. That is the shape that catches a pool silently narrowed to "another FRIENDLY
#// unit" (a very common mis-read of this wording) or to the source's own arena. The top of P1's deck is
#// Confiscate (an Event, so the "if it's not a unit" gate passes and the card stays on top); the
#// Experience pick is left UNANSWERED so the pending pool can be read.
#// COVERAGE: offer=OfferPool_AnotherUnitEitherSide (pending SELECTABLEEXACT; self is the "out", both
#//           sides x both arenas are the "in") · reqboundary=NOT COVERED (the source UID is passed in
#//           the CUSTOM handler's params, so it does survive the YESNO boundary, but no section forces a
#//           SimulateRequestBoundary across it) · control=N/A (Experience is a one-shot token grant) ·
#//           boundary pair=RevealNonUnitExp (non-unit on top → Experience granted) vs NOT COVERED for
#//           the unit-on-top negative · decline=NOT COVERED (the reveal is a YESNO "you may"; no NO
#//           branch section exists yet)

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_115:1:0
WithP1SpaceArena: SOR_178:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Deck: SOR_251

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-1&theirGroundArena-0&theirSpaceArena-0
P1SPACEARENAUNIT:0:CARDID:LAW_115
P1DECKCOUNT:1

---

# RevealedCardIsAUNIT_NoExperience
#// LAW_115 Rickety Quadjumper — "If it's NOT a unit, give an Experience token to another unit". This is the
#// negative half of that condition and the only section that exercises it: the top card is SOR_095
#// Battlefield Marine, a unit, so no token is created, no target is offered, and the revealed card stays
#// on top of the deck. RevealNonUnitExp reveals an event and hands out the token; without this pair the
#// condition itself is untested.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_115:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_095

---

# DeclineTheReveal_DeckUntouchedAndNoToken
#// LAW_115 Rickety Quadjumper — "You MAY reveal", so declining is a complete resolution: nothing is
#// revealed, no Experience is given, and the deck is left exactly as it was. The attack itself still
#// happens (3 damage to the enemy base from the 1-power Quadjumper plus nothing else — asserted so a
#// decline that aborted the whole attack would be visible).

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_115:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_251

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_251
P2BASEDMG:1
