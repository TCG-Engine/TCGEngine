# CreditIfFriendlyDefeated
#// LAW_161 Partisan U-Wing (Command, cost 5, space) — When Played: if a friendly unit was defeated this
#// phase, create a Credit token. SOR_128 (3/1) attacks into SOR_046 and dies, then the U-Wing creates a Credit.
#// COVERAGE: offer=N/A (no target picker; the friendly-defeated check is automatic) · decline=N/A (no
#//           "you may" — conditional automatic effect) · boundary pair=CreditIfFriendlyDefeated (flag
#//           set -> Credit) + NoCreditWhenOnlyEnemyDefeated (only an ENEMY died -> none). Intended: a
#//           friendly Pilot defeated as an UPGRADE also does not count as a unit defeat — verified
#//           correct in the engine (only unit defeats set the flag) and deliberately left un-encoded
#//           (hard-to-encode negative, low value) · control=N/A (seat-level phase flag, no per-unit
#//           marker) · reqboundary=CreditIfFriendlyDefeated (the defeat and the U-Wing play are separate
#//           requests; the phase flag survives the boundary)

## GIVEN
CommonSetup: ggw/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_161

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:1

---

# NoCreditWhenOnlyEnemyDefeated
#// LAW_161 — the Credit is created only if a FRIENDLY unit was defeated this phase. Here a friendly
#// Consular Security Force (3/7) attacks and defeats an enemy Death Star Stormtrooper (3/1) while itself
#// surviving; no friendly was defeated, so the U-Wing makes no Credit.

## GIVEN
CommonSetup: ggw/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: LAW_161

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:0
P2GROUNDARENACOUNT:0
