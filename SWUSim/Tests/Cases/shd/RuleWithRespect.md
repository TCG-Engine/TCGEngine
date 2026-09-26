# RuleWithRespect_CaptureBaseAttackers
#// SHD_106 Rule with Respect — "A friendly unit captures each enemy non-leader unit that attacked your
#// base this phase." P1 passes; P2's SHD_095 attacks P1's base (marking it a base-attacker); P1 then plays
#// SHD_106 and has SOR_046 capture SHD_095.

## GIVEN
CommonSetup: ggw/ggk/{myResources:4}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_106
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# ThreeSeat_AttackedAnotherSeatsBase_NotCaptured
#// SHD_106 captures "each enemy non-leader unit that attacked YOUR base this phase". P2's SHD_095 attacks
#// P3's base, not P1's, so P1's Rule with Respect must capture nothing and SHD_095 stays in play.
#//
#// ⚠ Same root cause as SHD_088: the pool read SWU_DEALT_BASEDMG_{uid}, which records "damaged A base"
#// with no owner. Two seats cannot show it — there, the only base that is not yours is theirs.

## GIVEN
CommonSetup3P: ggw/ggk/ggk
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 4
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_106
WithP2GroundArena: SHD_095:1:0

## WHEN
- P2>AttackGroundArena:0:P3B
- P3>Pass
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# FourSeat_AttackedAnotherSeatsBase_NotCaptured
#// 4P sibling: seat 2's unit attacks SEAT 3's base and seat 4's attacks seat 3's too. Neither attacked
#// SEAT 1's base, so Rule with Respect captures nothing and both stay in play.

## GIVEN
CommonSetup4P: ggw/ggk/ggk/ggk
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 4
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_106
WithP2GroundArena: SHD_095:1:0
WithP4GroundArena: SHD_095:1:0

## WHEN
- P2>AttackGroundArena:0:P3B
- P3>Pass
- P4>AttackGroundArena:0:P3B
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P2GROUNDARENACOUNT:1
P4GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
