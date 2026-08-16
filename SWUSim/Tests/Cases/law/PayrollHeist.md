# GrantOnAttackCredit
#// LAW_169 Payroll Heist (Command event, cost 4) — "For this phase, each friendly unit gains: On Attack:
#// Create a Credit token." After playing it, SOR_095 attacks the base and creates a Credit token.

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_169

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
P2BASEDMG:3

---

# GrantsAllFriendlyUnits_GroundAndSpace
#// LAW_169 Payroll Heist — the buff hits EVERY friendly unit (both arenas). A ground unit (SOR_095) and a
#// space unit (SOR_237) each attack the base and each creates a Credit token → 2 Credits. Base takes
#// 3 (ground) + 2 (space) = 5.

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1Hand: LAW_169

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1CREDITCOUNT:2
P2BASEDMG:5

---

# NoUnitsNoEffectStillPlays
#// LAW_169 Payroll Heist — with no friendly units in play there is nothing to grant the ability to; the
#// event still resolves with no effect and goes to the discard pile. No Credit tokens are created.

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
P1OnlyActions: true
WithP1Hand: LAW_169

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1CREDITCOUNT:0

---

# GrantSurvivesTheRequestBoundary
#// LAW_169 Payroll Heist — request-boundary guard. Payroll Heist's whole effect is phase-scoped state
#// ("For this phase, each friendly unit gains: On Attack: Create a Credit token"), so it must live in the
#// serialized gamestate: in a real game every answer starts a fresh process. LAW_228 Canyon Frontrunner
#// is used as the attacker purely because its own On Attack leaves a genuine pending pick
#// (myGroundArena-0 & theirGroundArena-0) to hang the boundary on. Canyon attacks (Credit #1), the game
#// round-trips through serialization with Canyon's pick still open, the pick is answered, and THEN a
#// second friendly unit attacks — its Credit (#2) can only be created if the grant survived the
#// round-trip. 2 Credits, and Canyon's -2/-0 still lands on SOR_046 (3 -> 1 power).

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: [LAW_228:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_169

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:1:BASE

## EXPECT
P1CREDITCOUNT:2
P2GROUNDARENAUNIT:0:POWER:1
