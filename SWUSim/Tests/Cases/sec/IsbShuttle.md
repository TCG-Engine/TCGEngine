# FriendlyDefeated_CreatesSpy
#// SEC_083 ISB Shuttle (Space, 3/2, Command/Villainy) — When Played: if a friendly unit was defeated
#//   this phase, create a Spy token. P1's SOR_095 attacks LAW_124 and dies (sets SWU_FRIENDLY_DEFEATED);
#//   then P1 plays SEC_083 → create a Spy.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SEC_083

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_083
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1NODECISION

---

# NoFriendlyDefeated_NoSpy
#// SEC_083 ISB Shuttle — no friendly unit defeated this phase → no Spy token.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_083

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_083
P1GROUNDARENACOUNT:0
P1NODECISION
