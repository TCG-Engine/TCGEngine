# OnAttack_Decline
#// SEC_137 — the double-power is a "may". Declining → SEC_137 deals its base 2 to P2's base.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_137:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:2

---

# OnAttack_DoublePower
#// SEC_137 Dryden Vos (Ground, 2/5) — On Attack: you may double this unit's power for this attack. P1
#//   accepts → SEC_137 deals 2×2 = 4 to P2's base.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_137:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
