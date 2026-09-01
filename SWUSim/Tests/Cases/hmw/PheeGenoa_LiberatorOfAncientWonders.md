# EnemyLeaderDeploys_ControllerPaysTwo_LeaderStaysReady
#// COVERAGE: offer=N/A - STRUCTURAL: nothing is ever chosen. The reaction targets "that leader", which
#//           is DETERMINED by the event that triggered it, and the only decision is a YESNO about
#//           paying. The nearest equivalent - whether the choice is offered AT ALL - is asserted as a
#//           pair by PaymentCapacityIncludesCredits_PromptIsRaised and
#//           CannotAfford_NoPromptAndLeaderIsExhausted.
#//           decline=EnemyLeaderDeploys_ControllerDeclines_LeaderIsEXHAUSTED (the printed "if they
#//           don't" branch), and it is DISTINCT from the cannot-pay branch, which raises no prompt.
#//           boundary=N/A - STRUCTURAL: the cost is a fixed 2 with no threshold to straddle. The only
#//           numeric edge is affordability, covered as the capacity pair above.
#//           control=PheeUnderENEMYControl_TriggersOnHERControllersEnemy ("an ENEMY leader" is relative
#//           to whoever controls Phee NOW, not to who owns her)
#//           reqboundary=SurvivesTheRequestBoundary
#//           modes=2P,TwinSuns,TeamSuns - "an ENEMY leader" is friendly/enemy wording AND the reaction
#//           has to fan out across every opponent, so both axes are live: a far seat's deploy must
#//           trigger, and a TEAMMATE's deploy must not.
#//
#// HMW_214 Phee Genoa, Liberator of Ancient Wonders - 4-cost 5/4 Ground, Underworld, unique.
#//   "Hidden
#//    When an enemy leader deploys: Its controller may pay [2 resources]. If they don't, exhaust that
#//    leader."
#// PREVIEW SET - no official rulings exist for HMW. Read from the CR plus released precedent.
#//
#// WHO DECIDES. "ITS controller" is the leader's controller - the player who just deployed, i.e. the
#// ACTIVE player. Phee's own controller never chooses anything. That is what makes this card's
#// cross-player shape unusually simple: the decision belongs on the queue of the player who is already
#// acting, not on the reactor's.
#// A deployed leader unit enters play READY, so exhausting it is a real cost and paying 2 is a real
#// choice - if it entered exhausted the whole card would be a fizzle-only offer.
#//
#// THE PAY BRANCH. P2 deploys Moff Gideon with 7 resources, pays the 2, and keeps a ready leader.
#// The leader chosen here has NO "When Deployed" ability on purpose: a second entry trigger would raise
#// the "Choose_trigger_to_resolve" ordering prompt and every section would need an extra answer.

## GIVEN
CommonSetup: yyw/ggk/{theirLeader:SHD_007;theirResources:7}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: HMW_214:1:0

## WHEN
- P2>DeployLeader
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_007
P2GROUNDARENAUNIT:0:READY
P2RESAVAILABLE:5
P1NODECISION
P2NODECISION

---

# EnemyLeaderDeploys_ControllerDeclines_LeaderIsEXHAUSTED
#// THE "IF THEY DON'T" BRANCH. Same board, answer NO: the resources are kept and the freshly deployed
#// leader is exhausted instead.
#// Both halves are asserted - the leader's STATE and the untouched resource count - so a handler that
#// exhausted the leader AND took the payment, or took the payment on a NO, is visible either way.
#// The CARDID is asserted alongside the exhaust so the section pins WHICH unit was exhausted rather
#// than merely that something on the board is exhausted.
## GIVEN
CommonSetup: yyw/ggk/{theirLeader:SHD_007;theirResources:7}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: HMW_214:1:0
## WHEN
- P2>DeployLeader
- P2>AnswerDecision:NO
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_007
P2GROUNDARENAUNIT:0:EXHAUSTED
P2RESAVAILABLE:7
P1NODECISION
P2NODECISION

---

# CannotAfford_NoPromptAndLeaderIsExhausted
#// CANNOT-PAY IS A DIFFERENT BRANCH FROM DECLINING. A player who cannot reach 2 must not be asked a
#// question they cannot act on - the house rule against offering a choice with only one possible
#// answer - so no prompt is raised at all and the "if they don't" outcome resolves immediately.
#// The board is built so the two conditions pull apart: the deploy threshold counts TOTAL resources
#// while the payment needs READY ones, so five EXHAUSTED resources deploy the leader perfectly well and
#// leave a payment capacity of zero.
## GIVEN
CommonSetup: yyw/ggk/{theirLeader:SHD_007}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 5:SOR_046:0
WithP1GroundArena: HMW_214:1:0
## WHEN
- P2>DeployLeader
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
P2NODECISION

---

# PaymentCapacityIncludesCredits_PromptIsRaised
#// THE OTHER HALF OF THE AFFORDABILITY PAIR, and the cell that keeps the gate honest.
#// "Pay 2 resources" is an ordinary cost, so Credit tokens and SEC_122 Droids pay it (CR 3.13) - the
#// gate must therefore read TOTAL PAYMENT CAPACITY, not a bare ready-resource count. Four exhausted
#// resources meet the deploy threshold, one ready resource plus one Credit reach exactly 2, and the
#// prompt must appear.
#// Without this pair the cannot-pay section above would be satisfied by a gate that simply never
#// offered the choice, and a capacity-blind gate would silently deny the option to a player who can
#// actually pay.
## GIVEN
CommonSetup: yyw/ggk/{theirLeader:SHD_007}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 4:SOR_046:0,1:SOR_046:1
WithP2Credits: 1
WithP1GroundArena: HMW_214:1:0
## WHEN
- P2>DeployLeader
## EXPECT
P2HASDECISION
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:READY

---

# YourOWNLeaderDeploying_DoesNotTrigger
#// THE WORD "ENEMY". Phee's own controller deploying their own leader is not an enemy leader deploying,
#// so nothing is offered and the leader stays ready with every resource intact.
#// An observer that fired on ANY leader deploy - the obvious first implementation, since the hook sits
#// in the deploy path itself - passes every other section in this file and fails only here.
## GIVEN
CommonSetup: yyw/ggk/{myLeader:SHD_007;myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_214:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1GROUNDARENACOUNT:2
P1LEADER:DEPLOYED
P1RESAVAILABLE:7
P1NODECISION
P2NODECISION

---

# NoPheeInPlay_NoTriggerAtAll
#// THE CONTROL that proves the whole reaction is gated on Phee being in play. Identical to the pay
#// section but with an ordinary unit in her place: the deploy is completely unaffected.
#// Without this, an unconditional tax on every enemy deploy would satisfy the two branch sections.
## GIVEN
CommonSetup: yyw/ggk/{theirLeader:SHD_007;theirResources:7}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_095:1:0
## WHEN
- P2>DeployLeader
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:READY
P2RESAVAILABLE:7
P1NODECISION
P2NODECISION

---

# Hidden_CantBeAttackedThePhaseSheIsPlayed
#// THE KEYWORD CLAUSE. Hidden is "this unit can't be attacked if it was played this phase" and is
#// auto-wired from the generated registry, so this verifies rather than implements - but a
#// keyword-plus-rider card that ships with only the rider tested is how half a card goes uncovered.
#// P1 PLAYS Phee (seeding her would not mark her as played this phase, so the keyword would not
#// apply), then P2 attacks. She is the only unit in P1's arena, so a protected Phee leaves the base as
#// the only legal target and the attack auto-fires there - which is the standard way to observe
#// "can't be attacked", since the harness does not re-validate an attack-target answer against the
#// offered list.
## GIVEN
CommonSetup: yyw/ggk/{theirLeader:SHD_007}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: [HMW_214]
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_214
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3

---

# PheeUnderENEMYControl_TriggersOnHERControllersEnemy
#// THE CONTROL-CHANGE CELL. "An ENEMY leader" is enemy relative to whoever CONTROLS Phee right now, not
#// to whoever owns her. P2 controls a P1-owned Phee, so it is P1's deploy that is now the enemy one -
#// the exact opposite of every other section in this file.
#// P1 declines, so P1's own freshly deployed leader is exhausted. An observer that resolved "enemy"
#// from Phee's OWNER would find P1's deploy friendly and do nothing here.
## GIVEN
CommonSetup: yyw/ggk/{myLeader:SHD_007;myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArenaControlled: HMW_214:1
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_007
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:7

---

# SurvivesTheRequestBoundary
#// THE REQUEST-BOUNDARY CELL. The reaction is armed while the deploy resolves and the payment decision
#// is answered afterwards - in production two separate requests, in two separate processes, with a
#// gamestate write and re-parse between them. Anything the handler needs in order to know WHICH leader
#// to exhaust must therefore be serialized; held in memory it is gone by the time the answer arrives,
#// and the failure is silent (the leader simply stays ready, as though the player had paid).
#// Same board and same expected outcome as the decline section, with the boundary inserted before the
#// answer.
## GIVEN
CommonSetup: yyw/ggk/{theirLeader:SHD_007;theirResources:7}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: HMW_214:1:0
## WHEN
- P2>DeployLeader
- P2>SimulateRequestBoundary
- P2>AnswerDecision:NO
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_007
P2GROUNDARENAUNIT:0:EXHAUSTED
P2RESAVAILABLE:7

---

# TwinSuns_AFarSeatsPheeTriggersOnASeat2Deploy
#// TWIN SUNS - CANNOT PASS AT TWO SEATS. Above two seats the reaction has to fan out: seat 2's deploy
#// is an enemy deploy for EVERY other seat, so seat 3's Phee must tax it.
#// PHEE IS PARKED ON SEAT 3, NOT SEAT 1, AND THAT IS DELIBERATE. OtherPlayer() answers 1 for every
#// seat except seat 1, so a reactor sitting on seat 1 gets the correct answer out of the broken
#// two-seat code and the whole section passes under the very bug it was written to catch.
#// Seat 2 declines, so seat 2's freshly deployed leader is exhausted.
## GIVEN
CommonSetup: yyw/ggk/{theirLeader:SHD_007;theirResources:7}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP3GroundArena: [HMW_214:1:0]
## WHEN
- P2>DeployLeader
- P2>AnswerDecision:NO
## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:CARDID:SHD_007
P2GROUNDARENAUNIT:0:EXHAUSTED
P2RESAVAILABLE:7

---

# TeamSuns_ATeammatesLeaderDeployDoesNotTrigger
#// TEAM SUNS - the other direction from the far-seat section above, and it fails opposite to it. Teams
#// are seat parity, so seat 1's TEAMMATE is seat 3. A teammate's leader is not an enemy leader, so seat
#// 3's Phee must not tax seat 1's deploy.
#// A reaction built on the plain Twin Suns fan-out - "every live seat that is not me" - triggers here
#// and taxes an ally, which is the natural bug to introduce once the far-seat section is made to pass.
#//
#// WARN THIS SECTION WAS REWRITTEN AFTER A GREEN MUTATION. Its first form had SEAT 3 deploy while Phee
#// sat on seat 1 - but there is NO far-seat leader directive (WithP3Leader does not exist) and
#// CommonSetup dresses seats 1 and 2 only, so seat 3 had no leader, DeployLeader was a silent no-op,
#// and the section passed because NOTHING HAPPENED. It is inverted here so the deploying seat is one
#// CommonSetup actually equips: seat 1 deploys, and the teammate holding Phee is seat 3.
## GIVEN
CommonSetup: yyw/ggk/{myLeader:SHD_007;myResources:7}
SkipPreGame: true
WithTeams: true
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP3GroundArena: [HMW_214:1:0]
## WHEN
- P1>DeployLeader
## EXPECT
SEATCOUNT:4
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SHD_007
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:7
P1NODECISION
