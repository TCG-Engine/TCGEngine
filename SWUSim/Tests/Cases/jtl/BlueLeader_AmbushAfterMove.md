# FromHand_EnemyOnGroundOnly_MoveThenAMBUSH
#// COVERAGE: offer=N/A (Ambush's own target pick is covered in BlueLeader_ScarifAirSupport.md) ·
#//           decline=DeclineTheMove_NoAmbushAtAll (no move → no re-bag) ·
#//           boundary=NoEnemyUnitsAnywhere_NoTriggerAtAll (the flag must not INVENT a trigger) ·
#//           control=GroundBoundAmbushUnit_NoSpuriousPrompt + EnemyInSpaceToo_OrderingUnchanged ·
#//           reqboundary=RequestBoundary_TheDeferredAmbushSURVIVES ·
#//           modes=2P only (Ambush's "an enemy unit" pool is already seat-swept in its own file)
#//
#// REPORTED FROM LIVE PLAY: "I should be able to also ambush after moving."
#//
#// JTL_096 Blue Leader enters the SPACE arena with two simultaneous triggers — Ambush and its own
#// "When Played: pay 2 → move this unit to the ground arena". OFFICIAL RULING (Blue Leader, 03/06/2025):
#//   "Ambush is an ability that triggers at the same time as 'When Played' abilities. If there is no
#//    enemy unit that can be attacked, the unit does not ready."
#//   "Blue Leader's Ambush keyword and 'When Played' ability can be resolved in either order."
#// So Ambush TRIGGERS on play whether or not a target exists — the target check belongs to resolution.
#//
#// THE BUG: collection bagged an Ambush trigger only when a legal target already existed IN THE ARENA
#// THE UNIT ENTERED. Against an enemy who holds only GROUND units, Blue Leader entering in space had no
#// space target, so nothing was bagged — and paying 2 to move it to the ground then had no Ambush left
#// to resolve. The card's entire point, silently unavailable. (Dispatch was always correct: it
#// re-resolves the unit by UID and recomputes targets for its CURRENT arena. Only collection was wrong.)
#//
#// HERE: the enemy holds one GROUND unit and nothing in space. Blue Leader pays 2, moves to the ground
#// as a 5/5, and must then be offered its Ambush — defeating SOR_095 (3/3) for 5 and taking 3 back.
#// ⚠ There is NO trigger-ordering prompt in this section, and that is correct: only one trigger was ever
#// bagged. The Ambush comes back when the move creates the target, not before.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_096}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
#// pay 2 → move to the ground arena + 2 Experience
- P1>AnswerDecision:YES
#// the Ambush that the move just made possible
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:DAMAGE:3
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# Fetched_EnemyOnGroundOnly_MoveThenAMBUSH
#// THE SAME GAP ON THE ALTERNATE PLAY PATH — LOF_100 Kelleran Beq searches the top 7 and PLAYS Blue
#// Leader (cost 3, −3 → free), so it is never in hand. 9 resources: 7 for Kelleran, 0 for Blue Leader,
#// the last 2 for the move. Nothing in Blue Leader's own file exercises this path, so a fix that only
#// covered the from-hand play would leave this half broken.
## GIVEN
CommonSetup: rgw/rrk/{myResources:9;handCardIds:LOF_100}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [JTL_096 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:JTL_096
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_096
P1GROUNDARENAUNIT:1:POWER:5
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:0

---

# DeclineTheMove_NoAmbushAtAll
#// THE GATE IS THE MOVE, not the play. Refusing to pay 2 leaves Blue Leader in SPACE with no legal
#// target, so no Ambush is offered and the enemy ground unit is untouched. A fix that re-bagged on
#// every play rather than on a relocation would raise a prompt here.
## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_096}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1NODECISION
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_096
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1

---

# NoEnemyUnitsAnywhere_NoTriggerAtAll
#// THE BOUNDARY that keeps the fix honest: the stamp records a near-miss, it must not INVENT a trigger.
#// With the enemy board completely empty, moving to the ground still finds nothing to attack, so no
#// Ambush prompt is raised and the action simply ends.
## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_096}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# GroundBoundAmbushUnit_NoSpuriousPrompt
#// THE CONTROL THAT REJECTED THE FIRST FIX I WROTE. Widening the COLLECT-time scan to both arenas also
#// made this pass its Ambush check — and LAW_078 Sabine Wren is a GROUND unit that can never relocate,
#// so her Ambush can never gain a target and the extra trigger was pure noise: it turned her clean
#// single-decision play into a pointless "which trigger first?" prompt. (It broke
#// law/SabineWren_SpectreFive.md::EnemyVigilanceUnitDoesNotUnlockUniqueUpgrades, which says so in its
#// own comment.) Keying the re-bag on an actual ARENA CHANGE instead leaves her untouched.
#// Sabine (Ambush + a When Played) is played into an enemy board holding only a SPACE unit: exactly one
#// decision, hers, and no trigger-ordering prompt.
## GIVEN
CommonSetup: ryw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 0:SOR_136
WithP2SpaceArena: SHD_060:1:0
WithP1Hand: LAW_078
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0.u0

---

# EnemyInSpaceToo_OrderingUnchanged
#// REGRESSION CONTROL for the path that already worked. With an enemy unit in SPACE at collection time
#// Ambush is bagged normally, so there are TWO triggers and the player still orders them — the deferred
#// re-bag must not fire a second time and must not disturb this. Ordering the When Played first moves
#// Blue Leader and the ordinary (not deferred) Ambush then attacks on the ground.
#// ⚠ If the re-bag double-fired, Blue Leader would attack twice and take counter-damage twice.
## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_096}
SkipPreGame: true
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:POWER:5
#// exactly ONE counter-attack's worth of damage, from the 3/3 marine
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:1

---

# RequestBoundary_TheDeferredAmbushSURVIVES
#// THE REQUEST-BOUNDARY CELL, and this fix is exactly the shape that needs it: the near-miss is recorded
#// when the unit ENTERS PLAY and read when the move resolves — two different requests in production,
#// because the pay-2 prompt sits between them. A PHP global would be empty by then and the Ambush would
#// silently never come back, with every section above still green.
## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_096}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENACOUNT:0

---

# DeferredAmbush_REJOINSTheOrderingPool_AmbushFirst
#// THE ORDERING HALF (USER RULING 2026-08-31: "someone might want to order those — keep it in the bag
#// to choose the order"). Everything here triggered on the play, so a deferred Ambush must go back into
#// the pool to be ordered against whatever the player has NOT resolved yet — not resolve on the spot.
#//
#// ASH_102 Ravager sits in P1's space arena: "When you play a unit: You may have it deal damage equal to
#// its power to a unit in the same arena." Playing Blue Leader against a ground-only enemy board gives
#// TWO triggers at first (Blue Leader's When Played + Ravager's), and the Ambush is not among them — it
#// has no target from space. Resolving the When Played moves Blue Leader to the ground, which re-arms
#// Ambush; it rejoins the pool, and P1 is asked to order it against Ravager's still-pending trigger.
#//
#// HERE: AMBUSH first. Blue Leader (5/5) attacks SEC_080 (3/3), defeating it and taking 3 back. Ravager's
#// trigger then has nothing left to point at and fizzles.
#// ⚠ THE DAMAGE IS WHAT DISCRIMINATES, and its other half is the very next section: Ravager's ability
#// deals 5 as an ABILITY, not an attack, so there is no counter and Blue Leader ends on ZERO damage
#// there against 3 here. Read them as a pair — that is what proves the order was a real choice rather
#// than a prompt whose answer is ignored.
#// (ash/Ravager_FinalImperialCommand.md drives the same board for the Ravager clause's own sake; it
#// deliberately asserts only that clause's outcome, so the ordering proof lives here.)
## GIVEN
CommonSetup: ggw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: JTL_096
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:YES
#// the move re-armed Ambush — order it ahead of Ravager's trigger
- P1>ResolveTrigger:Ambush
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:POWER:5
#// counter-damage from the 3/3 it attacked — the half that is ZERO when Ravager goes first
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0

---

# DeferredAmbush_REJOINSTheOrderingPool_RavagerFirst
#// THE OTHER ORDER on the SAME board, and the half that makes the choice observable. P1 resolves
#// RAVAGER's trigger first: Blue Leader (now a 5-power ground unit) deals 5 to SEC_080 as an ABILITY —
#// no attack, so no counter-damage — defeating it. The deferred Ambush then finds nothing to attack and
#// fizzles, leaving Blue Leader on ZERO damage against the 3 it takes when Ambush goes first.
#// A build that resolved the deferred Ambush on the spot instead of returning it to the pool would take
#// the counter-attack here too, and this section is what catches that.
## GIVEN
CommonSetup: ggw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: JTL_096
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:YES
- P1>ResolveTrigger:ASH_102
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:POWER:5
#// zero — Ravager's damage is an ability, not an attack
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0
