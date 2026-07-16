# DeclineAttack
#// SOR_055 The Force Is With Me — the attack is optional ("You may attack"). With a Force unit present,
#// Obi-Wan still gets +2 Experience and a Shield, but P1 DECLINES the attack: he stays ready and the
#// enemy base is untouched.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_049:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_055

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:0

---

# ForceUnit_ExpShieldAttack
#// SOR_055 The Force Is With Me (Vigilance/Heroism event, cost 4, Force) — "Choose a friendly unit and
#// give 2 Experience tokens to it. If you control a FORCE unit, also give a Shield token to it. You may
#// attack with the chosen unit." P1 chooses SOR_049 Obi-Wan (a Force unit, 4/6): +2 Experience → 5/8,
#// +1 Shield (Force controlled), then attacks the enemy base for 5.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_049:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_055

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:6
P2BASEDMG:6

---

# NoForceUnit_ExpAndAttackNoShield
#// SOR_055 The Force Is With Me — without a friendly FORCE unit, the chosen unit gets 2 Experience but
#// NO Shield. P1 chooses SOR_095 (3/3, non-Force): +2 Experience → 5/5, no shield, then attacks the
#// enemy base for 5.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_055

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:POWER:5
P2BASEDMG:5

---

# NoFriendlyUnit_Fizzle
#// SOR_055 The Force Is With Me — with no friendly unit to choose, the event fizzles cleanly: no
#// decision is offered and it simply resolves to the discard pile.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_055

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P2BASEDMG:0
