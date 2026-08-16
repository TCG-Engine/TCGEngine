# ExhaustCheapEnemyDiscard
#// LAW_075 Interrogation Droid (3/1) — When Played: exhaust an enemy unit. If you do and that unit costs
#// 3 or less, its controller discards a card. SEC_080 (cost 2) -> exhausted -> P2 discards (2 cards -> picks).

## GIVEN
CommonSetup: ryk/bgw/{myResources:2}
WithActivePlayer: 1
WithP2GroundArena: SEC_080:1:0
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP1Hand: LAW_075

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:1

---

# NoDiscardIfAlreadyExhausted
#// LAW_075 Interrogation Droid — When Played: target an already-exhausted enemy unit (SHD_029 Pyke
#// Sentinel, cost 2). Exhausting does nothing (already exhausted) -> "if you do" fails -> no discard.

## GIVEN
CommonSetup: ryk/bgw/{myResources:2}
WithActivePlayer: 1
WithP2GroundArena: SHD_029:0:0
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP1Hand: LAW_075

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_029
P2GROUNDARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:2

---

# NoDiscardIfCostAboveThree
#// LAW_075 Interrogation Droid — When Played: exhaust an enemy unit costing more than 3 (SOR_164 Wampa,
#// cost 4) -> exhausted but NO discard.

## GIVEN
CommonSetup: ryk/bgw/{myResources:2}
WithActivePlayer: 1
WithP2GroundArena: SOR_164:1:0
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP1Hand: LAW_075

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:2

---

# NoEnemyUnitsNothingHappens
#// LAW_075 Interrogation Droid — When Played with no enemy units: nothing to exhaust, no discard.

## GIVEN
CommonSetup: ryk/bgw/{myResources:2}
WithActivePlayer: 1
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP1Hand: LAW_075

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:2
P1GROUNDARENAUNIT:0:CARDID:LAW_075

---

# ExhaustPool_EnemyOnlyIncludingDeployedLeaderAndAlreadyExhausted
#// COVERAGE: offer=ExhaustPool_EnemyOnlyIncludingDeployedLeaderAndAlreadyExhausted (the "an enemy unit"
#//           pool asserted exactly: friendly excluded, space included, deployed leader included, an
#//           already-exhausted enemy included); offer-absence = NoEnemyUnitsNothingHappens · decline=N/A
#//           (mandatory choose, no "you may") · control=N/A (no control-change text) ·
#//           boundary=ExhaustCheapEnemyDiscard (cost 2 -> discard) vs NoDiscardIfCostAboveThree (cost 4 ->
#//           no discard), and NoDiscardIfAlreadyExhausted (the "if you do" gate fails) ·
#//           reqboundary=ExhaustCheapEnemyDiscard (P1's play queues P2's discard pick in a later request).
#// LAW_075 Interrogation Droid — "When Played: Exhaust AN ENEMY UNIT." The ONLY restriction word is
#// "enemy": there is no arena word, no non-leader word, and no "ready" word. The board makes each of those
#// three absences observable — P1's own SOR_046 must be OUT (controller scope); P2's SPACE SOR_225 must be
#// IN (no arena scope); P2's DEPLOYED LEADER at theirGroundArena-2 must be IN (contrast Double-Cross and
#// Maul, which say "non-leader"); and the already-EXHAUSTED SEC_080 at theirGroundArena-1 must ALSO be IN,
#// because this card's "If you do" rider is what handles a failed exhaust (see NoDiscardIfAlreadyExhausted)
#// rather than the pool pre-filtering the target away. ⚠ That last point is a deliberate divergence from
#// the ready-only friendly pool ruled for LAW_226 Secret Battle of Pretend: there the failed exhaust has no
#// rider to observe it, here it does, so an exhausted target is a meaningful (losing) choice.

## GIVEN
CommonSetup: ryk/bgw/{myResources:2;theirLeaderDeployed:true}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_095:1:0 SEC_080:0:0]
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_075

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:2:ISLEADERUNIT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2&theirSpaceArena-0
