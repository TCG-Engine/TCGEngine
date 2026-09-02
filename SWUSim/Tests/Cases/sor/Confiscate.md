# DefeatsEnemyUpgrade
#// COVERAGE: offer=N/A - STRUCTURAL: "an upgrade" is wholly unqualified, so every upgrade in play is
#//           legal and there is no exclusion to assert; the both-sides reading is proven behaviourally
#//           by DefeatsEnemyUpgrade + DefeatsOwnUpgrade, and MultipleUpgrades proves the pool is a real
#//           multi-candidate choose rather than an auto-resolve.
#//           decline=N/A - STRUCTURAL: printed mandatory.
#//           boundary=N/A - no threshold. · control=N/A - STRUCTURAL: an Event, fixed caster, and the
#//           text names no owner-scoped zone.
#//           reqboundary=N/A - STRUCTURAL: single decision, resolved in the same step it is raised;
#//           nothing is written before it and read behind it.
#//           modes=2P only - no player reference, no friendly/enemy wording.
#// SOR_251 Confiscate — defeat upgrade on enemy unit (auto-resolve)
#// Single upgraded unit → no choice needed; upgrade goes to P2's discard.

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P1RESAVAILABLE:0

---

# DefeatsOwnUpgrade
#// SOR_251 Confiscate — can target own unit's upgrade
#// Only P1's unit has an upgrade → auto-selects it; upgrade goes to P1's discard.

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:LOF_215
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:2
P1RESAVAILABLE:0

---

# MultipleUnits
#// SOR_251 Confiscate — multiple upgraded units, player chooses target
#// P1 and P2 each have one upgrade → player must choose which unit to target.
#// Choosing P2's unit leaves P1's upgrade intact.

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_215
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# MultipleUpgrades
#// SOR_251 Confiscate — single unit with two upgrades, player picks which to defeat
#// P2 unit has LOF_215 (index 0) and SOR_215 (index 1). Player defeats index 1
#// via the staged TempZone pick (myTempZone-1 → matchIdx[1]). One upgrade remains.

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215
WithP2GroundArenaUpgrade: 0:SOR_215

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
