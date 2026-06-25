# VISUAL CHECK — double-digit damage on large units and bases (20+)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor to confirm the damage badge stays
# legible with two-digit numbers (badge size, centering, text fit).
#
# NON-LEADER units only (leaders get exhausted / cause wonkiness when they collide).
# Ground non-leader units cap at 10 printed HP, so each carries 3-4 Experience tokens
# (SOR_T01, +1/+1 each) to lift HP into the teens — enough to survive double-digit
# damage. Damage is kept below the buffed HP so nothing is defeated and the cards render:
#   Ground:
#     LOF_073 Mythosaur 10 HP + 3 XP = 13 HP → 11 damage
#     LOF_170 Bendu     10 HP + 4 XP = 14 HP → 12 damage
#   Space (high-HP non-leaders, no XP needed):
#     ASH_083 Summa-verminoth 15 HP → 13 damage
#     JTL_090 Executor        12 HP → 11 damage
# Both bases carry 20+ damage (bases are 25-30 HP, so 22 / 24 survive):
#   myBaseDamage 22, theirBaseDamage 24
#
# What to look at:
#   • The center damage badge on every unit + both bases reads a clean two-digit
#     number without clipping/overflow.
#   • The XP-buffed ground units show their raised HP/power on the corner badges.
#   • No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
CommonSetup: bbk/grw/{myBaseDamage:22;theirBaseDamage:24}
WithP1GroundArena: LOF_073:1:11
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1SpaceArena: ASH_083:1:13
WithP2GroundArena: LOF_170:1:12
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2SpaceArena: JTL_090:1:11

## WHEN

## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:1
P2GROUNDARENACOUNT:1
P2SPACEARENACOUNT:1
