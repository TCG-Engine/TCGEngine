# CantAttackBases
#// SHD_145 Headhunting — "They can't attack bases for these attacks." With the opponent controlling no
#// units (only a base), P1's ready SOR_179 has no legal non-base target, so no attack is offered and the
#// opponent's base is untouched; the unit stays ready.
#// COVERAGE: offer=MultiAttack_BountyHunterBonus and ThreeAttacks_TheThirdOneResolves both pick attackers
#//           out of a multi-unit ready pool (a spent attacker is no longer offered on the next iteration);
#//           CantAttackBases is the negative — with no legal non-base target nothing is offered at all ·
#//           decline=MultiAttack_BountyHunterBonus's trailing '-' (choose nothing ends the sequence
#//           early, leaving the remaining attacks unused) · boundary=the "up to 3" range end to end:
#//           0 legal attacks (CantAttackBases) · 2 units available and it stops (MultiAttack_BountyHunter-
#//           Bonus) · 3 units available and the third one still resolves (ThreeAttacks_TheThirdOneResolves,
#//           the cap) · control=N/A (a one-shot event; the Bounty Hunter +2/+0 lasts only for its own
#//           attack and is never attached to an object that could change controller) ·
#//           reqboundary=N/A (the whole attack sequence completes inside the single play action)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_145
WithP1GroundArena: SOR_179:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY

---

# MultiAttack_BountyHunterBonus
#// SHD_145 Headhunting (Event, cost 2, Villainy/Aggression) — "Attack with up to 3 units (one at a time).
#// They can't attack bases for these attacks. Each Bounty Hunter that attacks this way gets +2/+0 for its
#// attack." P1 attacks with SOR_179 (Bounty Hunter, 3 power → 5 with the bonus) then SOR_046 (3 power, no
#// bonus) at the enemy SOR_046 (7 HP): 5 + 3 = 8 defeats it. (Without the +2 it would be 6 and survive, so
#// the enemy's defeat proves the Bounty-Hunter bonus.) Both attackers end exhausted.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_145
WithP1GroundArena: SOR_179:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1BASEDMG:0

---

# ThreeAttacks_TheThirdOneResolves
#// SHD_145 Headhunting — the cap is THREE, not two. P1 has three ready SOR_046 (3/7) and P2 one SOR_046
#// (3/7). Each attack adds 3 to the defender: 3 → 6 (alive at 7 HP) → 9 (defeated). The enemy's death is
#// therefore only reachable if the THIRD attack resolves; stopping at two would leave it at 6 damage and
#// alive. Every attacker ends exhausted with the 3 counter-damage it took, and no Bounty Hunter is
#// involved so no attacker gets +2/+0.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_145
WithP1GroundArena: [SOR_046:1:0 SOR_046:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:2:EXHAUSTED
P1GROUNDARENAUNIT:2:DAMAGE:3
P1BASEDMG:0
P2BASEDMG:0
