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
