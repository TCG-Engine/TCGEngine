# ThirdSeatReactor_IsOfferedTheChoice
#// TS26_78 Barriss Offee — "When an enemy unit attacks: you MAY give an Experience token to that unit." Bug report
#// P-25 (game 5089, a three-seat Twin Suns game): P3 controlled Barriss and P2's units attacked; the report says
#// P3 was forced to give the Experience, with no prompt to skip it. Here P2's SEC_080 attacks P1's base and P3,
#// a THIRD seat that is neither attacker nor defender, is the reactor. The offer is left PENDING: it must be P3's
#// own declinable pick (MZMAYCHOOSE), the attacker its only candidate, and the attack must not resolve under it.

## GIVEN
CommonSetup: yyk/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithGamePhase: ActionPhase
WithActivePlayer: 2
WithP3GroundArena: TS26_78:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:P1B

## EXPECT
P3HASDECISION
P3DECISIONTYPE:MZMAYCHOOSE
P2GROUNDARENAUNIT:0:POWER:3
P1BASEDMG:0

---

# ThirdSeatReactor_Declines_NoToken
#// TS26_78 — the decline branch from the third seat: P3 passes, so SEC_080 stays 3 power and deals 3 to P1's base.

## GIVEN
CommonSetup: yyk/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithGamePhase: ActionPhase
WithActivePlayer: 2
WithP3GroundArena: TS26_78:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:P1B
- P3>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1BASEDMG:3
