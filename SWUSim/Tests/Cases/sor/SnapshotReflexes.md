# WhenPlayed_HostOffer
#// SOR_215 Snapshot Reflexes — Upgrade, cost 1, [Cunning], +1/+1, trait Learned, non-unique.
#// "When Played: You may attack with attached unit."
#// COVERAGE: offer=WhenPlayed_HostOffer (attach host pool left PENDING) · decline=
#//           WhenPlayed_Declined_HostStaysReady · boundary=WhenPlayed_AttackWithHost_TargetUnit (ready
#//           host attacks) vs WhenPlayed_HostExhausted_NoAttack (exhausted host cannot) · reqboundary=
#//           WhenPlayed_AttackWithHost_TargetUnit (boundary between the YES and the attack-target pick)
#//           · control=N/A (the When Played resolves entirely at play time; the host cannot change
#//           control between the attach and the optional attack)
#// Host pool: every friendly unit is offered — ready, exhausted, ground AND space (the upgrade has no
#// printed arena/trait restriction). Left pending to assert the offer.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;myhandCardIds:SOR_215}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_164:0:0]
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0

---

# WhenPlayed_AttackWithHost_TargetUnit
#// Full flow: attach to the ready SOR_095 (3/3 → 4/4), answer YES to the optional attack, then pick the
#// enemy SOR_140 (2/2) as the attack target. The Marine deals 4 (SpecForce dies), takes 2 back, ends
#// exhausted with the upgrade attached. The base is untouched.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;myhandCardIds:SOR_215}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_164:0:0]
WithP2GroundArena: SOR_140:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_215
P2BASEDMG:0
P1RESAVAILABLE:0

---

# WhenPlayed_Declined_HostStaysReady
#// "You MAY attack" — declining the YESNO leaves the host READY (never exhausted) with the upgrade
#// attached, and nothing takes damage. Single unit in play → the attach auto-resolves; only the
#// optional-attack YESNO consumes an answer.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;myhandCardIds:SOR_215}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:0
P1NODECISION

---

# WhenPlayed_HostExhausted_NoAttack
#// Attached to an already-exhausted host: answering YES cannot start an attack (an exhausted unit can't
#// attack). The upgrade still attaches; no damage anywhere; the host stays exhausted.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;myhandCardIds:SOR_215}
P1OnlyActions: true
WithP1GroundArena: SOR_164:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# WhenPlayed_AttackRespectsSentinel_AutoTarget
#// The Snapshot attack is a normal attack: with an enemy Sentinel (SOR_098, 4/3) in the arena, the
#// Sentinel is the only legal target, so the attack auto-resolves into it with no target prompt.
#// Marine 4/4 kills the Sentinel and dies to the 4 counter (Snapshot goes to the discard with it).
#// The Marine is the only friendly unit, so the attach itself also auto-resolves — the YESNO is the
#// only answer this flow consumes.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;myhandCardIds:SOR_215}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_098:1:0 SOR_140:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_140
P2BASEDMG:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1
P1NODECISION

---

# Attach_EnemyHostOffered_NoAttackPrompt
#// No printed attach restriction → the host pool spans BOTH sides (CR 2.e). Attaching to the enemy
#// marine is legal — and then "you may attack with attached unit" can only fizzle (you cannot attack
#// with an opponent's unit), so per the fizzle-only-optional ruling NO prompt appears. The upgrade
#// stays on the enemy unit; nothing attacks.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SOR_215

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
