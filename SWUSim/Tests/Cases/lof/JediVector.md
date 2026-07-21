# JediAndLightsaber
#// LOF_244 Jedi Vector (1/3) — "+1/+0 if you control another Jedi unit and +1/+0 if you control a
#// Lightsaber upgrade." With the Jedi Plo Koon (carrying a Lightsaber), it is 1 + 1 + 1 = 3 power.

## GIVEN
CommonSetup: bbw/rrk
WithP1SpaceArena: LOF_244:1:0
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:SOR_053

## EXPECT
P1SPACEARENAUNIT:0:POWER:3

---

# Alone_NoBonus
#// LOF_244 — with no other Jedi and no Lightsaber it is just its base 1/3.
## GIVEN
CommonSetup: bbw/rrk
WithP1SpaceArena: LOF_244:1:0
## EXPECT
P1SPACEARENAUNIT:0:POWER:1
P1SPACEARENAUNIT:0:HP:3

---

# EnemyJediOnly_NoBonus
#// LOF_244 — an ENEMY Jedi unit (Youngling Padawan) does not count; still 1/3.
## GIVEN
CommonSetup: bbw/rrk
WithP1SpaceArena: LOF_244:1:0
WithP2GroundArena: LOF_193:1:0
## EXPECT
P1SPACEARENAUNIT:0:POWER:1
P1SPACEARENAUNIT:0:HP:3

---

# FriendlyJediOnly_Plus1
#// LOF_244 — a friendly Jedi (Youngling Padawan) grants +1/+0 → 2/3.
## GIVEN
CommonSetup: bbw/rrk
WithP1SpaceArena: LOF_244:1:0
WithP1GroundArena: LOF_193:1:0
## EXPECT
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:3

---

# EnemyLightsaberOnly_NoBonus
#// LOF_244 — an ENEMY Lightsaber upgrade does not count; still 1/3.
## GIVEN
CommonSetup: bbw/rrk
WithP1SpaceArena: LOF_244:1:0
WithP2GroundArena: SHD_055:1:0
WithP2GroundArenaUpgrade: 0:SOR_053
## EXPECT
P1SPACEARENAUNIT:0:POWER:1
P1SPACEARENAUNIT:0:HP:3

---

# FriendlyLightsaberOnly_Plus1
#// LOF_244 — a friendly Lightsaber upgrade on a non-Jedi carrier grants +1/+0 → 2/3.
## GIVEN
CommonSetup: bbw/rrk
WithP1SpaceArena: LOF_244:1:0
WithP1GroundArena: SHD_055:1:0
WithP1GroundArenaUpgrade: 0:SOR_053
## EXPECT
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:3
