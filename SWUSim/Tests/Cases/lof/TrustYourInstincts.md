# UseForce_BuffedAttack
#// LOF_221 Trust Your Instincts — "Use the Force. If you do, attack with a unit. It gets +2/+0 for this
#// attack and deals combat damage before the defender." P1's 3/3 attacks the base buffed to 5 power.

## GIVEN
CommonSetup: yyw/rrk/{myResources:1;handCardIds:LOF_221}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P2BASEDMG:5

---

# DamageFirst_DefenderDefeated_NoReturn
#// LOF_221 Trust Your Instincts — the buffed attacker deals its combat damage BEFORE the defender, and if the
#// defender is defeated it deals no combat damage back. Battlefield Marine (SOR_095 3/3) buffed to 5 power
#// attacks Bossk (SOR_182 4/5): 5 damage defeats Bossk first, so Bossk deals 0 back and the Marine takes no
#// damage. (Without damage-first the Marine would take 4.)

## GIVEN
CommonSetup: yyw/rrk/{myResources:1;handCardIds:LOF_221}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_182:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NOFORCE
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# DamageFirst_DefenderSurvives_DealsBack
#// LOF_221 Trust Your Instincts — when the defender survives the damage-first hit it still deals its combat
#// damage back. The buffed Marine (5 power) attacks Consular Security Force (SOR_046 3/7): 5 < 7 so it
#// survives with 5 damage, then deals 3 back, defeating the 3-HP Marine. Confirms return damage is NOT
#// prevented when the defender lives.

## GIVEN
CommonSetup: yyw/rrk/{myResources:1;handCardIds:LOF_221}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENACOUNT:0

---

# NoForce_NoEffect
#// LOF_221 Trust Your Instincts — if the player does not control the Force, "Use the Force" fails so no attack
#// happens. The event is still played (goes to discard); the Battlefield Marine keeps its base 3 power and the
#// enemy unit takes no damage.

## GIVEN
CommonSetup: yyw/rrk/{myResources:1;handCardIds:LOF_221}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:DAMAGE:0
