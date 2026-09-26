# OnAttack_CaptureBaseAttacker
#// SHD_088 Ephant Mon (5-cost, Command/Villainy ground) — "On Attack: Choose an enemy non-leader unit that
#// attacked your base this phase. A friendly unit in the same arena captures that unit." P2's SEC_080 attacks
#// P1's base; then Ephant Mon attacks and P1 has the friendly SOR_095 capture SEC_080.

## GIVEN
CommonSetup: ggk/ggk
WithActivePlayer: 2
WithP1GroundArena: SHD_088:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0

---

# ThreeSeat_AttackedAnotherSeatsBase_NotCapturable
#// SHD_088 says "attacked YOUR base this phase". Here P2's SEC_080 attacks P3's base — not P1's — so it
#// is NOT a legal capture target for P1 and Ephant Mon's On Attack must offer nothing.
#//
#// ⚠ THE BUG: the pool was built from SWU_DEALT_BASEDMG_{uid}, which records only "this unit damaged A
#// base" — the flag carries no base owner at all (see CombatLogic's note: those markers "need a seat
#// suffix bolted on to say WHICH"). At two seats "a base" and "your base" are the same base, so the
#// section above cannot tell them apart. At 3+ seats it let you capture a unit that never touched you.

## GIVEN
CommonSetup3P: ggk/ggk/ggk
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SHD_088:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:P3B
- P3>Pass
- P1>AttackGroundArena:0:P2B

## EXPECT
P2GROUNDARENACOUNT:1
P1NODECISION

---

# ZeroPowerAttacker_StillAttackedYourBase
#// "ATTACKED your base" is not "DAMAGED your base". SHD_028 has 0 power, so its attack on P1's base deals
#// nothing — but it still attacked, and is a legal capture target.
#//
#// ⚠ The old pool read SWU_DEALT_BASEDMG_{uid}, which CombatLogic stamps only inside `if ($attackPower
#// > 0)`. Its own comment at the Overwhelm site already draws this distinction — "it is combat damage to
#// the base but the unit is NOT considered to have ATTACKED that base ... deliberately NOT
#// SWU_DEALT_BASEDMG (the 'attacked' flag used by SHD_088/SHD_106)" — so the damage gate was a proxy,
#// never the condition. SWU_MYBASE_ATTACKEDBY_{uid}, stamped on the ATTACKED BASE'S OWNER, is the real one.

## GIVEN
CommonSetup: ggk/ggk
WithActivePlayer: 2
WithP1GroundArena: SHD_088:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_028:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1BASEDMG:0
P2GROUNDARENACOUNT:0
