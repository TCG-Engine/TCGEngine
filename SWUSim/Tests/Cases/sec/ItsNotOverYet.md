# AttackedUnit_NotEligible
#// SEC_177 It's Not Over Yet — a unit that attacked this phase is NOT eligible to ready. SOR_095
#//   attacks the base (exhausted + marked attacked-this-phase); then SEC_177 offers no ready target, so
#//   the unit stays exhausted and only the Spy is created.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_177

## WHEN
- P1>AttackGroundArena:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:2
P1NODECISION

---

# ReadyEligible_CreateSpy
#// SEC_177 It's Not Over Yet (Event, cost 2, Aggression) — "You may ready a unit that didn't attack or
#//   enter play this phase. Create a Spy token." A GIVEN exhausted SOR_095 (not played/attacked this
#//   phase) is eligible → ready it; also create a Spy.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1Hand: SEC_177

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENACOUNT:2
P1NODECISION
