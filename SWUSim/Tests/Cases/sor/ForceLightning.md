# LosesAbilities_ForceUnitDamage
#// SOR_138 Force Lightning (Event, cost 1, Aggression/Villainy) — "Choose a unit. It loses all abilities
#// for this phase. Then, if you control a FORCE unit, pay any number of resources and deal 2 damage to
#// the chosen unit for each resource paid this way." P1 controls a Force unit (SOR_051 Luke), targets the
#// enemy SOR_063 (Sentinel, 2/4): it loses Sentinel and, paying 1 resource, takes 2 damage (survives).
#// Spend = 1 (card) + 1 (X) of 3 ready → 1 left.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2GroundArena: SOR_063:1:0
WithP1Hand: SOR_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:1

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:1

---

# NoForceUnit_AbilitiesOnly
#// SOR_138 Force Lightning — the "loses all abilities" half is unconditional, but the "pay resources,
#// deal 2 each" half is gated on controlling a FORCE unit. With no Force unit, the enemy SOR_063 loses
#// Sentinel but takes NO damage and there is no pay step.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP2GroundArena: SOR_063:1:0
WithP1Hand: SOR_138

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:0
