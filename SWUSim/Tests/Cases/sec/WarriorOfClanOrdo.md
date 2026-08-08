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

---

# NonAggressionCardInHand_CantDisclose
#// SEC_164 Warrior of Clan Ordo — a non-Aggression card in hand (SOR_232 AT-ST, no Aggression icon)
#//   cannot satisfy the disclose, so no prompt is offered and the "if you don't" clause auto-deals 2 to
#//   your own base.

## GIVEN
CommonSetup: rrw/grw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_164:1:0
WithP1Hand: SOR_232

## WHEN
- P1>AttackGroundArena:0

## EXPECT
P2BASEDMG:3
P1BASEDMG:2
P1NODECISION

---

# SelfBaseDamage_TriggersBobaNonCombatReaction
#// SEC_164 Warrior of Clan Ordo dealing 2 to YOUR OWN base is "you deal non-combat damage", so JTL_009
#//   Boba Fett's "When you deal non-combat damage: you may exhaust this leader → deal 1 indirect" fires.
#//   Warrior (power 3) attacks P2's base (3 combat); with no Aggression to disclose it deals 2 to P1's own
#//   base; Boba then exhausts to deal 1 indirect to P2 → P2 base 3+1=4, P1 base 2, Boba exhausted.
#//   (Regression: self-base damage must attribute the DEALER as its controller, not the opponent.)

## GIVEN
CommonSetup: rrk/rrk/{myLeader:JTL_009}
WithActivePlayer: 1
WithP1GroundArena: SEC_164:1:0
WithP1Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:4
P1BASEDMG:2
P1LEADER:EXHAUSTED

---

# EmptyHand_NoDisclosePrompt_AutoSelfDamage
#// SEC_164 Warrior of Clan Ordo — the empty-hand boundary of the "cannot disclose" case. With NO cards
#// in hand at all there is nothing to reveal, so no prompt appears and the "if you don't" clause deals
#// 2 to your own base automatically. Distinct from the section above, where a card was held but carried
#// the wrong aspect.

## GIVEN
CommonSetup: rrw/grw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_164:1:0

## WHEN
- P1>AttackGroundArena:0

## EXPECT
P2BASEDMG:3
P1BASEDMG:2
P1HANDCOUNT:0
P1NODECISION
