# 2ExpToRebel
#// SOR_241 Wing Leader (Space, 2/1) — When Played: give 2 Experience tokens to another
#// friendly REBEL unit. P1's Battlefield Marine (SOR_095, Rebel, 3/3) is the only other
#// Rebel → auto-receives +2/+2 (→ 5/5).
#// COVERAGE: offer=Offer_OtherFriendlyRebelsOnly (pending SELECTABLEEXACT: excludes self,
#//           non-Rebel friendlies, and enemy Rebels) · decline=N/A (mandatory give, no
#//           "you may") · control=N/A (one-shot token grant at play time; nothing to follow) ·
#//           boundary=StacksOntoExistingExperience_ThreeTotal (token stacking edge) ·
#//           reqboundary=N/A (resolves inside the play ceremony)

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;handCardIds:SOR_241}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# StacksOntoExistingExperience_ThreeTotal
#// SOR_241 Wing Leader — Intended: the 2 Experience tokens stack onto a unit that ALREADY has
#// one. Battlefield Marine (3/3) starts with 1 Experience (4/4); the sole other friendly Rebel
#// auto-receives 2 more → 3 Experience total, 6/6.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;handCardIds:SOR_241}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6

---

# Offer_OtherFriendlyRebelsOnly
#// SOR_241 Wing Leader — Intended: the give-2-Experience pool is "another friendly REBEL unit"
#// only. P1 controls two ground Rebels (SOR_095, SOR_046) and a non-Rebel Vehicle (SOR_249);
#// P2 controls a Rebel (SOR_095). The pick is left PENDING: the pool must hold exactly the two
#// friendly Rebels — not the non-Rebel, not the enemy Rebel, and not the just-played Wing
#// Leader itself ("another").

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;handCardIds:SOR_241}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_249:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
