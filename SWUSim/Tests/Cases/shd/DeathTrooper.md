# WhenPlayed_DamageBothSides
#// SHD_030 Death Trooper (3-cost 3/3 ground, Villainy/Vigilance) — "When Played: Deal 2 damage to a
#// friendly ground unit and 2 damage to an enemy ground unit." Two sequential targeted damages. With a
#// second friendly ground unit present the friendly pick is a real MZCHOOSE (Death Trooper itself also
#// qualifies); the single enemy ground unit auto-resolves. Both chosen units take 2 and survive.

## GIVEN
CommonSetup: brk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_030
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:CARDID:SHD_030
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenPlayed_SelfTarget_EnemyFizzle
#// SHD_030 Death Trooper — the friendly-ground target is mandatory and Death Trooper itself always
#// qualifies, so with no other friendly unit it auto-resolves onto itself (takes 2, survives 3 HP). With
#// no enemy ground unit the enemy half fizzles cleanly (no crash, no dangling decision).

## GIVEN
CommonSetup: brk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_030

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_030
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:0
P1NODECISION
