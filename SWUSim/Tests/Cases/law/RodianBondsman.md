# WhenDefeatedEachCredit
#// LAW_116 Rodian Bondsman (2/3) — When Defeated: each player creates a Credit token. Attacks SOR_046
#// (3/7) and dies; both players gain a Credit.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_116:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:1

---

# WhenDefeatedByEvent
#// LAW_116 Rodian Bondsman — the When Defeated ability also fires when defeated by an event, not just in
#// combat. P1 plays Vanquish (SOR_078) on the lone enemy Rodian; both players still gain a Credit.

## GIVEN
CommonSetup: bbw/bgw/{myResources:5}
WithP1Hand: SOR_078
WithP2GroundArena: LAW_116

## WHEN
- P1>PlayHand:0
- P2>Drain

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P2CREDITCOUNT:1
