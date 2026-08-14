# OnAttackArenaAOE
#// LAW_178 Persecutor (9/7, space) — When Played/On Attack: choose an arena. You may deal 3 damage to
#// each unit in that arena. Attacks the base; choose Ground -> each ground unit takes 3 (SOR_046 3/7
#// survives at DAMAGE:3; SOR_095 3/3 dies).

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_178:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayedSpaceAOE
#// LAW_178 Persecutor (9/7, space) — When Played: choose an arena, may deal 3 to each unit in it.
#// Played from hand it enters the space arena, so choosing Space hits Persecutor itself (DAMAGE:3) and
#// each other space unit (JTL_037 4/5 -> DAMAGE:3); the enemy ground unit is untouched.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1Resources: 9
WithP1Hand: LAW_178
WithP2SpaceArena: JTL_037:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_178
P1SPACEARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OnAttackSpaceAOE
#// LAW_178 Persecutor — On Attack: choosing Space deals 3 to each space unit (Persecutor itself and the
#// enemy JTL_037 4/5 -> DAMAGE:3 each); the enemy ground unit is untouched. Persecutor still attacks the
#// base for 9.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_178:1:0
WithP2SpaceArena: JTL_037:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Space

## EXPECT
P2BASEDMG:9
P1SPACEARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_Decline_NoDamage
#// LAW_178 Persecutor — "You MAY deal 3 damage to each unit in that arena." The effect can be declined:
#// choosing Pass deals no damage. Persecutor enters the space arena undamaged and the enemy units are
#// untouched.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1Resources: 9
WithP1Hand: LAW_178
WithP2SpaceArena: JTL_037:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_178
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayedGroundAOE_HitsBothSidesNotSelf
#// COVERAGE: offer=N/A (the arena pick is an option choice — Space/Ground/Pass — not a unit pool; both
#//           arena options are exercised across the file) · decline=WhenPlayed_Decline_NoDamage +
#//           OnAttack_Decline_NoDamage (Pass in both trigger windows) ·
#//           boundary=WhenPlayedGroundAOE_HitsBothSidesNotSelf (3-damage kills the 3-HP unit, the 7-HP
#//           unit survives at 3) · control=N/A (arena-wide damage ignores control; both sides hit is
#//           asserted here) · reqboundary=the arena choice resolves in a request after the play/attack
#//           request in every section
#// LAW_178 Persecutor — choosing Ground hits EVERY ground unit, friendly and enemy alike, but not the
#// space arena: P1's own SOR_046 (3/7) takes 3, P2's SOR_095 (3/3) dies, while Persecutor (space) and
#// P2's JTL_037 are untouched.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1Resources: 9
WithP1Hand: LAW_178
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_037:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_178
P1SPACEARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P2SPACEARENAUNIT:0:DAMAGE:0

---

# OnAttack_Decline_NoDamage
#// LAW_178 Persecutor — the decline also exists in the ON ATTACK window: Persecutor attacks the base
#// for 9 and the player answers Pass on the arena choice, so no unit anywhere takes ability damage.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_178:1:0
WithP2SpaceArena: JTL_037:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Pass

## EXPECT
P2BASEDMG:9
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayedSpace_PhantomUnharmed
#// LAW_178 Persecutor vs SHD_187 Lurking TIE Phantom ("can't be captured, damaged, or defeated by enemy
#// card abilities") — choosing Space still damages Persecutor itself (own-side ability, 3) and P2's
#// JTL_037 (3), but the enemy Phantom takes 0: Persecutor's AOE is an enemy card ability from the
#// Phantom's point of view.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1Resources: 9
WithP1Hand: LAW_178
WithP2SpaceArena: [SHD_187:1:0 JTL_037:1:0]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_178
P1SPACEARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0
