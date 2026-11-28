#// VISUAL — 4P Always Two, the partner vote (docs/superpowers/specs/2026-11-20-swusim-always-two-design.md, "## 4P Always Two").
#// Four seats, one board each: Red = seats 1+3, Blue = 2+4. Red's turn; board 1 has just proposed playing a Battlefield Marine.
#// WHAT TO LOOK AT: seat 3's page shows "P1 wants to play Battlefield Marine. Accept?" with Yes / No; seat 1's page says it is
#// waiting for P3; Zoom In on board 3 from seat 1 shows board 3's hand face-up. Harness: DevTools/ui-harness/swusim-alwaystwo4p-xbrowser.mjs.
# AlwaysTwo_4p_ProposalPending
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 6
WithP3Resources: 4
WithP1Hand: [SOR_095 SOR_095]
WithP3Hand: [SOR_098 SOR_128 SOR_046]
WithP2Hand: [SOR_095 SOR_095 SOR_095]
WithP4Hand: [SOR_095]
## WHEN
- P1>PlayHand:0
## EXPECT
P3HASDECISION
