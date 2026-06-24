# ASH_055 Blade of Talzin (Upgrade, +2/+1) — When Defeated: if it was on a friendly Night unit, return it
# from your discard pile to your hand. LOF_031 (Force,Night, 2/4 → 4/5 with the Blade) is pre-damaged and
# dies attacking SOR_046; the Blade is defeated off a Night host, so it returns to P1's hand.
## GIVEN
CommonSetup: bbk/bbk
WithP1GroundArena: LOF_031:1:3
WithP1GroundArenaUpgrade: 0:ASH_055
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
