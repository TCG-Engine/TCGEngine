# UseForce_Exhaust
#// LOF_178 Adept of Anger — Action [Exhaust, use the Force]: exhaust a unit. P1 exhausts the Adept, uses
#// the Force, and exhausts the enemy 3/7.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_178:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# NoForce_CannotExhaust
#// LOF_178 Adept of Anger — the Action costs "use the Force (lose your Force token)". With NO Force token, the
#// exhaust ability is unavailable: activating the Adept does not exhaust the enemy unit and both stay ready.
#// Ref: "cannot exhaust a unit if controller does not have the force" — only the Attack option is
#// offered.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_178:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:READY
