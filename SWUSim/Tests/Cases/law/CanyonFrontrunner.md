# OnAttackDebuffIfFirst
#// LAW_228 Canyon Frontrunner (3/2) — On Attack: if no other units have attacked this phase (including
#// enemy units), you may give a unit -2/-0 for this phase. It's the only attacker -> debuff SOR_046
#// (3/7 -> 1/7).

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_228:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1

---

# NoDebuffIfFriendlyAlreadyAttacked
#// LAW_228 Canyon Frontrunner (3/2) — On Attack debuff only applies if NO other unit has attacked this
#// phase. SOR_164 Wampa attacks first, then Canyon attacks; because a friendly unit already attacked
#// this phase the ability does not trigger and no target is offered (SOR_046 keeps its 3 power).

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_228:1:0 SOR_164:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:POWER:3

---

# NoDebuffIfEnemyAlreadyAttacked
#// LAW_228 Canyon Frontrunner — an ENEMY attack this phase also disables the debuff. P2's SEC_213 A-Wing
#// attacks P1's base first, then P1 attacks with Canyon. Because a unit already attacked this phase
#// Canyon's On Attack does not trigger and no target is offered (SOR_046 keeps its 3 power).

## GIVEN
CommonSetup: yyk/bgw/{}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LAW_228:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:POWER:3

---

# DebuffAppliesEvenIfAttackedPreviousPhase
#// LAW_228 Canyon Frontrunner — the "no other units have attacked" check is per-phase. SOR_164 Wampa
#// attacks in the first action phase; after advancing to the next action phase Canyon attacks as the
#// first attacker of THAT phase, so the debuff still applies (SOR_046 3 power -> 1).

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_228:1:0 SOR_164:1:0]
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:1

---

# NonAttackActionsDoNotBlockTheDebuff
#// LAW_228 Canyon Frontrunner — the gate is specifically "no other units have ATTACKED this phase", not
#// "no other ACTIONS have been taken". P1 spends an action PLAYING a unit (SOR_095) and then attacks with
#// Canyon, which is still the phase's first attacker — so the debuff applies (SOR_046 3 -> 1).
#// Load-bearing next to NoDebuffIfFriendlyAlreadyAttacked: that proves a prior friendly ATTACK blocks,
#// this proves a prior friendly non-attack action does not. Without the pair the gate could be reading
#// "any prior action" and every other section would still pass.
#// (SOR_095 is Command against a Cunning board, so it costs 2+2=4 — hence 5 resources.)

## GIVEN
CommonSetup: yyk/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_228:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1

---

# AnotherFriendlyAttackedOnTheSAMEAction_NoDebuff
#// The sharpest form of the gate: the blocking attack happens within the SAME action, not an earlier one.
#// SEC_006 Colonel Yularen's leader Action ("Attack with a unit. Then, you may attack with another unit
#// that costs less than it") attacks with Wampa (SOR_164, cost 4) and then chains Canyon (cost 2 < 4).
#// Wampa has attacked by the time Canyon's On Attack checks, so the debuff does NOT trigger and no target
#// is offered — proving the check reads live attack state rather than a per-action snapshot taken up front.
#// Base damage 4 (Wampa) + 3 (Canyon, undebuffed and debuffing nothing) = 7.

## GIVEN
CommonSetup: yyk/bgw/{
  myLeader:SEC_006
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [LAW_228:1:0 SOR_164:1:0]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1NODECISION
P2BASEDMG:7
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# AmbushAttackCountsAsAnAttack
#// "If no other units have attacked this phase" — an AMBUSH attack is an attack, and it reaches combat
#// through a DIFFERENT entry point than a player-initiated one. P1 plays JTL_216 Contracted Hunter, takes
#// its Ambush attack, then attacks with Canyon Frontrunner in the SAME phase: no debuff may be offered.
#// The Hunter's 3 damage (SOR_046's counter... no — SOR_095 3/3 trades into it) plus P2's arena dropping
#// to 1 prove the Ambush attack really executed, so P1NODECISION cannot pass vacuously.

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: LAW_228:1:0
WithP1Hand: JTL_216
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-1
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:1:DAMAGE:3
P2BASEDMG:3
P1NODECISION

---

# DebuffAppliesEvenIfAttackedOnThePreviousREGROUPPhase
#// The counter is PHASE-scoped, and an attack can happen during the REGROUP phase: P2's JTL_216
#// Contracted Hunter defeats itself when regroup starts, which collects P1's SHD_226 Unrefusable Offer
#// bounty — P1 replays the Hunter READY under its own control and takes its Ambush attack, inside the
#// regroup phase. On the next action phase Canyon Frontrunner must still see "no other units attacked".
#// P2's arena falling to 1 during regroup is what proves the regroup attack executed.
#// The paired partner is DebuffAppliesEvenIfAttackedPreviousPhase (the action-phase boundary); this is the
#// regroup-phase boundary, which is a genuinely different code path because the flags were once cleared
#// ONLY at regroup start — an attack made after that clear leaked into the next phase.

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: LAW_228:1:0
WithP2GroundArena: [JTL_216:1:0 SOR_046:1:0 SOR_095:1:0]
WithP2GroundArenaUpgrade: 0:SHD_226
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>Pass
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-1
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENACOUNT:1
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# OnAttackDebuffIfFirst_SurvivesTheRequestBoundary
#// LAW_228 Canyon Frontrunner — request-boundary guard. Identical to OnAttackDebuffIfFirst except the
#// game round-trips through serialization (SimulateRequestBoundary) while the "may give a unit -2/-0"
#// pick is still pending (a genuine two-option offer: myGroundArena-0 & theirGroundArena-0). In a real
#// game that answer arrives in a fresh process, so the deferred -2/-0 payload queued by the On Attack
#// trigger — and the phase-scoped "no other units have attacked" state it was gated on — must be
#// serialized rather than parked in a transient global. SOR_046 must still drop 3 -> 1 power.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_228:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1
