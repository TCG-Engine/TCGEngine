# VISUAL CHECK — the fixed decision BANNERS stay on screen at phone width
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it in the Test
# Schema Editor, or run the automated probe:
#   cd DevTools/ui-harness && node swusim-decision-banner-mobile-xbrowser.mjs
#
# REPORTED 2026-09-25 (game 1310334, a 3-seat Twin Suns board): ASH_220 Remnant Lookouts asked "Look at
# which opponent's hand?" and the picker's buttons ran off the right edge of the phone screen, so the
# player could not answer at all. The board was otherwise fine — the prompt itself was unreachable.
#
# ROOT CAUSE (Core/OptionChooseUI.js, and the same shape in Core/NumberChooseUI.js)
#   .optchoose-banner is `position: fixed` and centered, and GameLayoutShared.php's HUD sweep re-centers
#   it on BOTH axes (top:50%/left:50% + translate(-50%,-50%)). It is a `display:flex` row whose two
#   children are BOTH `flex-shrink: 0`, with no `flex-wrap`. So once the label plus the button row exceed
#   the banner's `max-width: 80vw`, nothing shrinks and nothing wraps — the row simply overflows, and
#   because the banner is CENTER-anchored the overflow spills off BOTH edges symmetrically. The right-hand
#   buttons end up past the viewport with no way to scroll to them.
#
#   It needs three seats to bite in the wild: at two players SWUQueueChooseOpponent auto-resolves the lone
#   opponent to an invisible PASSPARAMETER, so Premier never renders this banner at all. Twin Suns offers
#   one button per live opponent, and the labels are USERNAMES (humanised in optionDisplayLabel), which are
#   far wider than the "Ground"/"Space" pair the banner was originally sized for.
#
# WHAT TO LOOK AT (and what the probe asserts)
#   • At 390x844 the banner's left edge is >= 0 and its right edge is <= viewport width.
#   • EVERY option button is fully inside the viewport, and hit-testing its centre returns that button
#     (not some element on top of it) — a button that is on-screen but unclickable is the same bug.
#   • The banner wraps to stacked rows rather than growing wider than the screen.
#   • Desktop (1600x900) is unchanged: still one row, still centered.
#   • NumberChooseUI's banner (.numchoose-banner) is the same component shape and is checked alongside it.
#     It had NO max-width at all, so it overflowed even more readily than the reported one.
#
# ⚠ Long names are the point. The probe uses realistic usernames, not "P2"/"P3" — the seat tokens are the
#   TRANSPORT form and are never what a player sees (see ChooseOpponent_PickerShowsPlayerNames.md, which
#   pins that humanising and the rule that the button still SUBMITS the raw token).
#
# AUTOMATED PROBE (what was actually run, 2026-09-25 — 79 assertions)
#   node DevTools/ui-harness/swusim-decision-banner-mobile-xbrowser.mjs
#   Three banner shapes x {mobile 390x844, desktop 1600x900} x {Chromium, Firefox, WebKit}:
#     optchoose        — the reported "Look at which opponent's hand?" picker, 3 username buttons
#     optchoose-cards  — the SAME banner's other shape ("@SOR_046&Play&Discard&Leave"), the branch the
#                        fix did not target but DID change; asserts the card art survives at a usable size
#     numchoose        — .numchoose-banner, the sibling with the identical defect
#
#   BEFORE the fix (Chromium, 390px):  banner 39..351 (it DID honour max-width) but the buttons rendered
#     at 342..511, 521..618 and 628..731 — all three past the 390px viewport, none hit-testable. That is
#     the screenshot in the report: the label centered, "CLA…" clipped at the right edge.
#   AFTER:  ALL PASS in all three engines, both layouts. Mobile banner 98..293, buttons wrap to two rows,
#     every button hit-tested at its own centre. Desktop unchanged (optchoose 437..1163 of 1600).
#   MUTATION: reverting ONLY Core/OptionChooseUI.js turns the four optchoose rows red with exactly the
#     pre-fix numbers while every numchoose row stays green — the two banners are pinned independently.
#
#   ⚠ The card-art assertion needs the image to have DECODED. An <img> that has not loaded has no
#     intrinsic width, and `flex: 0 0 auto` then collapses it to its borders — measuring too early
#     reported "2x106" and a layout bug that did not exist. The probe waits for load (bounded) first.
#
# NOT COVERED HERE
#   Which seats are offered, and that the answer routes to the right seat — that is the schema suite
#   (e.g. shd/CadBane_HeWhoNeedsNoIntroduction.md::Front_TwinSuns_YouChooseWHICHOpponent). The banners that
#   already handle narrow widths (MZMultiChoose, TwoSidedSlider, NameCard all carry their own media
#   queries) are untouched by this fix; the HUD button SKIN is HUDButtons_MZDecisions.md.

## GIVEN
#// A 4-seat board so the opponent picker renders the maximum number of buttons (one per live opponent).
#// The probe drives the banners directly, the same way ChooseOpponent_PickerShowsPlayerNames.md does.
CommonSetup: rrk/bbw/{myLeader:SHD_014}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN

## EXPECT
SEATCOUNT:4
