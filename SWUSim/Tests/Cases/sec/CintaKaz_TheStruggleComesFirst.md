# WhenPlayed_MayAttack
#// SEC_172 Cinta Kaz (Ground, 5/5, cost 6) — When Played: you may attack with a unit. P1 plays SEC_172
#//   and attacks with the ready SEC_041 → P2's base takes 1.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_172

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:1

---

# PlayedViaPlot_MayAttack
#// SEC_172 Cinta Kaz has Plot — "When you deploy a leader, you may play this card from your resources."
#// Deploying SOR_013 Cassian Andor opens the Plot window; playing Cinta Kaz from resources still fires
#// her When Played "you may attack with a unit" — P1 attacks with the ready SOR_095 (3 power) into P2's
#// base for 3.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:SOR_013}
P1OnlyActions: true
WithP1Resources: 1:SEC_172:1,13:SOR_095:1
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P2BASEDMG:3

---

# WhenPlayedAttack_PassesTurn_NoExtraAction
#// Bug #926. SEC_172's When Played attack is NESTED inside her own play, so the play is P1's single
#//   action and the turn must pass once the attack resolves. Exactly one After Action may run: the
#//   combat's and the play's FINISH_PLAY_CARD terminator must not both fire (double swap = back to P1
#//   with two seats), nor both stand down (no swap at all) — both read as a free extra action.
#//
#//   Deliberately NOT P1OnlyActions and initiative UNCLAIMED: this family is masked when the seat or
#//   initiative state makes the extra swap unobservable (see the Support/event-attack cases).

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_172

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:1
TURNPLAYER:2

---

# WhenPlayedAttack_AcrossRequestBoundary_PassesTurn
#// Same, but the attacker choice is answered in a FRESH process. After-action ownership rides serialized
#// SWUVars precisely so it survives that boundary; a transient global would be lost and the turn would
#// swap twice. SimulateRequestBoundary models the real HTTP boundary the prompt creates.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_172

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:1
TURNPLAYER:2

---

# PlayedViaPlot_Attack_PassesTurn
#// The Plot route: the deploy opens the Plot window, Cinta is played from resources, and her When Played
#// attack resolves inside BOTH the deploy action and the Plot window. The deploy is the single action, so
#// the turn must still pass to P2 exactly once.
#//
#// The trailing YES answers SOR_013 Cassian Andor's own "draw a card?" deploy prompt. Omitting it leaves
#// that decision PENDING, so the action legitimately has not finished and the turn correctly has not
#// passed — which reads exactly like the free-action bug and is not one. Assert P1NODECISION alongside
#// TURNPLAYER so an unanswered prompt can never masquerade as a turn-passing failure again.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:SOR_013}
WithActivePlayer: 1
WithP1Resources: 1:SEC_172:1,13:SOR_095:1
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_095,SOR_095

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:DEPLOYED
P2BASEDMG:3
P1NODECISION
TURNPLAYER:2

---

# WhenPlayedAttack_WithOnAttackChain_PassesTurn
#// Bug #926, the reported repro. Cinta's When Played attack is launched with a unit whose granted On
#//   Attack opens its OWN decision chain BEFORE combat damage: JTL_142 Darth Vader (pilot) grants
#//   "On Attack: you may deal 1 to a unit; if a unit is defeated this way, you may deal 1 to a unit or
#//   base." So the play contains an attack, which contains two more decisions, and only THEN resolves
#//   combat damage. Exactly one After Action may run across all of it.
#//
#//   JTL_085 Victor Leader (2/4) piloted by JTL_142 (+3/+3) = 5/7 attacks the enemy base. The On Attack
#//   kills LAW_038 Lepi Lookout (3/1) and chains 1 to the base, then combat adds 5 → 6 total.
#//   A plain When Played attack with no On Attack chain already passes (see the case above), so the
#//   chain — not the nesting alone — is what this pins.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
WithActivePlayer: 1
WithP1SpaceArena: JTL_085:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: LAW_038:1:0
WithP1Hand: SEC_172

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:6
TURNPLAYER:2

---

# WhenPlayedAttack_OnAttackChain_AcrossRequestBoundaries_PassesTurn
#// Bug #926 as it actually happens. Identical to the case above, but EVERY answer arrives in a fresh
#//   process, which is what the live game does — the play, the attacker choice, the On Attack target and
#//   the chained ping are four separate HTTP requests. After-action ownership rides serialized SWUVars
#//   precisely so it survives those boundaries; anything held in a transient global is lost, and the
#//   nested attack and the play's terminator can then both act (or both stand down).
#//
#//   Same board: JTL_085 Victor Leader (2/4) piloted by JTL_142 (+3/+3) attacks the enemy base for 5,
#//   the granted On Attack kills LAW_038 Lepi Lookout and chains 1 to the base → 6 total, then the turn
#//   must pass to P2. Reported as P2 keeping the turn and getting a free action.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
WithActivePlayer: 1
WithP1SpaceArena: JTL_085:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: LAW_038:1:0
WithP1Hand: SEC_172

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:mySpaceArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:6
TURNPLAYER:2
