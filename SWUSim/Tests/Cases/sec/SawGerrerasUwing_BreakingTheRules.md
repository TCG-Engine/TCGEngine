# AttackEnd_AttackWithAggression
#// SEC_174 Saw Gerrera's U-Wing (Space, 4/8) — Saboteur + "When this unit completes an attack (and
#//   survives): you may attack with another Aggression unit." SEC_174 attacks P2's base (4); on attack-end
#//   the Aggression unit SEC_134 attacks the base too (3) → base 7 total.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_174:1:0
WithP1GroundArena: SEC_134:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:7

---

# AttackEnd_GrantedAttackMayTargetAUnit
#// SEC_174 Saw Gerrera's U-Wing — the granted attack is a normal attack, so the chosen Aggression unit
#// may attack a UNIT rather than the base. SEC_174 hits P2's base for 4, then SEC_134 attacks P2's
#// SOR_128 (3/1) and defeats it. The base takes only the U-Wing's 4.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_174:1:0
WithP1GroundArena: SEC_134:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:0

---

# UWingDoesNotSurviveItsAttack_NoGrantedAttack
#// SEC_174 Saw Gerrera's U-Wing — "when this unit completes an attack (AND SURVIVES)". The U-Wing (8 HP)
#// attacks JTL_069 and dies to the counter, so the follow-up attack is never offered and SEC_134 stays
#// ready with P2's base untouched.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_174:1:7
WithP1GroundArena: SEC_134:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
P1NODECISION
