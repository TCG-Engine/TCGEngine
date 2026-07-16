# SithGrit
#// LOF_238 Darth Revan's Lightsabers — Attach to a non-Vehicle unit. If attached unit is a Sith, it gains
#// Grit. SOR_038 (a Sith) gets Grit; a non-Sith would not.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: SOR_038:1:0
WithP1GroundArenaUpgrade: 0:LOF_238

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit

---

# TokenUnit
## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 3
WithP1Hand:LOF_238

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1
P1HANDCOUNT:0
