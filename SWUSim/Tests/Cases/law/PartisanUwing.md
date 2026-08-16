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

---

# CreditIfFriendlyDefeated_SurvivesTheRequestBoundary
#// LAW_161 Partisan U-Wing — "if a friendly unit was defeated this phase" is a phase-scoped flag written by
#// one action and read by a later one; in production those are separate processes, so the flag must be part
#// of the serialized gamestate rather than an in-memory global.
#// Extends CreditIfFriendlyDefeated with an intervening interactive decision so the boundary lands on a REAL
#// pending choose: SOR_128 (3/1) attacks SOR_046 and dies (flag set), P1 then plays Daring Raid (SHD_178)
#// whose "deal 2 to a unit or base" pick is a genuine multi-candidate MZCHOOSE, and a request boundary is
#// inserted before that answer. The U-Wing is played afterwards and must still find the flag set.

## GIVEN
CommonSetup: ggw/bgw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: [SHD_178 LAW_161]

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_161
P2BASEDMG:2

---

# NoCreditWhenOnlyAPilotUPGRADEWasDefeated
#// LAW_161 Partisan U-Wing — "if a friendly UNIT was defeated this phase". A Pilot played as an upgrade is
#// an UPGRADE, not a unit, so defeating it must not arm the Credit. JTL_046 Paige Tico rides SOR_237
#// Alliance X-Wing; SOR_251 Confiscate defeats the upgrade (the lone legal target, so the choice
#// auto-resolves) while the host X-Wing survives untouched, and the U-Wing then finds no friendly unit
#// defeat and creates no Credit.
#// The surviving host is the load-bearing control: UPGRADECOUNT:0 with the X-Wing still in the arena and a
#// discard of 2 (Confiscate + Paige) proves something really WAS defeated, so the "no Credit" result
#// cannot be passing merely because nothing died. This distinguishes it from
#// NoCreditWhenOnlyEnemyDefeated, where the defeat was on the other side of the table.
#// (Previously classified as a correct-but-hard-to-encode negative and left un-guarded; it encodes fine.)
#// COVERAGE: this sharpens the first section's ledger, which recorded this scenario as deliberately
#//           un-encoded — it is now encoded here, so treat that ledger note as superseded.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_046
WithP1Hand: [SOR_251 LAW_161]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:LAW_161
P1DISCARDCOUNT:2
