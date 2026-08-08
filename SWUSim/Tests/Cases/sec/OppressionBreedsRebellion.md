# AttackerDefeated_Draw3
#// SEC_158 Oppression Breeds Rebellion (event, cost 3) — If a friendly unit was defeated WHILE ATTACKING
#//   this phase, draw 3 cards. SEC_042 (2/2) attacks SOR_046 (3/7) and dies to the counter; then P1 plays
#//   SEC_158 → draws 3.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_042:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_158
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:3

---

# NoAttackerDefeated_NoDraw
#// SEC_158 — without a friendly unit defeated while attacking this phase, no cards are drawn. P1 just
#//   plays SEC_158 (no combat happened) → hand ends empty.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_158
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0

---

# EnemyAttackerDefeated_NoDraw
#// SEC_158 — the defeated-while-attacking unit must be FRIENDLY. Here an ENEMY unit (SOR_095 2/3) attacks
#//   into P1's SOR_046 (4/7) and dies to the counter. That is an enemy attacker defeated, not a friendly
#//   one, so playing SEC_158 draws nothing.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
WithActivePlayer: 2
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_158
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P2>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0

---

# FriendlyDefenderDefeated_NoDraw
#// SEC_158 — the friendly unit must have died WHILE ATTACKING. Here P1's SOR_095 (2/3) is defeated while
#//   DEFENDING (enemy SOR_046 4/7 attacks it), so no draw.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
WithActivePlayer: 2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_158
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P2>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0

---

# FriendlyDefeatedByAnEvent_NoDraw
#// SEC_158 Oppression Breeds Rebellion — the condition is "defeated WHILE ATTACKING", not merely
#// "defeated". A friendly unit killed by an EVENT outside combat does not arm it. P1 plays It's Worse
#// (LOF_264) on its own SEC_042, then plays SEC_158 → no cards are drawn.
## GIVEN
CommonSetup: rrw/rrk/{myResources:12}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_042:1:0
WithP1Hand: LOF_264
WithP1Hand: SEC_158
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:3

---

# FriendlyLEADERUnitDefeatedWhileAttacking_Draw3
#// SEC_158 Oppression Breeds Rebellion — a deployed LEADER is a friendly unit, so a leader that dies while
#// attacking arms the draw just like any other unit. P1's deployed Nute Gunray (TWI_002, 3/3) attacks
#// P2's AT-AT Suppressor (SOR_039, 8/8) and is defeated; P1 then plays SEC_158 and draws 3.
## GIVEN
CommonSetup: rrw/rrk/{myResources:10;myLeader:TWI_002;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_039:1:0
WithP1Hand: SEC_158
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:3
P1LEADER:NOTDEPLOYED
