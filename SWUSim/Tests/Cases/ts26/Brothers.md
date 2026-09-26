# TwoUniqueAttackNoDamageTaken
#// TS26_59 Brothers (Event, cost 3, Command) — Attack with up to 2 unique units (one at a time); prevent
#// all combat damage that would be dealt to each of them. Dodonna (4/4) then Veers (3/3) attack LAW_124
#// (4/7): combined 7 damage kills it, and both attackers take 0 counter damage (prevented).
## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:TS26_59}
WithP1GroundArena: [SOR_242:1:0 SOR_230:1:0]
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENACOUNT:0

---

# TheSecondOfferExcludesTheFirstAttackerAndNonUniques
#// TS26_59 Brothers — "up to 2 UNIQUE units (one at a time)". After Dodonna (index 0) attacks, the second
#// offer is Veers alone: the first attacker is out because each attack needs a different unit, and the
#// non-unique SOR_095 at index 2 was never eligible.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:TS26_59}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_242:1:0 SOR_230:1:0 SOR_095:1:0]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1

---

# PassingTheSecondAttack
#// TS26_59 Brothers — "UP TO 2". Dodonna attacks LAW_124 for 4 and takes no counter damage (prevented),
#// then the second attack is declined: Veers is untouched and still ready.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:TS26_59}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_242:1:0 SOR_230:1:0]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:READY

---

# PassingBOTHAttacks
#// TS26_59 Brothers — declining at the first offer ends the whole effect: LAW_124 is untouched, the event
#// still resolves into the discard, and no decision is left hanging.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:TS26_59}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_242:1:0 SOR_230:1:0]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1
P1NODECISION

---

# NoUniqueFriendlyUnits_NoEffect
#// TS26_59 Brothers — with only the non-unique SOR_095 in play there is nobody to attack with. The event
#// is played and discarded, LAW_124 takes nothing, and no offer is raised at all.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:TS26_59}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OnlyCOMBATDamageToTheAttackerIsPrevented
#// TS26_59 Brothers — "prevent all COMBAT damage that would be dealt to each of them". Obi-Wan's
#// Aethersprite (TWI_048, 4/6) attacks LAW_124 (4/7) through Brothers and uses its own On Attack to deal 1
#// to ITSELF and 2 to the defender. LAW_124's 4 power is prevented, but the Aethersprite's self-inflicted 1
#// is not combat damage and lands: it ends on 1 damage, not 0 and not 5. LAW_124 takes 4 combat + 2.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:TS26_59}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: TWI_048:1:0
WithP1GroundArena: SOR_242:1:0
WithP2SpaceArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:6

---

# ThreeSeat_DeadSeatDoesNotHIDETheLivingOnesTargets
#// ⚠ THE FIXTURE THIS NEEDED `WithEliminatedSeats` FOR. The attacker pool filters on "does this unique
#// unit have ANY legal attack target", and that gate read
#// `SWUGetValidAttackTargets(OtherPlayer($player), …)` — ONE seat. Normally that hides nothing, because
#// an opponent's BASE is always a legal target, so the gate is satisfied whichever seat it looks at.
#//
#// It only breaks when the seat OtherPlayer() names is DEAD. Here the caster is seat 1, so
#// OtherPlayer(1) is SEAT 2 — eliminated, base removed, board empty — and the gate concluded there was
#// nothing to attack and offered NO unique unit at all, while seat 3 sat there perfectly attackable.
#//
#// That is why `WithLiveSeats` alone could not express this test: it leaves the dead seat's base
#// readable, so the gate would still find a target and the bug would stay invisible.

## GIVEN
CommonSetup3P: ggk/rrk/rrk
SkipPreGame: true
WithEliminatedSeats: 2
WithActivePlayer: 1
WithP1Resources: 3
WithP1Hand: TS26_59
WithP1GroundArena: SOR_242:1:0
WithP3GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:3
SEATLIVE:2:false
P1SELECTABLEEXACT:myGroundArena-0
