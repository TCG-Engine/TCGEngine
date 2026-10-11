# VISUAL CHECK — Twin Suns 4-player STRESS: every zone of every seat filled PAST a normal game (2026-10-03)
#
# Visual-only schema (Tests/Visual/ is NOT scanned by the regression). Load it in the Test Schema
# Editor (zzTestSchemaEditor.php), then view it as EACH seat in turn (View-as / playerID=1..4):
#   http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=N&playerID=1
#
# WHY THIS EXISTS: TwinSuns_4P_AllZonesFilled.md is the ordinary mid-game board. This is its
# worst-case sibling — every seat at the same time has a crowded board, so any zone that overflows,
# wraps, scrolls, or pushes a neighbour shows up here, and it shows up on ALL FOUR SEATS AT ONCE
# (one oversized seat is easy to fit; four of them in the preview strip is the real test).
#
# Per seat: 8 ground units (6 + BOTH leaders deployed), 7 space units, 12 hand, 20 discard, 30 deck,
# 12 resources (6 exhausted / 6 ready) + 5 Credits, Force token, base at heavy damage with 3 Fortify
# upgrades AND 3 arrested captives.
#
# WHAT TO LOOK AT — repeat for playerID=1, 2, 3 AND 4 (each seat is "you" once):
#   1. YOUR arenas: 8 ground / 7 space units all reachable — either they fit, or the arena scrolls /
#      compresses DELIBERATELY. ⚠ A unit clipped off-screen with no way to reach it is the bug; so is
#      an arena that grows and shoves the base, the hand, or the prompt bar out of the viewport.
#   2. The SUBCARD STACK on ground unit 0: 5 subcards (2 Shields, 2 Experience, Academy Training).
#      The pips/badges must stay legible and inside the card — no spill onto the neighbouring unit.
#      Space unit 0: pilot + Shield + Experience (pilot styling distinct from upgrades).
#      Ground unit 1: TWO captives owned by two DIFFERENT seats.
#   3. YOUR leaders: BOTH deployed — both leader slots show deployed styling, and the two leader units
#      are the LAST two ground units.
#   4. YOUR hand: 12 cards — the hand row fans/overlaps or scrolls; every card must still be hoverable
#      and the hover lift must not be clipped by the viewport.
#   5. YOUR resources: 12 resources (mixed ready/exhausted) + 5 Credit tokens — the row must not wrap
#      into the hand or the arenas; the ready count must be readable.
#   6. YOUR piles: deck count 30, discard count 20 (top card visible); the discard popup lists all 20
#      and scrolls.
#   7. YOUR base: damage 2-digit counter + grey FORTIFY tab "3" + goldenrod ARREST tab "3", no overlap.
#   8. The THREE opponent preview tiles: still the SAME SIZE as on the AllZonesFilled board (a crowded
#      seat must NOT grow its tile); 8 ground / 7 space thumbnails fit or compress; badges stay on the
#      right unit; hidden zones show counts/backs only.
#   9. Click into each matchup and back; repeat 1–7 for the full-size opponent half.
#  10. ?swuLayout=mobile at 390px wide — this is where overflow bites first.
#
# BOARD SHAPE — why each element exists (don't gut this fixture):
#   • HAND / DISCARD / DECK cards are DISTINCT PER SEAT, one set each — P1 SOR, P2 SHD, P3 TWI,
#     P4 JTL — and no card repeats across a seat's three zones. So a preview, popup or zoom that
#     shows the WRONG seat's pile is obvious at a glance (mobile report 2026-10-03: "when I switch
#     to another player and click the discard it always shows the same discard").
#   • 8/7 units is above what a real Twin Suns board usually holds — that's the point; trimming it
#     turns this back into AllZonesFilled.
#   • Damage is only placed on units whose HP exceeds it (a lethal-damage unit would read as a bug).
#   • Captives on ground unit 1 come from two different seats so per-owner captive rendering is
#     exercised; base captives likewise.
#   Casts reuse the corpus convention: P1 Vader+Kylo, P2 Gideon+Bossk, P3 Cad Bane+Aphra,
#   P4 Maul+Pre Vizsla (all Villainy).
#
# CROSS-BROWSER: verify in Chromium, Firefox AND Safari/WebKit (CLAUDE.md). If WebKit can't be
# launched, say so — don't imply coverage.
#
# No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
#// BOTH leaders of every seat are deployed (CARDID:ready:deployed). Seat 1/2 base damage MUST go
#// through CommonSetup (my/theirBaseDamage) — WithP{n}Base only applies to seats 3/4.
CommonSetup: rrk/bbw/{myLeader:IBH_053:1:1; myLeader2:SHD_011:1:1; theirLeader:SHD_007:1:1; theirLeader2:SHD_010:1:1; myBaseDamage:21; theirBaseDamage:15}
WithSeatOrder: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithRound: 7

#// ── Seat 1 ─────────────────────────────────────────────────────────────────────────────────
WithP1GroundArena: [SOR_032:1:0 SOR_033:0:2 SOR_228:1:0 SOR_229:0:1 SOR_240:1:0 SOR_227:0:0]
WithP1SpaceArena:  [SOR_031:1:0 SOR_040:0:5 SOR_225:1:0 SOR_231:0:1 SOR_132:1:0 SOR_134:0:3 SOR_185:1:0]
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaCaptive: 1:SOR_095:2
WithP1GroundArenaCaptive: 1:SOR_046:3
WithP1SpaceArenaPilot:    0:JTL_108
WithP1SpaceArenaUpgrade:  0:SOR_T02
WithP1SpaceArenaUpgrade:  0:SOR_T01
WithP1BaseUpgrade: [HMW_081 HMW_171 HMW_205]
WithP1BaseCaptive: SOR_128:2
WithP1BaseCaptive: SOR_095:3
WithP1BaseCaptive: SOR_046:4
WithP1Resources: 6:SOR_074:0,6:SOR_123:1
WithP1Credits: 5
WithP1Force: true
WithP1Hand:    [SOR_031 SOR_034 SOR_037 SOR_040 SOR_043 SOR_046 SOR_049 SOR_052 SOR_055 SOR_058 SOR_061 SOR_064]
WithP1Discard: [SOR_067 SOR_070 SOR_073 SOR_076 SOR_079 SOR_082 SOR_085 SOR_088 SOR_091 SOR_094 SOR_097 SOR_100 SOR_103 SOR_106 SOR_109 SOR_112 SOR_115 SOR_118 SOR_121 SOR_124]
WithP1Deck:    [SOR_127 SOR_130 SOR_133 SOR_136 SOR_139 SOR_142 SOR_145 SOR_148 SOR_151 SOR_154 SOR_157 SOR_160 SOR_163 SOR_166 SOR_169 SOR_172 SOR_175 SOR_178 SOR_181 SOR_184 SOR_187 SOR_190 SOR_193 SOR_196 SOR_199 SOR_202 SOR_205 SOR_208 SOR_211 SOR_214]

#// ── Seat 2 ─────────────────────────────────────────────────────────────────────────────────
WithP2GroundArena: [SOR_034:1:0 SOR_035:0:1 SOR_164:1:0 SOR_232:0:4 SOR_204:1:0 SOR_213:0:2]
WithP2SpaceArena:  [SOR_050:1:0 SOR_052:0:5 SOR_237:1:0 SOR_111:0:0 SOR_147:1:2 SOR_144:0:0 SOR_099:1:3]
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaCaptive: 1:SOR_095:3
WithP2GroundArenaCaptive: 1:SOR_046:4
WithP2SpaceArenaPilot:    0:JTL_108
WithP2SpaceArenaUpgrade:  0:SOR_T02
WithP2SpaceArenaUpgrade:  0:SOR_T01
WithP2BaseUpgrade: [HMW_081 HMW_171 HMW_205]
WithP2BaseCaptive: SOR_128:3
WithP2BaseCaptive: SOR_095:4
WithP2BaseCaptive: SOR_046:1
WithP2Resources: 6:SOR_074:0,6:SOR_123:1
WithP2Credits: 5
WithP2Force: true
WithP2Hand:    [SHD_027 SHD_030 SHD_033 SHD_036 SHD_039 SHD_042 SHD_045 SHD_048 SHD_051 SHD_054 SHD_057 SHD_060]
WithP2Discard: [SHD_063 SHD_066 SHD_069 SHD_072 SHD_075 SHD_078 SHD_081 SHD_084 SHD_087 SHD_090 SHD_093 SHD_096 SHD_099 SHD_102 SHD_105 SHD_108 SHD_111 SHD_114 SHD_117 SHD_120]
WithP2Deck:    [SHD_123 SHD_126 SHD_129 SHD_132 SHD_135 SHD_138 SHD_141 SHD_144 SHD_147 SHD_150 SHD_153 SHD_156 SHD_159 SHD_162 SHD_165 SHD_168 SHD_171 SHD_174 SHD_177 SHD_180 SHD_183 SHD_186 SHD_189 SHD_192 SHD_195 SHD_198 SHD_201 SHD_204 SHD_207 SHD_210]

#// ── Seat 3 ─────────────────────────────────────────────────────────────────────────────────
WithP3Leader:  SHD_014:1:1
WithP3Leader2: SHD_015:1:1
WithP3Base: SOR_026:24
WithP3GroundArena: [SOR_036:1:0 SOR_037:0:3 SOR_230:1:0 SOR_226:0:0 SOR_129:1:1 SOR_083:0:0]
WithP3SpaceArena:  [SOR_060:1:0 SOR_066:0:2 SOR_178:1:0 SOR_209:0:1 SOR_208:1:0 SOR_212:0:2 SOR_090:1:6]
WithP3GroundArenaUpgrade: 0:SOR_T02
WithP3GroundArenaUpgrade: 0:SOR_T02
WithP3GroundArenaUpgrade: 0:SOR_T01
WithP3GroundArenaUpgrade: 0:SOR_T01
WithP3GroundArenaUpgrade: 0:SOR_120
WithP3GroundArenaCaptive: 1:SOR_095:4
WithP3GroundArenaCaptive: 1:SOR_046:1
WithP3SpaceArenaPilot:    0:JTL_108
WithP3SpaceArenaUpgrade:  0:SOR_T02
WithP3SpaceArenaUpgrade:  0:SOR_T01
WithP3BaseUpgrade: [HMW_081 HMW_171 HMW_205]
WithP3BaseCaptive: SOR_128:4
WithP3BaseCaptive: SOR_095:1
WithP3BaseCaptive: SOR_046:2
WithP3Resources: 6:SOR_074:0,6:SOR_123:1
WithP3Credits: 5
WithP3Force: true
WithP3Hand:    [TWI_031 TWI_034 TWI_037 TWI_040 TWI_043 TWI_046 TWI_049 TWI_052 TWI_055 TWI_058 TWI_061 TWI_064]
WithP3Discard: [TWI_067 TWI_070 TWI_073 TWI_076 TWI_079 TWI_082 TWI_085 TWI_088 TWI_091 TWI_094 TWI_097 TWI_100 TWI_103 TWI_106 TWI_109 TWI_112 TWI_115 TWI_118 TWI_121 TWI_124]
WithP3Deck:    [TWI_127 TWI_130 TWI_133 TWI_136 TWI_139 TWI_142 TWI_145 TWI_148 TWI_151 TWI_154 TWI_157 TWI_160 TWI_163 TWI_166 TWI_169 TWI_172 TWI_175 TWI_178 TWI_181 TWI_184 TWI_187 TWI_190 TWI_193 TWI_196 TWI_199 TWI_202 TWI_205 TWI_208 TWI_211 TWI_214]

#// ── Seat 4 ─────────────────────────────────────────────────────────────────────────────────
WithP4Leader:  TWI_009:1:1
WithP4Leader2: TWI_010:1:1
WithP4Base: SOR_026:27
WithP4GroundArena: [SOR_038:1:0 SOR_039:0:7 SOR_046:1:4 SOR_095:0:2 SOR_128:1:0 SOR_130:0:2]
WithP4SpaceArena:  [SOR_086:1:0 SOR_089:0:5 SOR_206:1:0 SOR_110:0:1 SOR_112:1:2 SOR_250:0:3 SOR_102:1:4]
WithP4GroundArenaUpgrade: 0:SOR_T02
WithP4GroundArenaUpgrade: 0:SOR_T02
WithP4GroundArenaUpgrade: 0:SOR_T01
WithP4GroundArenaUpgrade: 0:SOR_T01
WithP4GroundArenaUpgrade: 0:SOR_120
WithP4GroundArenaCaptive: 1:SOR_095:1
WithP4GroundArenaCaptive: 1:SOR_046:2
WithP4SpaceArenaPilot:    0:JTL_108
WithP4SpaceArenaUpgrade:  0:SOR_T02
WithP4SpaceArenaUpgrade:  0:SOR_T01
WithP4BaseUpgrade: [HMW_081 HMW_171 HMW_205]
WithP4BaseCaptive: SOR_128:1
WithP4BaseCaptive: SOR_095:2
WithP4BaseCaptive: SOR_046:3
WithP4Resources: 6:SOR_074:0,6:SOR_123:1
WithP4Credits: 5
WithP4Force: true
WithP4Hand:    [JTL_032 JTL_035 JTL_038 JTL_041 JTL_044 JTL_047 JTL_050 JTL_053 JTL_056 JTL_059 JTL_062 JTL_065]
WithP4Discard: [JTL_068 JTL_071 JTL_074 JTL_077 JTL_080 JTL_083 JTL_086 JTL_089 JTL_092 JTL_095 JTL_098 JTL_101 JTL_104 JTL_107 JTL_110 JTL_113 JTL_116 JTL_119 JTL_122 JTL_125]
WithP4Deck:    [JTL_128 JTL_131 JTL_134 JTL_137 JTL_140 JTL_143 JTL_146 JTL_149 JTL_152 JTL_155 JTL_158 JTL_161 JTL_164 JTL_167 JTL_170 JTL_173 JTL_176 JTL_179 JTL_182 JTL_185 JTL_188 JTL_191 JTL_194 JTL_197 JTL_200 JTL_203 JTL_206 JTL_209 JTL_212 JTL_215]

## WHEN

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:12
P2HANDCOUNT:12
P3HANDCOUNT:12
P4HANDCOUNT:12
P1DISCARDCOUNT:20
P2DISCARDCOUNT:20
P3DISCARDCOUNT:20
P4DISCARDCOUNT:20
P1DECKCOUNT:30
P2DECKCOUNT:30
P3DECKCOUNT:30
P4DECKCOUNT:30
P1RESCOUNT:12
P2RESCOUNT:12
P3RESCOUNT:12
P4RESCOUNT:12
P1CREDITCOUNT:5
P2CREDITCOUNT:5
P3CREDITCOUNT:5
P4CREDITCOUNT:5
P1HASFORCE
P2HASFORCE
P3HASFORCE
P4HASFORCE
P1GROUNDCOUNT:8
P2GROUNDCOUNT:8
P3GROUNDCOUNT:8
P4GROUNDCOUNT:8
P1SPACECOUNT:7
P2SPACECOUNT:7
P3SPACECOUNT:7
P4SPACECOUNT:7
P1LEADERCOUNT:2
P2LEADERCOUNT:2
P3LEADERCOUNT:2
P4LEADERCOUNT:2
P1LEADER0DEPLOYED:true
P1LEADER1DEPLOYED:true
P2LEADER0DEPLOYED:true
P2LEADER1DEPLOYED:true
P3LEADER0DEPLOYED:true
P3LEADER1DEPLOYED:true
P4LEADER0DEPLOYED:true
P4LEADER1DEPLOYED:true
P1BASEUPGRADECOUNT:3
P2BASEUPGRADECOUNT:3
P3BASEUPGRADECOUNT:3
P4BASEUPGRADECOUNT:3
P1BASECAPTIVECOUNT:3
P2BASECAPTIVECOUNT:3
P3BASECAPTIVECOUNT:3
P4BASECAPTIVECOUNT:3
P1BASEDMG:21
P2BASEDMG:15
P3BASEDMG:24
P4BASEDMG:27
#// Pin the subcard stacks to their hosts (UPGRADECOUNT counts captives/pilots too) and the two
#// deployed leader units to the last two ground slots. Pilots are attached AFTER plain upgrades, so
#// the pilot is subcard index 2 on space unit 0.
P1GROUNDARENAUNIT:0:UPGRADECOUNT:5
P1GROUNDARENAUNIT:0:SHIELDCOUNT:2
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:UPGRADECOUNT:3
P1SPACEARENAUNIT:0:UPGRADE:2:CARDID:JTL_108
P1GROUNDARENAUNIT:6:CARDID:IBH_053
P1GROUNDARENAUNIT:7:CARDID:SHD_011
P2GROUNDARENAUNIT:0:UPGRADECOUNT:5
P2GROUNDARENAUNIT:0:SHIELDCOUNT:2
P2GROUNDARENAUNIT:1:UPGRADECOUNT:2
P2SPACEARENAUNIT:0:UPGRADECOUNT:3
P2SPACEARENAUNIT:0:UPGRADE:2:CARDID:JTL_108
P2GROUNDARENAUNIT:6:CARDID:SHD_007
P2GROUNDARENAUNIT:7:CARDID:SHD_010
P3GROUNDARENAUNIT:0:UPGRADECOUNT:5
P3GROUNDARENAUNIT:0:SHIELDCOUNT:2
P3GROUNDARENAUNIT:1:UPGRADECOUNT:2
P3SPACEARENAUNIT:0:UPGRADECOUNT:3
P3SPACEARENAUNIT:0:UPGRADE:2:CARDID:JTL_108
P3GROUNDARENAUNIT:6:CARDID:SHD_014
P3GROUNDARENAUNIT:7:CARDID:SHD_015
P4GROUNDARENAUNIT:0:UPGRADECOUNT:5
P4GROUNDARENAUNIT:0:SHIELDCOUNT:2
P4GROUNDARENAUNIT:1:UPGRADECOUNT:2
P4SPACEARENAUNIT:0:UPGRADECOUNT:3
P4SPACEARENAUNIT:0:UPGRADE:2:CARDID:JTL_108
P4GROUNDARENAUNIT:6:CARDID:TWI_009
P4GROUNDARENAUNIT:7:CARDID:TWI_010
