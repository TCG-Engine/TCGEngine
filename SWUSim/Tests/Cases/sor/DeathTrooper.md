# Deals2And2
#// SOR_033 Death Trooper (3/3) — When Played: deal 2 to a friendly ground unit AND 2
#// to an enemy ground unit. P1 already has Battlefield Marine (SOR_095); chosen as the
#// friendly target it takes 2. P2's Consular Security Force (SOR_046) is the only enemy
#// ground unit → auto-takes 2.
#// COVERAGE: offer=Offer_FriendlyGroundOnly_IncludesSelf + Offer_EnemyGroundOnly (pending
#//           SELECTABLEEXACT for each half: ground-only pools, self included, space units on
#//           both sides excluded) · decline=N/A (both damage halves are mandatory — no "you
#//           may") · control=N/A (one-shot When Played damage) ·
#//           boundary=NoEnemyGround_FriendlyHalfStillResolves (empty enemy half no-ops while the
#//           friendly half still resolves) vs full resolution (DamagesItself) ·
#//           reqboundary=DamagesItself (play → friendly pick → enemy pick span separate requests)

## GIVEN
CommonSetup: bbk/bbk/{myResources:3;handCardIds:SOR_033}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # friendly target — index 0
WithP2GroundArena: SOR_046:1:0    # enemy target

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Offer_FriendlyGroundOnly_IncludesSelf
#// SOR_033 Death Trooper — the FRIENDLY pick is ground-only and includes Death Trooper
#// himself (he is in play by When Played resolution): pool = Marine + Death Trooper, and
#// the friendly SPACE unit is excluded. Two candidates → the pick stays pending.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3;handCardIds:SOR_033}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # friendly ground candidate
WithP1SpaceArena: SOR_060:1:0     # friendly SPACE — must NOT be offered
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Offer_EnemyGroundOnly
#// SOR_033 Death Trooper — the ENEMY pick is ground-only: after the friendly half resolves,
#// the enemy pool is both enemy ground units and excludes the enemy SPACE unit. Two
#// candidates → the pick stays pending.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3;handCardIds:SOR_033}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_060:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# DamagesItself
#// SOR_033 Death Trooper — he can be his own friendly target: picking himself puts 2 damage
#// on Death Trooper (3/3 → survives at 2), then 2 on the chosen enemy ground unit; neither
#// space unit is touched.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3;handCardIds:SOR_033}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_060:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_033
P1GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0

---

# NoEnemyGround_FriendlyHalfStillResolves
#// SOR_033 Death Trooper — with NO enemy ground units (enemy space only) the friendly half
#// still resolves as a mandatory pick: Death Trooper targets himself for 2; the enemy half
#// has no candidates and no-ops; no prompt is left hanging.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3;handCardIds:SOR_033}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_060:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_033
P1GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION
