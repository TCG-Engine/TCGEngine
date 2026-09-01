# Deploy_OnAttack_Decline
#// SOR_005 Luke Skywalker — Deployed: OnAttack NO → no shield given.

## GIVEN
CommonSetup: gbw/grw/{myResources:6}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1LEADER:EPICUSED

---

# Deploy_OnAttack_ShieldAnotherUnit
#// SOR_005 Luke Skywalker — Deployed: OnAttack YES → give Shield to another unit.
#// Luke attacks base; OnAttack gives shield to P2's unit (valid "another unit" target).

## GIVEN
CommonSetup: gbw/grw/{myResources:6}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EPICUSED

---

# LeaderAction_NotPlayedThisPhase
#// SOR_005 Luke Skywalker — Leader Action: No shield when unit not played this phase.
#// SOR_095 is pre-existing (GIVEN), not played this phase — no valid targets.

## GIVEN
CommonSetup: gbw/grw/{myResources:1}
WithP1GroundArena: SOR_095:2:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# LeaderAction_ShieldPlayedUnit
#// SOR_005 Luke Skywalker — Leader Action: Shield a Heroism unit played this phase.

## GIVEN
CommonSetup: gbw/grw/{myResources:3;handCardIds:SOR_095}
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# SimulateRequestBoundary_PlayedThisPhaseTargetPoolSurvives
#// SOR_005 Luke Skywalker — the leader action targets "a Heroism unit you played this phase", so the
#// played-this-phase memory is written by one request (the play) and read by a later one (the leader
#// action), whose own target pick ends yet another request. Mirrors LeaderAction_ShieldPlayedUnit with
#// a boundary after the play AND before the answer: the Marine is still a legal target and gets its
#// Shield.

## GIVEN
CommonSetup: gbw/grw/{myResources:3;handCardIds:SOR_095}

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# EpicDeploy_FiveResources_IsRefused
#// SOR_005 Luke Skywalker — the Epic Action's own condition, which no existing section exercises:
#// "Epic Action: If you control 6 OR MORE resources, deploy this leader." The tight N-1 side: with
#// exactly FIVE resources the deploy is refused outright — Luke stays on his leader side and, crucially,
#// the once-per-game Epic Action is NOT consumed (still EPICAVAILABLE), so the player can deploy later.
#// The N side (6 resources → deploys, EPICUSED) is the Deploy_OnAttack_* sections' GIVEN.
#// COVERAGE: offer=Deploy_OnAttack_Offer_IsEveryOtherUnit (pending exact pool for the deployed side's
#//           "another unit"; the leader-side action's pool discrimination is
#//           LeaderAction_OnlyHeroismUnitsPlayedThisPhaseAreEligible) ·
#//           decline=Deploy_OnAttack_Decline (the deployed "you may" answered '-') ·
#//           boundary=EpicDeploy_FiveResources_IsRefused (5 out) vs the deployed sections (6 in) ·
#//           control=N/A (both abilities are the leader's own; the shield lands on a unit chosen at
#//           resolution and no state outlives the action, so there is no owner-vs-controller reading) ·
#//           reqboundary=SimulateRequestBoundary_PlayedThisPhaseTargetPoolSurvives

## GIVEN
CommonSetup: gbw/grw/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
P1GROUNDARENACOUNT:0
P1RESCOUNT:5

---

# LeaderAction_OnlyHeroismUnitsPlayedThisPhaseAreEligible
#// SOR_005 Luke Skywalker — the [Heroism] word in "Give a Shield token to a [HEROISM] unit you played
#// this phase" is load-bearing, and no existing section proves it. P1 plays two units in the same phase:
#// Battlefield Marine (Command/Heroism) and Greedo (Cunning, no alignment). Both satisfy "you played
#// this phase", so the aspect filter is the ONLY thing separating them: the pool narrows to the Marine
#// alone, which means the pick auto-resolves and the Shield lands on the Marine while Greedo gets
#// nothing. The auto-resolution IS the assertion here (P1NODECISION), since a widened pool would have
#// stopped for a choice.

## GIVEN
CommonSetup: gbw/grw/{myResources:9}
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_204]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_204
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1LEADER:EXHAUSTED
P1NODECISION

---

# Deploy_OnAttack_Offer_IsEveryOtherUnit
#// SOR_005 Luke Skywalker, deployed side — "On Attack: You may give ANOTHER unit a Shield token." The
#// scope word is "another", with no controller, arena or aspect qualifier, so the pool is every unit in
#// play except Luke himself: P1's AT-ST and TIE/ln, P2's Wampa (the unit Luke is currently attacking)
#// and P2's Alliance X-Wing — and NOT the deployed Luke at ground index 1. Neither base appears. The
#// choice is left PENDING so the exact pool can be read; Deploy_OnAttack_ShieldAnotherUnit and
#// Deploy_OnAttack_Decline resolve the two branches.

## GIVEN
CommonSetup: gbw/grw/{myLeader:SOR_005:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1HASDECISION
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# Deploy_OnAttack_ShieldingTheDefenderPrecedesCombatDamage
#// SOR_005 Luke Skywalker, deployed side — WHEN the On Attack shield lands matters. It is an On Attack
#// trigger, so it resolves during the attack and BEFORE the "deal combat damage" step; putting the
#// Shield on the DEFENDER therefore saves that defender from the very attack that triggered it. Deployed
#// Luke (4/7) attacks a Wampa (4/5) and shields the Wampa: the Shield absorbs all 4 of Luke's combat
#// damage and is then defeated, so the Wampa ends undamaged and unupgraded, while the Wampa's own 4
#// still lands on Luke. If the shield were applied after combat damage the Wampa would sit on 4.

## GIVEN
CommonSetup: gbw/grw/{myLeader:SOR_005:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# LeaderAction_PlayedThisPhaseMemoryResetsAtThePhaseBoundary
#// SOR_005 Luke Skywalker — the DURATION edge of "a [Heroism] unit you played THIS PHASE". The marker
#// is phase-scoped, so it must be cleared when a new action phase begins; LeaderAction_NotPlayedThisPhase
#// only proves a unit that was never played is ineligible, which a never-set marker would also satisfy.
#// Here P1 plays Battlefield Marine, both players pass into regroup and back into a fresh action phase,
#// and P1 then plays an Alliance X-Wing. The leader action now has exactly ONE eligible unit — the
#// X-Wing — so it auto-resolves onto it, and the Marine (played in the PREVIOUS phase) is left
#// unshielded. The section discriminates in both directions: a marker that never cleared would have
#// widened the pool to two and stopped for a choice, and a marker cleared too eagerly would have left
#// nothing to shield.

## GIVEN
CommonSetup: gbw/grw/{myResources:9}
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_237]
WithP1Deck: SOR_128
WithP1Deck: SOR_225
WithP2Deck: SOR_128
WithP2Deck: SOR_225

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION
