# VISUAL CHECK — Twin Suns Home Panels carry a STATIC printed-HP pip on every opponent's base
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
#
# SETUP — load this file in the Test Schema Editor (zzTestSchemaEditor.php), or:
#   GN=$(curl -s -X POST http://localhost:3400/TCGEngine/SWUSim/TestSchemaSetup.php \
#          --data-urlencode "schema@SWUSim/Tests/Visual/HomePanel_BaseHPPip.md" \
#        | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   open "http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=1&authKey=testschema"
#   PHONE: append &swuLayout=mobile at 430x932 — the Home Panels render as SEAT ROWS there, drawn by a
#   second function (swuRenderSeatRow) with its own pip size in GameLayoutMobile.php. Check both.
#
# FEATURE REQUEST (2026-10-08)
#   "add an HP badge to the Twin Suns home panels so that it is easier to see the total HP of a base
#   card from that view at the start of a game. this counter should not count down. it should be a
#   static printed HP of the base." Owner chose the TOP-LEFT corner from a design canvas with real
#   before/after screenshots (top-left vs bottom-left).
#
# WHAT CHANGED
#   The tile already draws the whole base card, printed HP included, but at 68x49 (desktop) / 46x34
#   (phone) that number is ~6px tall. A new Base-zone virtual, PrintedHP = BasePrintedHP() = CardHp(),
#   ships the printed value on the wire for every seat, and swuMbBaseHP() draws it as a pip in the
#   thumbnail's top-left — directly over the card's own printed HP, so it reads as that number enlarged.
#
# ── WHAT TO LOOK AT ─────────────────────────────────────────────────────────────────────────────
#   • P2 (Security Complex) shows 25, P3 (Colossus) 35, P4 (Starlight Temple) 28. Three DIFFERENT
#     values on purpose: a pip reading the wrong seat's base shows the wrong number.
#   • P4's pip reads 28, NOT 16. P4 carries 12 damage; the pip is the PRINTED HP and must not count
#     down. The red "12" token stays centred, as before.
#   • THE PIP IS THE USUAL BLUE HP BADGE (swusim-hp_v2.png — the same art as a unit's HP token), owner
#     2026-10-08 ("use our usual hp badge instead of the shield"), sitting inside the top-left corner
#     over the card's own printed HP. Deliberately SQUARER than the 3:4 art (owner: "reduce the badge
#     height to look more square") — slightly squat is intended; tall and narrow is the old version.
#   • IT OVERHANGS THE TOP EDGE by about a third of its height ("it can fall off the card some") — the
#     base health bar runs along that edge. It must stay inside its own panel: not clipped, not touching
#     the row above on the phone.
#   • Where the pip meets the damage token, the DAMAGE number is on top (the pip is emitted first).
#   • P3 is ELIMINATED (LiveSeats=124): its tile stays, greyed, units gone — and its pip STAYS, greyed
#     with the rest of the tile.
#   • YOUR OWN BASE (P1, full size, bottom half) has NO pip — it is large enough to read already.
#   • THE ZOOM IN board is unchanged: no pip on the zoomed opponent's full-size base.
#   • Row 1 of every tile is the SAME WIDTH as before — the pip is an overlay, it adds no width.
#   • Hovering the base still pops the card preview (the pip is pointer-events:none).
#
# ⚠ HOW THIS REGRESSES. Subtracting Damage in BasePrintedHP() turns it into a countdown — the one
#   thing the request ruled out. And the two renderers drift: the desktop tile and the phone row each
#   call swuMbBaseHP(); dropping one call leaves the other surface looking fine.
#
# ── AUTOMATED COVERAGE (this file is for what it cannot see) ────────────────────────────────────
#   DevTools/ui-harness/swusim-home-panel-base-hp-pip-xbrowser.mjs — chromium + firefox + webkit,
#   desktop AND phone, 168 checks on this same board: one pip per opponent base, its text equals
#   CardHp() of that base (derived from the container, not literals), tooltip names it, the CSS
#   applied (absolute, click-through), top-left and inside the thumbnail, legible but not a lid, and
#   no pip outside the Home Panels. Mutation-verified 2026-10-08: countdown, no desktop pip, no phone
#   pip and no CSS each go red.
#
# ⚠ WHAT NEITHER CAN JUDGE: whether the pip is too loud next to the leaders, or whether the outline
#   reads as "label" on a light-coloured base card. Look at it on a busy 4-seat board.

## GIVEN
#// P2's base comes from CommonSetup's theirBase — there is no WithP2Base directive.
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; theirBase:SOR_019}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 124
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP3Base: JTL_021:0
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Base: LOF_024:12
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010
WithP2GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0

## WHEN

## EXPECT
TURNPLAYER:1
SEATCOUNT:4
