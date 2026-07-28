# AttackBaseSelfDefeat
#// LAW_205 Flash the Vents (Aggression event, cost 1) — "Attack with a unit. It gets +2/+0 and gains
#// Overwhelm for this attack. After completing this attack, if that unit damaged a base, defeat that
#// unit." SEC_080 (power 3) attacks the base for 3+2 = 5, then self-defeats.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# OverwhelmSpillDefeatsAttacker
#// LAW_205 attacks an enemy unit; the granted Overwhelm spills the excess onto the base, so the attacker
#// self-defeats. SEC_080 (3/3, +2 = power 5) attacks IBH_063 (1/3): 3 defeats it, 2 overwhelm to the base;
#// SEC_080 survives combat but is defeated for having damaged a base.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: IBH_063:1:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:2
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# SurvivesNoBaseDamageNotDefeated
#// LAW_205 attacks a high-HP unit with no overwhelm spill; no base damage means the attacker is NOT defeated.
#// SEC_080 (+2 = power 5) attacks LOF_112 (2/6): 5 damage, no spill; LOF_112 deals 2 back, SEC_080 survives.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LOF_112:1:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:CARDID:LOF_112
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1

---

# NoReadyUnitToAttack
#// LAW_205 played with no unit that can attack (only an exhausted unit) does nothing but is still spent.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1Hand: LAW_205

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P2BASEDMG:0
P1DISCARDCOUNT:1
