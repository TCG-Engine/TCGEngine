# AttackImperial_Buff
#// SHD_236 Snowtrooper Lieutenant (2-cost ground unit) — "When Played: You may attack with a unit. If it's
#// an Imperial unit, it gets +2/+0 for this attack." P1 attacks with the Imperial SEC_080 (+2 → 5 power) at
#// the base for 5.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_236
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# AttackNonImperial_NoBuff
#// SHD_236 Snowtrooper Lieutenant — attacking with a NON-Imperial unit (SOR_095, Rebel) grants no +2, so the
#// base takes its printed 3.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_236
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
