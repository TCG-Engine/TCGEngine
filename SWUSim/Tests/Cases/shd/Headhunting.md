# CantAttackBases
#// SHD_145 Headhunting — "They can't attack bases for these attacks." With the opponent controlling no
#// units (only a base), P1's ready SOR_179 has no legal non-base target, so no attack is offered and the
#// opponent's base is untouched; the unit stays ready.

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
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1BASEDMG:0
