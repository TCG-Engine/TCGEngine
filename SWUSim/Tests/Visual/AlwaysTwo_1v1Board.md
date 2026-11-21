#// VISUAL — 1v1 Always Two board (docs/superpowers/specs/2026-11-20-swusim-always-two-design.md, "The 2×2 table"). Seat 1 plays boards
#// 1+3 (Red), seat 2 plays boards 2+4 (Blue). Open it as playerID 1 (the Test Schema Editor seat link): the 2×2 table — board 1
#// bottom-LEFT facing P4 above it, board 3 bottom-RIGHT facing P2 above it, the four bases meeting in the middle (left column's bases on
#// its right edge, right column's on its left edge), log / chat / undo down the right. playerID 2 is the mirror (board 2 left facing
#// P3, board 4 right facing P1). Board 3's base is at 0 (still in the game). Red's turn, board 1 holds the initiative, nobody has taken
#// it. Each board has units in both arenas so the layout is judged with a real board, not empty lanes. Harness:
#// DevTools/ui-harness/swusim-alwaystwo-xbrowser.mjs (layout) and swusim-alwaystwo-crosstable-xbrowser.mjs (crossing the table).
# AlwaysTwo_1v1_RedTurn
## GIVEN
CommonSetup4P: rrk/bbw/ggk/yyw/{myResources:6;theirResources:5;myBaseDamage:12;theirBaseDamage:8}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:25
WithP4Base: SOR_021:0
WithP3Resources: 4
WithP4Resources: 4
WithP1Hand: [SOR_046 SOR_095 SEC_111 ASH_110 HMW_254]
WithP3Hand: [LOF_191 JTL_103 SOR_128]
WithP2Hand: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP4Hand: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_225:1:0
WithP3GroundArena: SOR_128:1:0
WithP3GroundArena: SOR_243:0:0
WithP2GroundArena: SOR_098:1:0
WithP2GroundArena: SOR_095:1:1
WithP2SpaceArena: SOR_225:1:0
WithP4GroundArena: SEC_025:0:0
## WHEN
## EXPECT
SEATCOUNT:4
