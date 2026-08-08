# HealAndExp
#// SHD_075 Covert Strength (1-cost event) — "Heal 2 damage from a unit and give an Experience token
#// to it." Single friendly target (2-damaged marine) → auto-resolve: damage 0, +1 Experience → 4/4.

## GIVEN
CommonSetup: bbw/bbw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_075
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1DISCARDCOUNT:1

---

# SmuggledEvent_ResolvesAndGoesToDiscard
#// SHD_075 Covert Strength played via SMUGGLE (from resources) must resolve like any other event: heal 2
#// and give an Experience token, then go to the DISCARD — and the spent slot is replaced from the deck
#// (CR 8.22.g). REGRESSION GUARD: an event smuggled from resources used to fall through the UNIT path in
#// SWUSmuggleResource and be ADDED TO AN ARENA as a bogus "unit" — its effect never resolved and it never
#// reached the discard. Events now delegate to ActivateCard (as upgrades and Plot already did).
## GIVEN
CommonSetup: bbw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:4
WithP1Resources: 1:SHD_075:1,10:SOR_095:1
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>SmuggleResource:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
P1RESCOUNT:11
