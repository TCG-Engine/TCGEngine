# WhenDefeated_CreateSpy
#// SEC_132 Imperial Occupier (Ground, 2/2, Aggression/Villainy) — When Defeated: create a Spy token.
#// SEC_132 attacks LAW_124 (4/7) and dies → its When Defeated creates a Spy.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_132:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1DISCARDCOUNT:1
P1NODECISION

---

# NGOR_SpyToCaster
#// SEC_132 Imperial Occupier — When Defeated: create a Spy token. P1 plays No Glory, Only Results
#//   (JTL_043) to take control of the ENEMY Occupier and then defeat it. Because control transfers to
#//   the No Glory caster first, the When Defeated resolves for P1, so the Spy token is created on P1's side.

## GIVEN
CommonSetup: rrk/grw/{myResources:13}
P1OnlyActions: true
WithP1Hand: JTL_043
WithP2GroundArena: SEC_132:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1NODECISION
