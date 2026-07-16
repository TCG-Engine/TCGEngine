# Attackable_WhileReady
#// TWI_195 Sabine Wren — while READY she CAN be attacked (the protection is exhausted-only). P2's
#// SOR_046 (3 power) attacks the ready Sabine (4 HP) → she takes 3 damage.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: TWI_195:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# CantBeAttacked_WhileExhausted
#// TWI_195 Sabine Wren (Unit 4/4, Ground) — "While this unit is exhausted, she can't be attacked
#// (unless she gains Sentinel)." Sabine is exhausted (Status 0); P2's SOR_046 tries to attack her → the
#// attack is blocked and she survives undamaged.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: TWI_195:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# OnAttack_OffAspectDiscard_Deal2
#// TWI_195 Sabine Wren — "On Attack: You may discard a card from your deck. If it doesn't share an
#// aspect with your base, deal 2 damage to a ground unit." Base is Cunning (y). Sabine attacks P2's base
#// (4 damage); on YES she discards the top card SOR_128 (Aggression/Villainy — no Cunning) → off-aspect →
#// deal 2 to the enemy SOR_046.

## GIVEN
CommonSetup: yyw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_195:1:0
WithP1Deck: SOR_128
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:2
P1DISCARDCOUNT:1

---

# OnAttack_SharedAspect_NoDamage
#// TWI_195 Sabine Wren — when the discarded card SHARES an aspect with the base (base Cunning; top card
#// TWI_205 is Cunning), no damage is dealt.

## GIVEN
CommonSetup: yyw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_195:1:0
WithP1Deck: TWI_205
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1
