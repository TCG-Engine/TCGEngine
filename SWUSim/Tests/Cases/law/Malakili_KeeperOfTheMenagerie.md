# GrantsUnderworldToCreature
#// LAW_212 Malakili (2/4) — "Each friendly Creature unit ... gains the Underworld trait." SOR_164 is a
#// Creature but NOT natively Underworld; with Malakili in play it counts as Underworld, so LAW_249 Black
#// Sun Cabalist (When Played: give an Experience token to another friendly Underworld unit) can target
#// it. Choosing SOR_164 (the granted unit) makes it 5/6. Without the grant SOR_164 wouldn't be a legal
#// target and the choice would auto-resolve to Malakili instead — so SOR_164 ending at 5/6 proves the
#// grant works.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_212:1:0
WithP1GroundArena: SOR_164:1:0
WithP1Hand: LAW_249

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_164
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:6

---

# EnemyCreatureNotUnderworld_FriendlyOnlyGrant
#// LAW_212 Malakili — the grant applies only to Creatures you control; enemy Creatures do NOT gain
#// Underworld. P1 has Malakili (Underworld) + SOR_164 Wampa (friendly Creature, granted Underworld); P2 has
#// LOF_044 Loth-Wolf (enemy Creature). LAW_249 Black Sun Cabalist ("give Experience to another friendly
#// Underworld unit") can select exactly Malakili and Wampa — NOT the enemy Loth-Wolf. The target decision
#// stays pending to prove the enemy Creature is excluded from the granted-Underworld set. (Verified genuine:
#// without Malakili, Wampa loses the grant, leaving a single legal target that auto-resolves with no prompt.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [LAW_212:1:0 SOR_164:1:0]
WithP2GroundArena: LOF_044:1:0
WithP1Hand: LAW_249

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
