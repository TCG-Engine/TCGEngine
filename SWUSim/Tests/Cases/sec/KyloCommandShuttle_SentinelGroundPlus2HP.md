# SEC_032 Kylo Ren's Command Shuttle (Space, 3/5) — Each friendly ground unit with Sentinel gets +0/+2.
#   The Sentinel SOR_063 (2/4) becomes 2/6.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1Hand: SEC_032

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:HP:6
P1NODECISION
