# OnAttack_Decline_NoDefeatNoDraw
#// TWI_035 Morgan Elsbeth — the "may" is optional: declining (AnswerDecision:-) keeps the friendly unit
#// and draws nothing.

## GIVEN
CommonSetup: bbk/rrw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_035:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:0

---

# OnAttack_DefeatFriendlyDraw
#// TWI_035 Morgan Elsbeth (Unit 3/6, Ground, cost 4, Vigilance/Villainy, Force/Imperial/Official) —
#// Restore 1 + "On Attack: You may defeat another friendly unit. If you do, draw a card." Attacking the
#// enemy base: Restore 1 heals P1's base (3 → 2), then defeating the friendly SOR_095 draws a card.

## GIVEN
CommonSetup: bbk/rrw/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: TWI_035:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_035
P1HANDCOUNT:1
P1BASEDMG:2
