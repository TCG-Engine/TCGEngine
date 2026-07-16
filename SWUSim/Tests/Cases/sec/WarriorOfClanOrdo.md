# CantDisclose_2ToOwnBase
#// SEC_164 Warrior of Clan Ordo — no Aggression card in hand → can't disclose → deal 2 to your own
#//   base automatically (no decision offered).

## GIVEN
CommonSetup: rrw/grw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_164:1:0

## WHEN
- P1>AttackGroundArena:0

## EXPECT
P2BASEDMG:3
P1BASEDMG:2
P1NODECISION

---

# Decline_2ToOwnBase
#// SEC_164 Warrior of Clan Ordo — decline the disclose → "if you don't" deals 2 to your own base.

## GIVEN
CommonSetup: rrw/grw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_164:1:0
WithP1Hand: SEC_133

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1BASEDMG:2
P1NODECISION

---

# Disclose_NoPenalty
#// SEC_164 Warrior of Clan Ordo (Ground, 3/3, Aggression) — On Attack: you may disclose Aggression.
#//   If you DON'T, deal 2 damage to your base.
#// SEC_164 attacks P2 base (3 power). On Attack: disclose SEC_133 (Aggression) → no penalty to own base.

## GIVEN
CommonSetup: rrw/grw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_164:1:0
WithP1Hand: SEC_133

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:3
P1BASEDMG:0
P1NODECISION
