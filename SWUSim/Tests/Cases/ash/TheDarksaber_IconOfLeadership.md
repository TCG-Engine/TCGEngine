# Darksaber_AttachesUniqueNonVehicle
#// ASH_135 The Darksaber — "Attach to a <uq> non-Vehicle unit." Positive host case (guards over-blocking).
#// Board has a valid host: LAW_139 Admiral Motti (unique=true, Imperial/Official, NO Vehicle trait).
#// Darksaber is a legal fit → it attaches. Proves the restriction doesn't over-block a valid host.
#// Darksaber is Command, cost 4 → ggw covers it, 4 resources.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4;handCardIds:ASH_135}
P1OnlyActions: true
WithP1GroundArena: LAW_139:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:ASH_135

---

# Darksaber_CantAttachNonUnique
#// ASH_135 The Darksaber — "Attach to a <uq> non-Vehicle unit." Host-restriction (unique half).
#// Board has ONLY a non-unique non-Vehicle unit (SOR_095 Battlefield Marine, unique=false) — it is a
#// non-Vehicle, so this isolates the *unique* rule: the only reason it's an illegal host is that it
#// isn't unique. Darksaber has no valid host → no-op, card stays in hand, the unit stays bare.
#// Darksaber is Command, cost 4 → ggw covers it, 4 resources.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4;handCardIds:ASH_135}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Darksaber_CantAttachVehicle
#// ASH_135 The Darksaber — "Attach to a <uq> non-Vehicle unit." Host-restriction (Vehicle half).
#// Board has ONLY a unique Vehicle (SOR_089, unique Imperial Capital Ship) — it IS unique, so this
#// isolates the *non-Vehicle* rule: the only reason it's an illegal host is its Vehicle trait.
#// Darksaber has no valid host → the play is a no-op, the card stays in hand, the Vehicle stays bare.
#// Darksaber is Command, cost 4 → ggw (Command base + Command/Heroism leader) covers it, 4 resources.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4;handCardIds:ASH_135}
P1OnlyActions: true
WithP1SpaceArena: SOR_089:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:4
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# Darksaber_LeaderUnit
#// ASH_135 The Darksaber — "Attached unit is a leader unit." A friendly LAW_139 Admiral Motti ("friendly
#// leader units get +2/+2") sees the Darksaber-wearing SOR_046 as a leader unit, so it gets the +2/+2 on
#// top of its 7/9 (Darksaber stats) → 9/11. (Without the leader-unit grant it would stay 7/9.)
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
WithP1GroundArena: LAW_139:1:0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:9
P1GROUNDARENAUNIT:0:HP:11

---

# Darksaber_MandalorianTrait
#// ASH_135 The Darksaber — "Attached unit gains the Mandalorian trait." A friendly ASH_113 Mandalorian
#// Flagship ("+1/+0 for each OTHER friendly Mandalorian unit") counts the Darksaber-wearing SOR_046 (now
#// Mandalorian) and gets +1 → 5 power (base 4). SOR_046 is normally Rebel/Trooper, so without the grant
#// ASH_113 would stay at 4.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_113:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## EXPECT
P1SPACEARENAUNIT:0:POWER:5

---

# Darksaber_NoAspectWithoutIt
#// ASH_135 — control: without the Darksaber, SOR_046 provides no aspect icons, so P1 (Cunning/Villainy)
#// faces the full +2 Heroism penalty on SOR_237 (cost 2 → 4) and cannot afford it on 2 resources — the
#// unit stays in hand. Proves the aspect provision comes from the Darksaber.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_237}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1

---

# Darksaber_ProvidesAspect
#// ASH_135 The Darksaber — "While you are paying costs, the attached unit provides its aspect icons." P1
#// (Cunning/Villainy, no Heroism) plays SOR_237 (cost 2, mono-Heroism) on exactly 2 resources: the +2
#// off-aspect Heroism penalty is waived because the Darksaber-wearing SOR_046 (Vigilance/Heroism) provides
#// Heroism. The unit enters play with 0 resources left.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_237}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:1
P1RESAVAILABLE:0

---

# Darksaber_Stats
#// ASH_135 The Darksaber (Upgrade, +4/+2) attached to SOR_046 (3/7) — the host becomes a 7/9.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:9
