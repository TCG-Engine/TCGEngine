#// VISUAL + HARNESS — crossing the 1v1 Always Two 2×2 table (docs/superpowers/specs/2026-11-20-swusim-always-two-design.md, "The 2×2
#// table" → "Crossing the table"). Seat 1 plays board 1 (bottom-LEFT, facing P4) and board 3 (bottom-RIGHT, facing P2). Each section
#// leaves ONE choice open whose legal answers sit (partly) in the OTHER column, so the table must light them there and pass the click
#// to the board that is choosing. DevTools/ui-harness/swusim-alwaystwo-xbrowser.mjs seeds each section on its own.
#// WHAT TO LOOK AT (view as playerID 1): the green "selectable" glow on the cards in the other column; clicking one answers the choice.
# Cross_Board1AttacksIntoP2
#// Board 1 (left) attacks with its Battlefield Marine. Legal: P4's unit and base (its own column) AND P2's unit and P2's base at 0 HP
#// (right column) — a 0-HP base is still attackable in Always Two.
## GIVEN
CommonSetup4P: rrk/bbw/ggk/yyw
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Base: SOR_019:25
WithP4GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:?
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:p2GroundArena-0&p2Base-0&p4GroundArena-0&p4Base-0

---

# Cross_Board3AttacksIntoP4
#// Board 3 (right) attacks. P4's unit is in the LEFT column.
## GIVEN
CommonSetup4P: rrk/bbw/ggk/yyw
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0
## WHEN
- P3>AttackGroundArena:0:?
## EXPECT
P3HASDECISION
P3SELECTABLEEXACT:p2GroundArena-0&p2Base-0&p4GroundArena-0&p4Base-0

---

# Cross_Board1PaysWithBoard3sUnit
#// Board 1's Tomb of Eilram: exhaust a friendly unit — board 1's own Marine (left) or teammate board 3's unit (right column).
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBase:LOF_028}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>UseBaseAbility
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# Cross_UpToThreeAcrossBothColumns
#// Board 1 plays IG-2000: deal 1 damage to each of up to 3 units — P4's unit (left column) and P2's unit (right column).
## GIVEN
CommonSetup4P: grk/bbk/grk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: JTL_140
WithP1Resources: 8
WithP2GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&p2GroundArena-0&p4GroundArena-0

---

# Cross_UpgradeOnBoard3sUnit
#// Board 1 plays Sabine Wren: you may defeat a non-unique upgrade — the only one is on teammate board 3's unit (right column). No enemy
#// units, so Ambush adds nothing.
## GIVEN
CommonSetup4P: ryw/bgw/ryw/bgw/{myResources:3}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3GroundArena: SOR_128:1:0
WithP3GroundArenaUpgrade: 0:SOR_120
WithP1Hand: LAW_078
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:p3GroundArena-0.u0
