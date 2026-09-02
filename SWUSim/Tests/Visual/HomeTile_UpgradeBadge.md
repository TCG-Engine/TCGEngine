# VISUAL CHECK — attached-upgrade count badge on home-tile units (count + click-through panel)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor. Desktop layout.
#
# WHAT THIS IS. Each unit with anything attached shows ONE white badge with a black number in its
# top-right corner: how many cards are attached. Clicking it opens the panel listing them all with art.
#
# ⚠ THIS REPLACED A PER-UPGRADE RAIL — a vertical strip of upgrade art chips down the card's right
#   edge. The rail worked and was fully tested, and it still looked bad: at ~17px a concat crop is not
#   recognisable, so the chips read as noise on the art. If you are tempted to put per-upgrade art back
#   on a thumbnail, the blocker is LEGIBILITY, not space.
# ⚠ Peek-from-below slivers (what the FULL board does) were never available: one sliver at the board's
#   proportion is ~17px, the gap below a mini card is ~3.7px between grid rows, and the tile has zero
#   free height. Measured, not assumed — the same budget that caused the "falling out of the container"
#   overflow.
#
# THE COUNT IS THE HONEST TOTAL — nothing filtered, nothing collapsed. Real upgrades, token upgrades
# (Experience / Shield / Weakness), captives, unit PILOTS and pilot LEADERS all count and all appear in
# the panel. That is deliberate: a pilot is attached as an upgrade and is exactly what an opponent
# needs to see. Note this differs from the FULL board, which collapses 4+ of one token into an "xN"
# sliver — here 4 Experience reads "4", not "1".
#
#   P2 ground 0  SOR_095  1 upgrade                       -> "1"           · dmg 2 of 3
#   P2 ground 1  SOR_157  3 upgrades                      -> "3"           · dmg 0 (undamaged control)
#   P2 ground 2  SOR_046  5 upgrades                      -> "5"           · dmg 6 of 7
#   P2 ground 3  SOR_065  4x Experience                   -> "4" NOT "1"   · dmg 4 of 5
#   P2 ground 4  SOR_067  4x Experience + 2 upgrades      -> "6", EXHAUSTED · dmg 3 of 5
#   P2 ground 5  SOR_232  AT-ST: 1 upgrade + a unit PILOT -> "2"           · dmg 4 of 7
#   P2 SPACE  0  ASH_083  2 upgrades (a CREATURE, no pilot)-> "2"          · dmg 12 (TWO DIGITS)
#   P2 SPACE  1  SOR_090  Devastator: a PILOT LEADER      -> "1"           · dmg 3 of 10
#   P3 ground 0  SOR_095  NOTHING attached                -> NO BADGE      · dmg 2 (control)
#
# ⚠ THE PILOTS ARE ON VEHICLES ON PURPOSE. A pilot attaches to a VEHICLE, so the unit pilot flies the
#   AT-ST and the pilot leader flies the Devastator — each on its own unit, so the panel for each shows
#   exactly one pilot and there is no ambiguity about which face belongs to which. An earlier draft of
#   this file put the pilot leader on Summa-verminoth, which is a CREATURE, and the unit pilot on Scout
#   Bike Pursuer, which is an Imperial TROOPER — the badge counted them and the panel listed them, so
#   nothing looked wrong, but the board was describing something the rules cannot produce. A visual
#   fixture may be unrealistic (nobody holds this exact board), but it must never be ILLEGAL, or it
#   stops being a reference for what the UI should show.
#
# What to look at:
#   • Read each badge against the table. The 4x-Experience unit reading "4" is the one that proves the
#     count is not silently grouping tokens the way the full board's slivers do.
#   • The unit with nothing attached has NO badge at all. Without it, a badge that always drew (or that
#     rendered "0") would look correct here.
#   • CLICK a badge -> the panel lists every attached card with art. Click anywhere to dismiss, like
#     the discard pile.
#   • ⚠ PILOTS SHOW THE RIGHT FACE, and this is the whole point of checking them:
#       - the PILOT LEADER on the Devastator shows its _back (UNIT) side — the deployed side that is
#         actually attached. Its LEADER front is a different card and showing it would be wrong.
#       - the unit PILOT on the AT-ST shows its full portrait, not the square concat crop.
#       - every ordinary upgrade still shows its concat art.
#     Both pilot faces are full cards (450x628 PORTRAIT) while every ordinary entry is the square
#     450x450 concat crop, so also confirm the two pilots are letterboxed in the panel's square slots
#     rather than squashed.
#   • ⚠ Badge vs the rest of the card. The badge owns the TOP-RIGHT because the damage token owns the
#     centre, the keyword badges own the bottom edge, and the title band owns the top. On the
#     5-upgrade/6-damage unit and the two-digit-damage space unit, confirm the badge, the damage token,
#     the title and the keyword badges are all readable and none overlaps another.
#   • EXHAUSTED + badge. P2 ground 4 is exhausted, so the card is rotate(8deg) scale(0.886) and the
#     badge rotates with it — it must stay inside the tilted card and clear of its neighbour.
#   • The badge is white with BLACK text — the inverse of every other marker on a mini card — so it
#     reads as a count, not as another status icon.
#
# ── LIVE POWER / HP (the bottom corners) ────────────────────────────────────────────────────────
# ⚠ THE THUMBNAIL'S ART LIES. A mini unit is the concat crop, and the crop's bottom corners show the
#   card's PRINTED stats. For an upgraded unit those are simply wrong, often by a lot — so live values
#   are drawn OVER them, in the same corners and with the same tokens the full board uses. Read each
#   pair against this table; every one of these units is modified, which is the point:
#
#     SOR_095  Battlefield Marine    printed 3/3    -> live 4/4
#     SOR_157  Cantina Braggart      printed 0/3    -> live 3/6
#     SOR_046  Consular Sec. Force   printed 3/7    -> live 8/12
#     SOR_065  Baze Malbus           printed 2/5    -> live 10/9
#     SOR_067  Rugged Survivors      printed 3/5    -> live 11/10
#     SOR_232  AT-ST                 printed 6/7    -> live 9/10
#     ASH_083  Summa-verminoth       printed 15/15  -> live 16/16
#     SOR_090  Devastator            printed 10/10  -> live 16/14   <- the report that prompted this
#     P3's SOR_095 (nothing attached) printed 3/3   -> live 3/3     <- the control: unchanged
#
#   • The overlay is ALWAYS drawn, including on P3's unmodified unit where it matches the print. An
#     overlay that appeared only when the numbers differ would make "no overlay" indistinguishable from
#     "not loaded", and would have the viewer reading two different unit shapes on one board.
#   • The counters are pushed OUT of the card's bottom corners — down and outward, deliberately proud
#     of the edge — to clear the centre line where the damage token and the badge strip sit. Check they
#     are not SLICED: the arena grid clips (overflow-x:auto forces overflow-y), and .swu-mb-row carries
#     padding-inline / padding-bottom purely so the spill has somewhere to live. A flat-cut token edge
#     means that padding is now smaller than the spill.
#   • The keyword badges hang from the bottom CENTRE, so on a unit with 3-4 badges their boxes overlap
#     the counters (~11-13% of a card at worst). That is bounding box, not ink: the badges are circles
#     and nest between the two tokens. Confirm by eye on the 4-badge unit that both numbers stay fully
#     readable. The full board avoids the adjacency with schema offsets (CurrentPower OffsetY=12 up,
#     badges OffsetY=20 down) that do NOT transfer to a square thumbnail — do not "fix" this by copying
#     those numbers across.

## GIVEN
CommonSetup3P: rrk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: SOR_095:1:2
WithP2GroundArenaUpgrade: 0:SOR_070
WithP2GroundArena: SOR_157:1:0
WithP2GroundArenaUpgrade: 1:SOR_070
WithP2GroundArenaUpgrade: 1:SHD_123
WithP2GroundArenaUpgrade: 1:LOF_053
WithP2GroundArena: SOR_046:1:6
WithP2GroundArenaUpgrade: 2:SOR_070
WithP2GroundArenaUpgrade: 2:SHD_123
WithP2GroundArenaUpgrade: 2:LOF_053
WithP2GroundArenaUpgrade: 2:ASH_114
WithP2GroundArenaUpgrade: 2:SOR_T02
WithP2GroundArena: SOR_065:1:4
WithP2GroundArenaUpgrade: 3:SOR_T01
WithP2GroundArenaUpgrade: 3:SOR_T01
WithP2GroundArenaUpgrade: 3:SOR_T01
WithP2GroundArenaUpgrade: 3:SOR_T01
WithP2GroundArena: SOR_067:0:3
WithP2GroundArenaUpgrade: 4:SOR_T01
WithP2GroundArenaUpgrade: 4:SOR_T01
WithP2GroundArenaUpgrade: 4:SOR_T01
WithP2GroundArenaUpgrade: 4:SOR_T01
WithP2GroundArenaUpgrade: 4:SOR_070
WithP2GroundArenaUpgrade: 4:SHD_123
WithP2GroundArena: SOR_232:1:4
WithP2GroundArenaUpgrade: 5:SOR_070
WithP2GroundArenaPilot: 5:JTL_046
WithP2SpaceArena: ASH_083:1:12
WithP2SpaceArenaUpgrade: 0:SOR_070
WithP2SpaceArenaUpgrade: 0:SHD_123
WithP2SpaceArena: SOR_090:1:3
WithP2SpaceArenaPilot: 1:JTL_001
WithP3GroundArena: SOR_095:1:2

## WHEN

## EXPECT
SEATCOUNT:3
P2GROUNDARENACOUNT:6
P2SPACEARENACOUNT:2
P3GROUNDARENACOUNT:1
