# CompletesAttack_DefenderDefeated_Heal
#// SHD_059 Embo (3-cost 3/4 ground, Vigilance, Underworld/Bounty Hunter) — "When this unit completes an
#// attack: If the defender was defeated, heal up to 2 damage from a unit." Embo (3 power) attacks and
#// defeats SOR_128 (3/1), taking 3 counter (survives at 4 HP). Defender defeated → onAttackEnd heals the
#// damaged friendly SOR_046 by 2 (2 damage → 0). Both Embo and SOR_046 are damaged, so the pick is explicit.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_059:1:0
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SHD_059
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# CompletesAttack_DefenderSurvives_NoHeal
#// SHD_059 Embo — the heal is gated on the defender being defeated. Embo (3 power) attacks SOR_046 (3/7),
#// which survives → SWU_LAST_DEFENDER_DEFEATED is not set → no heal offer. The damaged friendly SEC_080
#// stays at 2 damage, and there is no pending decision.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_059:1:0
WithP1GroundArena: SEC_080:1:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:DAMAGE:2
P1NODECISION
