# VISUAL CHECK — Twin Suns Home Panels show the WHOLE base card, not a crop
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
#
# SETUP — load this file in the Test Schema Editor (zzTestSchemaEditor.php), or:
#   GN=$(curl -s -X POST http://localhost:3400/TCGEngine/SWUSim/TestSchemaSetup.php \
#          --data-urlencode "schema@SWUSim/Tests/Visual/HomePanel_WholeBaseCard.md" \
#        | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   open "http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=1&authKey=testschema"
#   PHONE: append &swuLayout=mobile at 430x932 — the Home Panels render as SEAT ROWS there, and they
#   are a second stylesheet with their own copy of the sizing. Check both.
#
# OWNER FEATURE REQUEST (2026-09-26)
#   "for Twin Suns Home Panels, use the whole base image instead of a crop."
#
# WHAT CHANGED
#   The base thumbnail was painted from Images/concat/ — the 450x450 SQUARE art crop — into a
#   LANDSCAPE box with background-size:cover. Measured at 68x48, cover discarded 29% of the image's
#   height, so the card's TITLE, its HP SHIELD and its ASPECT ICON were all outside the thumbnail:
#   you saw art and nothing else. A SWU base card is natively landscape (WebpImages is 628x450), so
#   the fix is the right folder plus a box at the card's own ratio.
#
# ── WHAT TO LOOK AT ─────────────────────────────────────────────────────────────────────────────
#   • EACH OPPONENT PANEL's third thumbnail is a complete base card: name plate across the top
#     ("Capital City"), the blue HP shield top-left, the aspect icon top-right, the subtitle strip
#     along the bottom ("LOTHAL"). Nothing is clipped on any edge.
#   • IT MATCHES THE LEADERS BESIDE IT. Leaders were already whole cards; the base was the odd one
#     out. All three thumbnails should now read as the same kind of object.
#   • NO LETTERBOXING. The box is the card's own 628:450, so cover fills it exactly — if you see bars
#     or a stretched card, the ratio has drifted (someone wrote an explicit height again).
#   • THE DAMAGE COUNTER still sits centred on the base and stays readable over the busier image.
#   • UNITS IN THE ARENAS ARE UNCHANGED and still SQUARE. That is deliberate, not an oversight: a
#     unit's whole card is PORTRAIT and two thirds rules text at this size, so units keep the concat
#     crop. If units suddenly look like tall cards, someone swapped every concat/ in the file.
#
# ⚠ HOW THIS REGRESSES. The height DERIVES from the width (`calc(var(--swu-mb-base-w) * 0.717)`) in
#   GameLayoutShared.php. Writing an explicit `height:` in any override — which is exactly what
#   `body.swu-home .swu-mb-base { width: 66px; height: 46px; }` used to do — silently re-introduces
#   the crop while every image URL still looks correct. Set the WIDTH only.
#
# ── AUTOMATED COVERAGE (this file is for what it cannot see) ────────────────────────────────────
#   DevTools/ui-harness/swusim-home-panel-base-art-xbrowser.mjs — chromium + firefox + webkit,
#   desktop AND phone, 60 checks: the folder is WebpImages, the box matches 628:450 so cover crops
#   nothing, and two CONTROLS — units still on concat/, leaders still on WebpImages.
#   ⚠ It measures the PADDING box fractionally (rect minus border widths). clientHeight is an integer
#     and rounds 31.55 to 32, which reads as a 1% crop that is not there.
#   ⚠ Its board deliberately puts a UNIT on every opponent seat. The first cut reused the chat-matrix
#     board, whose arenas are empty, so the units control matched nothing and a blanket
#     concat->WebpImages swap passed it.
#
# ⚠ WHAT NEITHER CAN JUDGE: whether the whole card is actually BETTER at this size. The title is
#   small, and the card now competes with the leaders for attention where before it was quiet art.
#   That is the owner's call and it has been made — but if the panel reads as busy on a 4-seat board,
#   raise it in the gameboard design review rather than shrinking the base back into a crop.

## GIVEN
#// Four seats, each opponent holding a base, two leaders and one ground unit — the unit matters:
#// it is what makes the "units stay square" contrast visible beside the now-whole base.
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP3Base: SOR_026:5
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Base: SOR_026:8
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0

## WHEN

## EXPECT
TURNPLAYER:1
SEATCOUNT:4
