# VISUAL CHECK — Twin Suns: the active player is highlighted in the home preview strips
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
#   curl -s -X POST .../SWUSim/TestSchemaSetup.php --data-urlencode "schema@<this file>"
#   open  http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=N&playerID=2
#   ⚠ folderPath=SWUSim is REQUIRED, and the seat matters — see the two cases below.
#
# WHAT TO LOOK AT (seat order 1234; open as playerID=2, so P1/P3/P4 get preview strips)
#   • Every tile shows TWO leader cards followed by that seat's base — if a tile shows one leader, the
#     fixture is wrong for the format, not the renderer.
#   • The strip for the seat whose turn it is wears a soft warm AMBER GLOW
#     ⚠ A GLOW, not an outline: two blurred box-shadow layers, matching 2P's .has-action idiom
#     (0 0 14px 3px + 0 0 4px 1px of the accent). It was originally `0 0 0 2px` — a zero-blur spread,
#     which paints a crisp solid line and reads as a different visual language from the rest of the
#     board. Regression check: the computed box-shadow must contain NO `0px 0px 0px` layer. + a brightness lift, and its
#     seat number carries a "TURN" pill. Every other strip is untouched.
#   • Drive the turn on and the highlight MOVES, without the strips being re-rendered:
#       curl -X POST .../TestSchemaStep.php -d gameName=N --data-urlencode "step=- P2>Pass"
#     ⚠ This is the case worth actually running. swuTwHighlightActiveSeat() is called from BOTH the
#     strip rebuild AND the TurnPlayerData setter, because the turn can pass without anything on an
#     opponent's mini-board changing — a rebuild-only hook looks correct on load and then goes stale.
#   • NEGATIVE — open as the seat whose turn it IS (playerID=4 while TurnPlayer is 4): NOTHING is
#     highlighted. Only opponents have strips, and your own board is the whole bottom half of the
#     screen. An implementation that highlighted "the first strip" or fell back to seat 1 passes the
#     positive case and fails this one.
#
# TILE INTERACTION — the preview should behave almost like the full board
#   • The TILE ITSELF has no hover state. It is not a button; a whole-tile glow competed with the
#     active-turn ring, which is the one border state here that carries meaning.
#   • HOVER any card in a tile — leader, base, or an arena unit — and it blows up in the normal card
#     preview, exactly as on the full board. ⚠ Mini-board cards are CSS background-image spans, not
#     <img>, so ShowCardDetail (which reads an element's src) cannot drive them. They go through
#     ShowMiniCardDetail -> ShowCardDetailByCardID (Core/jsInclude.js), which resolves the art path
#     from a data-card-id. The id stored is the RESOLVED one (resolveCardImageID), so preview/mock
#     cards blow up correctly too.
#   • ⚠ CHECK THE PREVIEW'S SHAPE, not just that something appeared. That path must call ShowDetail,
#     NOT ShowSubcardDetail: ShowSubcardDetail hardcodes the SWU PORTRAIT ratio (400 * 0.71), because
#     a subcard sliver has no natural dimensions to measure, and then sets BOTH width and height on
#     the <img>. Every LEADER and every BASE is a 628x450 LANDSCAPE card, so they came out visibly
#     squashed into a portrait frame while units looked fine. ShowDetail preloads the image and sizes
#     from img.width/img.height, so each card keeps its own shape. Measured: 0.0% distortion for
#     leader, base AND unit in both engines.
#   • The dwell before the preview opens must MATCH the full board (SWUSim = 850ms). Both read the
#     same CardDetailHoverDelay() now; measured mini-board 877ms vs full board 873ms in Chromium,
#     824 vs 866 in Firefox. A preview path with its own timing reads as a different feature.
#   • CLICK the DISCARD chip and that seat's discard pile opens, the same popup the full board opens
#     from its discard counter. Verify it STAYS open — the board polls and re-renders continuously,
#     and a popup that a later tick wipes would look like a flicker.
#     ⚠ Only Discard is wired. It is a PUBLIC zone, so opening any seat's is harmless. Do NOT extend
#     this to Deck / Hand / Resources: see the note at the bottom of this file.
#
# ⚠ NOT colour-only: the "TURN" pill carries the same information as the ring, so the state survives a
# colourblind viewer and a screenshot. Do not "simplify" it away.
#
# THE REMOVED TOP BAR
# A fixed top-centre ORDER STRIP (#swuOrderStrip — chips reading "P1 | P2 (you) | P3 | P4", green ring
# on the turn player) used to answer the same question in a different visual language. It is gone:
# markup (desktop + mobile), swuRenderOrderStrip(), its pollGlobals() call, and its CSS.
# ⚠ It also rendered a THIRD state the strips do not: `myActionsData.roundState` == 'took-counter'
# (_SWUSeatTookCounterThisRound), amber, meaning that seat has taken the counter this round. The server
# still computes and sends roundState, so re-surfacing it is a client-only change.
# ⚠ And the strips only exist in the HOME view — inside a matchup view (you vs one seat) there is now
# no whose-turn indicator beyond the "Waiting for the other player" miasma, which does not name a seat.
#
# MINI-BOARD ART + ARENA HEIGHT (same fixture)
#   • Each preview's LEADERS show the WHOLE leader card — name bar, art, rules text, stats.
#     ⚠ They must come from WebpImages/, NOT concat/. A leader (and a base) is a 628x450 LANDSCAPE
#     card; concat/ holds a 450x450 SQUARE crop of it. The old code drew the square crop into a
#     PORTRAIT 40x56 box, so ~28% of the card's width was gone before a second crop to fit — what you
#     saw was a vertical slice through the middle. The box is now landscape (61x44, ~628:450).
#   • ⚠ The BASE thumbnail still uses concat/ and is still a square crop in a landscape box. That is
#     deliberate scope, not an oversight — it was not part of the request. It now reads differently
#     from the full-art leader beside it, so it is the obvious next thing to change if wanted.
#   • The SPACE and GROUND containers are ALWAYS the same height as each other and fill the tile,
#     whether they hold units or nothing. Check an EMPTY arena next to a populated one.
#     ⚠ Two traps, both found by measuring rather than looking:
#       – `flex: 1 1 0` with `min-height: 0` lets a flex item shrink past its content. On the roomy
#         desktop tile it looked perfect; on the short mobile tile BOTH arenas collapsed to 10px —
#         padding and border, no cards at all. Leave min-height at its `auto` default.
#       – the row's min-height must be the thumbnail's height PLUS its 1px borders (50, not 48).
#         .swu-mb-card is content-box, so 48 left an empty arena 2px shorter than a populated one —
#         again invisible on desktop, where surplus height lets flex-grow even them out regardless.
#   • ⚠ On MOBILE the preview block is ~40px taller than before (187 -> 227): its height is content
#     driven there, and an empty arena no longer collapsing is exactly what adds the height. The
#     desktop block is fixed-height (top+bottom pinned) and is unchanged. If the mobile overlay is too
#     tall, shrink the mobile thumbnail rather than reintroducing the collapse.
#
# STAT ROW (between [leaders/base/zoom] and [Space])
#   • Each preview shows RES ready/total (+credits in gold when the seat holds any), DECK count and
#     DISCARD count for THAT seat.
#   • All of it is computable for an OPPONENT, which is the non-obvious part:
#       – resources arrive masked as `CardBack 0 {"Status":N}` — identity hidden, ready/exhausted
#         intact, which is all ready/total needs;
#       – Credit tokens arrive UNMASKED (real LAW_T01 id) because credits are public information, so
#         they can be counted separately AND excluded from the resource total;
#       – a private Deck renders as one "CardBack <count>" entry, so the count is field 1;
#       – the Discard is public, so its entries can simply be counted.
#     It reuses parseResCountFromData — the SAME function behind the main board's resource badge — so
#     the preview cannot drift from the board you get when you zoom in.
#   • ⚠ An empty deck emits NOTHING now (it renders as the empty pile frame), so the whole zone piece
#     is '' and a naive split would yield NaN. Guarded; assert a 0 deck reads "DECK 0", not blank.
#   • ⚠ THE test worth running: seat offsets. Give three seats DIFFERENT values and check each tile
#     shows its own — a block-offset bug reads plausible numbers off the wrong seat and every tile
#     still looks fine. The fixture below does exactly that (P1 4/6·3·2, P2 3/3·2·0, P3 5/6·1·1),
#     including one seat with credits so the total excludes them (6 real + 3 credits reads "4/6 +3").
#
# VERIFIED 2026-08-19: Chromium + Firefox at 1700x1100, plus a 430x930 mobile context. Highlight tracks
# a live turn change through the engine (turn 1 -> 3 -> 4, highlight followed each time), the negative
# holds from the active seat's own view, zero page errors, and #swuOrderStrip is absent in all three.
# Initiative verified 2026-08-19 in BOTH engines, P1 holding a CLAIMED initiative: as viewer 2, P1's
# tile shows Turn + a FILLED Initiative pill and the their-band token is display:none; as viewer 1
# (the holder) no tile carries the pill and the token is visible in #swuMyControlBand at y=982.
# The unclaimed variant renders the same pill outlined.
# Interaction verified 2026-08-19 in BOTH engines: tile border unchanged on hover; hovering a leader
# blows up SHD_007 at natural 628x450; clicking P1's Discard chip opens "P1 Discard" with its 2 cards
# and it survives 12s of poll cycles. P4's RES reads "3/4 +5".
# Stat row verified against the distinct-per-seat fixture in BOTH engines (P1 Res4/6 Deck3 Discard2,
# P2 Res3/3 Deck2 Discard0, P3 Res5/6 Deck1 Discard1) and on mobile; credits case checked separately
# (6 resources + 3 LAW_T01 reads "4/6 +3"). Zero page errors. Mobile block 227 -> 252 for the new row.
# Mini-board re-verified after the art/height change: leader 63x46 from WebpImages, arenas 167/167 on
# desktop and 75/75 on mobile, zero image 404s and zero page errors in both engines.
# WebKit NOT covered: it does not launch on this machine.
#
# WHO HAS THE INITIATIVE
#   • The seat holding it wears a CYAN "Initiative" pill on ROW 2 of its tile, beside the Discard chip — outlined while unclaimed,
#     FILLED once claimed for the round. A seat can hold the turn and the initiative at once, so this
#     is deliberately a second pill in a different colour rather than a variant of the amber Turn pill.
#   • ⚠ Why it exists: the bottom-left initiative token encodes only MINE vs THEIRS. With three
#     opponents "theirs" names nobody. The seat is on the wire the whole time —
#     InitiativeCounterData is "P<seat>_CLAIMED" / "P<seat>_UNCLAIMED" — it was simply never surfaced
#     per seat.
#   • ⚠ And the token was INVISIBLE here anyway: when an opponent holds it, it reparents into
#     #swuTheirControlBand at y=0, which the preview tiles cover now that they start at the top of the
#     board. body.swu-home hides it in that band for exactly that reason. Do not "fix" that by moving
#     the token back — it would still be ambiguous.
#   • When YOU hold the initiative there is no tile for you: the token stays in #swuMyControlBand on
#     your half, visible and unambiguous. Check BOTH — open as a seat that does not hold it (tile pill,
#     token hidden) and as the seat that does (no tile pill, token visible at the bottom).
#
# TILES MUST BE EVEN — the tiles are a COMPARISON view, so anything that shifts per seat breaks them
#   • ROW 1 is identical across tiles: seat label, two leaders, base, Zoom-in. The Turn/Initiative
#     pills live on ROW 2 (after Discard) for exactly this reason — on row 1 they pushed the pilled
#     seat's leaders and base to the right, so that tile no longer lined up with the others.
#     ⚠ Assert it numerically: the first .swu-mb-leader's offset from its tile must be EQUAL on every
#     tile (29px at 1700x1100). Eyeballing a ~30px shift across three wide tiles is unreliable.
#   • The RES chip is STATIC. Its value sits in .swu-mb-statval with min-width 4.9em, reserved for the
#     widest case "NN/NN +NN" whether or not the seat holds credits — a chip that grew when credits
#     appeared moved DECK and DISCARD with it, putting the same fact in a different place per tile.
#     ⚠ Test it with a 2-DIGIT seat. Single-digit fixtures fit any width and prove nothing. The
#     included fixture gives P4 12 resources + 12 credits ("10/12 +12"): measured natural width 60px
#     inside the 64px reserve, and all three RES chips 102px wide in both engines.
#     ⚠ If you widen .swu-mb-statcred's gap, re-measure that headroom — the reserve is not generous.
#
# HOME LAYOUT vs ZOOMED-IN LAYOUT — the two must look like different things
#   • HOME: the HUD corner frame (.swu-arena-bg, the cyan L-brackets) wraps ONLY YOUR HALF. Its top
#     corners sit on the midline and the three preview tiles sit ABOVE them. Before this, the frame
#     spanned both halves as it does in 2P, so the brackets appeared to frame three opponents' boards
#     as if they were one arena.
#   • HOME: the tiles start at the TOP of the board (top: 8px). They used to start at --swu-hand-h,
#     reserving the opponent's hand row — but this view hides #theirHandSlot and the old seat-chip bar
#     is gone, so that band was dead space. Measured 1700x1100: tiles 8..546 (was 118..546), frame
#     554..958.
#   • ZOOM IN (any matchup) and it must be PIXEL-IDENTICAL to a 2-player board: frame spanning both
#     halves, 142..958. This works because both rules are scoped to body.swu-home and the class is
#     absent on a matchup — check by toggling views, not by reading the CSS.
#   • ⚠ MOBILE IS NOT AFFECTED by either: GameLayoutMobile does not load the desktop body.swu-home
#     rules (--swu-hand-h is not even defined there) and has no .swu-arena-bg element at all. Verify
#     mobile separately; do not assume a desktop home-view fix reached it.
#
# ARROWS — cycling opponents while zoomed in
#   • HOME: no ◀ ▶ and no Go-back. Every opponent is already on screen; there is nothing to page to.
#   • MATCHUP: ◀ ▶ appear at the board edges alongside Go-back, and CYCLE between the other opponents
#     WITHOUT returning to home. Measured with 4 seats as P1: next → 3, 4, 2, 3 …; prev → 2, 4 …; the
#     view mode stays 'matchup' throughout.
#   • ⚠ The arrows step through swuStepView(), which wraps within swuMatchupIndices(). A plain
#     index±1 (what the flat 4-player carousel used, and what these buttons were originally wired to)
#     falls into the HOME view at index 0 — a wrap that silently changes what KIND of view you are on.
#     That is the case to test: page forward past the LAST opponent and confirm you land on the first.
#
# ⚠⚠ DO NOT wire Deck / Hand / Resources popups from these tiles. GetPopupContent.php decides what to
# mask from a hardcoded name list ($hiddenZonePrefixes = Hand, Memory, Material, TempZone,
# DecisionQueue, Versions) after stripping only a `my`/`their` prefix — it does NOT read the zone's
# declared Visibility. So `Deck` (Private) and `Resources` (Self) are not masked at all, and every
# `p{n}` form bypasses the strip. Measured 2026-08-19 against a live game: popupType=theirDeck returns
# the opponent's deck IN DRAW ORDER, myDeck returns your own, theirResources returns face-down
# resources, and p{n}Hand returns any seat's hand in the clear. Only theirHand is masked, and only by
# accident of being on that list. The client's ShowZonePopup refuses to open those zones, which is why
# nobody has noticed — it is client-side-only enforcement. Reported, not fixed.
#
# The GIVEN state is the whole check — the WHEN steps are for driving the turn by hand.

## GIVEN
#// ⚠ EVERY Twin Suns visual test seeds TWO leaders and a base per seat — a Twin Suns deck runs two
#// leaders, so a one-leader board is not the format and any per-leader render bug hides on it.
#// Deployed or undeployed does not matter; the count does. Leaders in a pair must share a force side
#// (all Villainy here). Same casts as TwinSuns_3P/4P_FullArenas so the corpus stays consistent:
#// P1 = Darth Vader + Kylo Ren, P2 = Moff Gideon + Bossk, P3 = Cad Bane + Aphra, P4 = Maul + Pre Vizsla.
#// Distinct per-seat resource/deck/discard values on purpose — see the stat-row note above. P1 also
#// has 3 Credit tokens, so its RES must read "4/6 +3": credits counted separately, NOT in the total.
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
#// P1 holds the initiative and has CLAIMED it, so the tile pill renders in its filled state and the
#// "who has initiative" read is checkable from a seat that is NOT P1 (open as playerID 2).
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP3Base:    SOR_026:5
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010
WithP4Base:    SOR_026:8
#// P4 carries 5 Credit tokens on top of 4 real resources, so its RES must read "3/4 +5" — a second,
#// differently-shaped credits case alongside P1's "4/6 +3".
#// ⚠ P4 is the WIDEST-VALUE seat on purpose: 12 real resources + 12 credits reads "10/12 +12", the
#// case the static RES chip is sized for. Do not simplify it to single digits.
WithP4Resources: 10:SOR_095:1,2:SOR_095:0,12:LAW_T01:1
WithP1Resources: 4:SOR_095:1,2:SOR_095:0,3:LAW_T01:1
WithP1Deck: [SOR_231 SOR_046 SOR_128]
WithP1Discard: [SOR_095 SOR_046]
WithP1GroundArena: SOR_229:1:0
WithP2Resources: 3:SOR_095:1
WithP2Deck: [SOR_231 SOR_046]
WithP2GroundArena: SOR_229:1:0
WithP3Resources: 5:SOR_095:1,1:SOR_095:0
WithP3Deck: [SOR_231]
WithP3Discard: [SOR_095]
WithP3GroundArena: SOR_229:1:0
WithP4GroundArena: SOR_229:1:0
#// Hands on EVERY seat — 5 to 8 cards, a different count per seat. Without them the hand band renders
#// empty, so nothing exercises the space it actually occupies: on MOBILE the band is what the seat
#// rows and your own board have to fit around, and an empty-hand fixture silently over-reports how
#// much room there is. Distinct counts also make an opponent's masked hand-count badge checkable.
#// P1 8 · P2 5 · P3 7 · P4 6.
WithP1Hand: SOR_095
WithP1Hand: SOR_046
WithP1Hand: SOR_231
WithP1Hand: SOR_128
WithP1Hand: SOR_229
WithP1Hand: SEC_195
WithP1Hand: SOR_032
WithP1Hand: SOR_033
WithP2Hand: SOR_095
WithP2Hand: SOR_046
WithP2Hand: SOR_231
WithP2Hand: SOR_128
WithP2Hand: SOR_229
WithP3Hand: SOR_095
WithP3Hand: SOR_046
WithP3Hand: SOR_231
WithP3Hand: SOR_128
WithP3Hand: SOR_229
WithP3Hand: SEC_195
WithP3Hand: SOR_032
WithP4Hand: SOR_095
WithP4Hand: SOR_046
WithP4Hand: SOR_231
WithP4Hand: SOR_128
WithP4Hand: SOR_229
WithP4Hand: SEC_195

## WHEN

## EXPECT
SEATCOUNT:4
SEATLIVE:4:true
