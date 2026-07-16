# FriendlyHit_NoSelfBase
#// SEC_169 AAT Incinerator — if a friendly unit IS damaged by the ability, no self-base damage. Hit one
#//   friendly SOR_046 → no penalty.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SEC_169

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:0
P1NODECISION

---

# NoFriendlyHit_SelfBase
#// SEC_169 AAT Incinerator (Unit, Aggression, cost 5) — When Played: deal 1 to each of up to 4 OTHER
#//   ground units; if no friendly units were damaged, deal 2 to your base. Hit two enemies only → 2 to own base.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_169

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P1BASEDMG:2
P1NODECISION
