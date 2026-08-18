# WhenPlayedExhaustsHost
#// LAW_127 Kill Switch (Upgrade, -1/-1, cost 2, Vigilance) — "When Played: Exhaust attached unit."
#// Played onto the ready SEC_080 → it becomes EXHAUSTED and is 2/2 (3/3 with -1/-1).

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_127

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2

---

# WhenPlayedDefeatsHostAtZeroHP
#// LAW_127 Kill Switch (-1/-1) — attaching it reduces the host's HP; if that drops the host to 0 remaining
#// HP, the host is defeated by the no-remaining-HP state-based check (no damage needed). Played onto the
#// friendly SOR_128 Death Star Stormtrooper (3/1 → 2/0): the unit is defeated, sending BOTH it and the
#// Kill Switch upgrade to P1's discard.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1Hand: LAW_127

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# AlreadyExhaustedHost_StaysExhaustedAndTakesTheStatHit
#// LAW_127 Kill Switch — the When Played exhaust is a one-shot on entry, so attaching it to a host that is
#// ALREADY exhausted is legal and simply leaves it exhausted; the upgrade's -1/-1 still applies (SEC_080
#// 3/3 -> 2/2). Both existing sections attach to a ready host, so neither covers the no-op branch.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1Hand: LAW_127

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2

---

# TheHostReadiesNormallyAtTheNextRegroup
#// LAW_127 Kill Switch — "When Played: Exhaust attached unit" is a one-time effect, NOT a standing
#// can't-ready lock (contrast LAW_077 Shadow of Stygeon Prime, which is). The host it exhausted on entry
#// readies again at the regroup with the upgrade still attached, and the -1/-1 persists. Both decks are
#// seeded so the regroup draws add no CR 6.1 empty-deck damage.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_127
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2

---

# AttachPool_AnyUnitEitherSideEitherArena
#// LAW_127 Kill Switch — the card prints NO attach restriction, so per CR 2.e its legal-host pool is every
#// unit in play regardless of controller or arena. That matters more here than on any other upgrade in the
#// set: Kill Switch is -1/-1 plus "When Played: exhaust attached unit", a pure drawback with no upside, so
#// the enemy half of the pool is the only reason to play the card at all. Discriminating board: a friendly
#// ground unit, a friendly space unit, an enemy ground unit and an enemy space unit are all legal hosts.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: LAW_127

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# OnAnENEMYHost_ItExhaustsAndShrinksTHEIRUnit
#// LAW_127 Kill Switch — the play the card is actually for. Attached to an ENEMY ready SOR_046 (3/7), the
#// When Played exhausts it and the upgrade's -1/-1 leaves it a 2/6. Both existing behaviour sections put it
#// on a friendly unit, so neither shows the card doing its job.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_127

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6
