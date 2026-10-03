# VISUAL CHECK — Twin Suns 4-player, EVERY zone of EVERY seat is populated (2026-10-03)
#
# Visual-only schema (Tests/Visual/ is NOT scanned by the regression). Load it in the Test Schema
# Editor (zzTestSchemaEditor.php), then view it as EACH seat in turn (View-as / playerID=1..4):
#   http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=N&playerID=1
#
# WHY THIS EXISTS: every other Twin Suns visual fixture seeds one or two zones (arenas, bases, leaders)
# and leaves the rest EMPTY. An empty hand / deck / discard / resource row takes no room, so a layout
# that only fits when those zones are bare looks fine everywhere else. This board is a "mid-game"
# state where nothing is empty — the realistic worst-ordinary case, not a stress test (for the
# stress test see TwinSuns_4P_AllZonesOverflow.md).
#
# WHAT TO LOOK AT — repeat for playerID=1, 2, 3 AND 4 (each seat is "you" once):
#   1. YOUR board: hand row shows 6 cards; deck pile shows a count of 10; discard pile shows its top
#      card (count 6); resources show 8 cards — 4 EXHAUSTED + 4 ready — PLUS 2 Credit tokens; the
#      Force token is shown. ⚠ Nothing may overlap the arenas, the base, or the action/prompt bar.
#   2. YOUR leaders: TWO leaders over the base. The FIRST is DEPLOYED (deployed styling in the leader
#      zone AND a leader unit in the ground arena); the SECOND is undeployed and EXHAUSTED.
#   3. YOUR ground arena = 3 units + the deployed leader unit LAST (4 total):
#        unit 0 wears a Shield + an Experience token (two token pips/subcards on one host),
#        unit 1 wears Academy Training (a real, non-token upgrade),
#        unit 2 holds a CAPTIVE (an enemy unit — captive styling, not an upgrade).
#      ⚠ The damaged units must show their damage counter alongside the upgrade badges.
#   4. YOUR space arena = 3 units: unit 0 is PILOTED (Clone Pilot JTL_108 — pilot styling), unit 1
#      is damaged (3) AND shielded, unit 2 is bare.
#   5. YOUR base: damage counter + the grey FORTIFY tab (1 upgrade) + the goldenrod ARREST tab
#      (1 captive). All three readable at once, no overlap.
#   6. The THREE opponent preview tiles (HOME view): each shows two leaders (one deployed), base with
#      damage + both tabs, 4 ground / 3 space units with their badges, and its hand / deck /
#      discard / resource counts. ⚠ Every tile must stay the SAME SIZE — a fuller seat must not grow
#      its tile. Hidden zones (hand, deck) must show BACKS/COUNTS only, never faces.
#   7. Click into each matchup (you vs one) and back: the full-size opponent half shows all the same
#      zones, and the hidden-zone rule still holds.
#
# BOARD SHAPE — why each element exists (don't gut this fixture):
#   • HAND / DISCARD / DECK cards are DISTINCT PER SEAT, one set each — P1 SOR, P2 SHD, P3 TWI,
#     P4 JTL — and no card repeats across a seat's three zones. So a preview, popup or zoom that
#     shows the WRONG seat's pile is obvious at a glance (mobile report 2026-10-03: "when I switch
#     to another player and click the discard it always shows the same discard").
#   • Seat-distinct units in every arena so a cross-seat render mix-up is visible by card art.
#   • Shield+Experience on ONE host = the multi-token pip case; Academy Training = the non-token case;
#     a captive = the captive-subcard case; a pilot = the IsPilot case. Four subcard flavours per seat.
#   • A deployed + an undeployed leader per seat (Twin Suns = two leaders/seat, always).
#   • Mixed ready/exhausted resources + Credits so the resource row shows BOTH states and the tokens.
#   • Base upgrade + base captive so both base tabs render together with damage.
#   Casts reuse the corpus convention: P1 Vader+Kylo, P2 Gideon+Bossk, P3 Cad Bane+Aphra,
#   P4 Maul+Pre Vizsla (all Villainy).
#
# Neighbouring invariants to re-check while here: the active-seat highlight (P1 active), the order
# strip, the per-seat playmat behind each preview tile, and ?swuLayout=mobile at 390px wide.
#
# CROSS-BROWSER: verify in Chromium, Firefox AND Safari/WebKit (CLAUDE.md). If WebKit can't be
# launched, say so — don't imply coverage.
#
# No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
#// P1/P2 leaders come from CommonSetup; the FIRST leader of each is deployed (CARDID:ready:deployed),
#// the second is undeployed + exhausted (CARDID:ready=0). Seat 1/2 base damage goes through
#// CommonSetup (my/theirBaseDamage) — WithP{n}Base only applies to seats 3/4.
CommonSetup: rrk/bbw/{myLeader:IBH_053:1:1; myLeader2:SHD_011:0; theirLeader:SHD_007:1:1; theirLeader2:SHD_010:0; myBaseDamage:9; theirBaseDamage:6}
WithSeatOrder: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithRound: 4

#// ── Seat 1 ─────────────────────────────────────────────────────────────────────────────────
WithP1GroundArena: [SOR_032:1:0 SOR_033:0:2 SOR_228:1:0]
WithP1SpaceArena:  [SOR_031:1:0 SOR_040:0:3 SOR_225:1:0]
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 1:SOR_120
WithP1GroundArenaCaptive: 2:SOR_095:2
WithP1SpaceArenaPilot:    0:JTL_108
WithP1SpaceArenaUpgrade:  1:SOR_T02
WithP1BaseUpgrade: HMW_081
WithP1BaseCaptive: SOR_128:3
WithP1Resources: 4:SOR_074:0,4:SOR_123:1
WithP1Credits: 2
WithP1Force: true
WithP1Hand:    [SOR_031 SOR_034 SOR_037 SOR_040 SOR_043 SOR_046]
WithP1Discard: [SOR_049 SOR_052 SOR_055 SOR_058 SOR_061 SOR_064]
WithP1Deck:    [SOR_067 SOR_070 SOR_073 SOR_076 SOR_079 SOR_082 SOR_085 SOR_088 SOR_091 SOR_094]

#// ── Seat 2 ─────────────────────────────────────────────────────────────────────────────────
WithP2GroundArena: [SOR_034:1:0 SOR_035:0:1 SOR_164:1:0]
WithP2SpaceArena:  [SOR_050:1:0 SOR_052:0:3 SOR_231:1:0]
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 1:SOR_120
WithP2GroundArenaCaptive: 2:SOR_095:3
WithP2SpaceArenaPilot:    0:JTL_108
WithP2SpaceArenaUpgrade:  1:SOR_T02
WithP2BaseUpgrade: HMW_081
WithP2BaseCaptive: SOR_128:4
WithP2Resources: 4:SOR_074:0,4:SOR_123:1
WithP2Credits: 2
WithP2Force: true
WithP2Hand:    [SHD_027 SHD_030 SHD_033 SHD_036 SHD_039 SHD_042]
WithP2Discard: [SHD_045 SHD_048 SHD_051 SHD_054 SHD_057 SHD_060]
WithP2Deck:    [SHD_063 SHD_066 SHD_069 SHD_072 SHD_075 SHD_078 SHD_081 SHD_084 SHD_087 SHD_090]

#// ── Seat 3 ─────────────────────────────────────────────────────────────────────────────────
WithP3Leader:  SHD_014:1:1
WithP3Leader2: SHD_015:0
WithP3Base: SOR_026:12
WithP3GroundArena: [SOR_036:1:0 SOR_037:0:2 SOR_229:1:0]
WithP3SpaceArena:  [SOR_060:1:0 SOR_066:0:3 SOR_132:1:0]
WithP3GroundArenaUpgrade: 0:SOR_T02
WithP3GroundArenaUpgrade: 0:SOR_T01
WithP3GroundArenaUpgrade: 1:SOR_120
WithP3GroundArenaCaptive: 2:SOR_095:4
WithP3SpaceArenaPilot:    0:JTL_108
WithP3SpaceArenaUpgrade:  1:SOR_T02
WithP3BaseUpgrade: HMW_081
WithP3BaseCaptive: SOR_128:1
WithP3Resources: 4:SOR_074:0,4:SOR_123:1
WithP3Credits: 2
WithP3Force: true
WithP3Hand:    [TWI_031 TWI_034 TWI_037 TWI_040 TWI_043 TWI_046]
WithP3Discard: [TWI_049 TWI_052 TWI_055 TWI_058 TWI_061 TWI_064]
WithP3Deck:    [TWI_067 TWI_070 TWI_073 TWI_076 TWI_079 TWI_082 TWI_085 TWI_088 TWI_091 TWI_094]

#// ── Seat 4 ─────────────────────────────────────────────────────────────────────────────────
WithP4Leader:  TWI_009:1:1
WithP4Leader2: TWI_010:0
WithP4Base: SOR_026:18
WithP4GroundArena: [SOR_038:1:0 SOR_039:0:2 SOR_230:1:0]
WithP4SpaceArena:  [SOR_086:1:0 SOR_089:0:3 SOR_134:1:0]
WithP4GroundArenaUpgrade: 0:SOR_T02
WithP4GroundArenaUpgrade: 0:SOR_T01
WithP4GroundArenaUpgrade: 1:SOR_120
WithP4GroundArenaCaptive: 2:SOR_095:1
WithP4SpaceArenaPilot:    0:JTL_108
WithP4SpaceArenaUpgrade:  1:SOR_T02
WithP4BaseUpgrade: HMW_081
WithP4BaseCaptive: SOR_128:2
WithP4Resources: 4:SOR_074:0,4:SOR_123:1
WithP4Credits: 2
WithP4Force: true
WithP4Hand:    [JTL_032 JTL_035 JTL_038 JTL_041 JTL_044 JTL_047]
WithP4Discard: [JTL_050 JTL_053 JTL_056 JTL_059 JTL_062 JTL_065]
WithP4Deck:    [JTL_068 JTL_071 JTL_074 JTL_077 JTL_080 JTL_083 JTL_086 JTL_089 JTL_092 JTL_095]

## WHEN

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:6
P2HANDCOUNT:6
P3HANDCOUNT:6
P4HANDCOUNT:6
P1DISCARDCOUNT:6
P2DISCARDCOUNT:6
P3DISCARDCOUNT:6
P4DISCARDCOUNT:6
P1DECKCOUNT:10
P2DECKCOUNT:10
P3DECKCOUNT:10
P4DECKCOUNT:10
P1RESCOUNT:8
P2RESCOUNT:8
P3RESCOUNT:8
P4RESCOUNT:8
P1CREDITCOUNT:2
P2CREDITCOUNT:2
P3CREDITCOUNT:2
P4CREDITCOUNT:2
P1HASFORCE
P2HASFORCE
P3HASFORCE
P4HASFORCE
P1GROUNDCOUNT:4
P2GROUNDCOUNT:4
P3GROUNDCOUNT:4
P4GROUNDCOUNT:4
P1SPACECOUNT:3
P2SPACECOUNT:3
P3SPACECOUNT:3
P4SPACECOUNT:3
P1LEADERCOUNT:2
P2LEADERCOUNT:2
P3LEADERCOUNT:2
P4LEADERCOUNT:2
P1LEADER0DEPLOYED:true
P1LEADER1DEPLOYED:false
P2LEADER0DEPLOYED:true
P2LEADER1DEPLOYED:false
P3LEADER0DEPLOYED:true
P3LEADER1DEPLOYED:false
P4LEADER0DEPLOYED:true
P4LEADER1DEPLOYED:false
P1BASEUPGRADECOUNT:1
P2BASEUPGRADECOUNT:1
P3BASEUPGRADECOUNT:1
P4BASEUPGRADECOUNT:1
P1BASECAPTIVECOUNT:1
P2BASECAPTIVECOUNT:1
P3BASECAPTIVECOUNT:1
P4BASECAPTIVECOUNT:1
P1BASEDMG:9
P2BASEDMG:6
P3BASEDMG:12
P4BASEDMG:18
#// Pin each subcard flavour to its intended host (UPGRADECOUNT counts captives/pilots too), and the
#// deployed leader unit's slot (appended after the seeded units → ground index 3).
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_120
P1GROUNDARENAUNIT:2:UPGRADE:0:CARDID:SOR_095
P1GROUNDARENAUNIT:3:CARDID:IBH_053
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_108
P1SPACEARENAUNIT:1:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_120
P2GROUNDARENAUNIT:2:UPGRADE:0:CARDID:SOR_095
P2GROUNDARENAUNIT:3:CARDID:SHD_007
P2SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_108
P2SPACEARENAUNIT:1:SHIELDCOUNT:1
P3GROUNDARENAUNIT:0:UPGRADECOUNT:2
P3GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_120
P3GROUNDARENAUNIT:2:UPGRADE:0:CARDID:SOR_095
P3GROUNDARENAUNIT:3:CARDID:SHD_014
P3SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_108
P3SPACEARENAUNIT:1:SHIELDCOUNT:1
P4GROUNDARENAUNIT:0:UPGRADECOUNT:2
P4GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_120
P4GROUNDARENAUNIT:2:UPGRADE:0:CARDID:SOR_095
P4GROUNDARENAUNIT:3:CARDID:TWI_009
P4SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_108
P4SPACEARENAUNIT:1:SHIELDCOUNT:1
