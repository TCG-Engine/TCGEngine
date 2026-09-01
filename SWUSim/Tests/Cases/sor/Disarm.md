# ChoosesAmongEnemies
#// COVERAGE: offer=TargetOffer_EnemyUnitsInBOTHArenas_FriendlyExcluded (decision left PENDING and the
#//           exact legal set read: 2 enemy ground + 1 enemy space in, a friendly ground unit and a
#//           friendly space unit out) ·
#//           boundary=PowerFloorAtZero + ReducesEnemyPower (the −4 straddling the power floor: 9→5 above
#//           it, 3→0 rather than −1 at it) ·
#//           reqboundary=SimulateRequestBoundary_ChosenTargetSurvives ·
#//           decline=N/A: the card has no optional clause — no printed "you may", and the target pick is
#//           a mandatory MZCHOOSE (a single legal enemy auto-resolves, see ReducesEnemyPower). The only
#//           declinable half is playing the event out of hand at all, which is the generic hidden-zone
#//           play rule and not this card's text ·
#//           control=N/A: −4/−0 is a phase effect written onto the chosen unit itself, and the pool is
#//           controller-relative — ZoneSearch('theirGroundArena'/'theirSpaceArena') recomputed at
#//           resolution, so a unit you control but do not own sits in my*, never their*, and is excluded
#//           by exactly the assertion above. No owner-relative zone (hand/deck/discard/base) is touched,
#//           so there is no field an owner≠controller split could send to the wrong seat.
#// SOR_216 Disarm — with 2 enemy units, player chooses which one is shrunk.
#// AT-AT (idx 0, 9/9) and Imperial Dark Trooper (idx 1, 3/3). Choose idx 1.
#// Only the chosen unit gets −4/−0; the other is untouched.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_216}
WithP2GroundArena: SOR_088:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_088
P2GROUNDARENAUNIT:0:POWER:9
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:POWER:0

---

# PowerFloorAtZero
#// SOR_216 Disarm — −4/−0 cannot push power below 0.
#// Battlefield Marine (3/3): power 3 − 4 floors at 0 (not −1). HP unchanged at 3.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_216}
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:3

---

# ReducesEnemyPower
#// SOR_216 Disarm — Give an enemy unit −4/−0 for this phase.
#// Single enemy unit (Blizzard Assault AT-AT, 9/9) → auto-target.
#// Power 9 − 4 = 5; HP unchanged at 9 (−0).

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_216}
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_088
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:HP:9

---

# SimulateRequestBoundary_ChosenTargetSurvives
#// SOR_216 Disarm — with two legal enemy units the "choose a unit" prompt ends the request in
#// production, so the −4/−0 phase effect is applied by a fresh process from the answer alone.
#// Mirrors ChoosesAmongEnemies with the boundary inserted before the answer.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_216}
WithP2GroundArena: SOR_088:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_088
P2GROUNDARENAUNIT:0:POWER:9
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:POWER:0

---

# TargetOffer_EnemyUnitsInBOTHArenas_FriendlyExcluded
#// SOR_216 Disarm — OFFER axis. "Give AN ENEMY UNIT −4/−0" carries two facts that answering a target can
#// never prove: the pool spans BOTH arenas (the text says "unit", not "ground unit"), and it excludes
#// every friendly body. The decision is left PENDING so the whole legal set can be read. N+1 fixture:
#// THREE legal enemy targets (2 ground + 1 space, so nothing auto-resolves), with a friendly ground unit
#// AND a friendly space unit seated as the excluded bodies; both bases are excluded by "unit".

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_216}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_088:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0
P1GROUNDARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:POWER:2
