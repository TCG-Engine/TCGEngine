# OnAttack_BuffOne_NoInitiative
#// TWI_085 Kalani (Unit 5/7, Ground) — "On Attack: You may choose another unit. If you have the
#// initiative, you may choose up to 2 other units instead. Give each chosen unit +2/+2 for this phase."
#// Without the initiative (P2 holds it), Kalani attacks P2's base and buffs 1 other unit (SOR_095 → 5/5).

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_085:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5

---

# OnAttack_BuffTwo_WithInitiative
#// TWI_085 Kalani — WITH the initiative, Kalani may buff up to 2 other units. Attacking P2's base, she
#// gives both SOR_095 and SEC_080 +2/+2.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: TWI_085:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1&myGroundArena-2

## EXPECT
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:2:POWER:5
