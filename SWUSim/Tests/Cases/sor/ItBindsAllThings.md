# ChooseHealLessThan3
#// SOR_075 It Binds All Things — "Heal UP TO 3" — the player may choose to heal LESS than 3 even when
#// more damage is available. SOR_046 has 3 damage, but P1 chooses to heal only 1 (NUMBERCHOOSE → 1):
#// SOR_046 is left at DAMAGE:2, and "deal that much" deals only 1 to the enemy (LAW_124 → DAMAGE:1).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# DeclineDeal
#// SOR_075 It Binds All Things — the conditional damage is optional ("you may deal"). With a Force unit
#// present, P1 heals 3 from SOR_046 but DECLINES the deal (AnswerDecision:-); the enemy is untouched.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:3
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# ForceUnit_HealAndDeal
#// SOR_075 It Binds All Things (Vigilance event, cost 2, Force) — "Heal up to 3 damage from a unit. If
#// you control a FORCE unit, you may deal that much damage to another unit." P1 controls a Force unit
#// (SOR_049 Obi-Wan). Healing 3 from the damaged SOR_046 (damage 3 → 0) then deals that 3 to the enemy
#// LAW_124 (4/7 → DAMAGE:3).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:3
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# HealLessThan3_DealsThatMuch
#// SOR_075 It Binds All Things — "deal that much" equals the amount ACTUALLY healed. The heal amount is
#// capped at the unit's damage: the chosen unit only has 2 damage, so the NUMBERCHOOSE max is 2; healing
#// 2 (→ 0) makes the conditional deal 2, not 3. LAW_124 (4/7) takes DAMAGE:2.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:2
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NoForceUnit_HealOnly
#// SOR_075 It Binds All Things — without a friendly FORCE unit, only the heal happens; no damage may be
#// dealt. P1 heals 3 from SOR_046 (damage 3 → 0); no deal decision is offered and the enemy is untouched.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:3

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
