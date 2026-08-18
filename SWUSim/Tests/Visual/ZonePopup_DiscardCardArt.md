# VISUAL CHECK — the discard zone popup shows real card art (bug #971)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, then CLICK the opponent's discard pile.
#
# WHY THIS EXISTS
# Clicking a pile opens the zone popup built by createPopupHTML() in Core/jsInclude.js — the modal
# titled "Their Discard" (the title is the zone name split on capitals). It used to hand-build its art
# folder as "./" + <#folderPath> + "/concat", which Card()'s AssetReflectionPath rewrite turns into
# ./SWUSim/concat/<id>.webp — the per-app tree the shared-corpus migration deleted. SWU art is one
# shared corpus at AppCore/SWU/Images/{concat,WebpImages}; the fix reads window.assetImageFolder.
# ⚠ THE SYMPTOM DEPENDS ON THE ENVIRONMENT, which is why the report looked preview-specific:
#   • no legacy SWUSim/concat tree (a clean checkout) -> EVERY card in the popup is broken;
#   • legacy tree still present (prod, and the reporter's box) -> only PREVIEW cards are broken,
#     because their art is mock_-prefixed and was only ever synced into the shared corpus.
# The board below reproduces the reported discard of game 3342 exactly: one preview card and one
# released card, so BOTH failure shapes are visible in one screenshot.
#
# WHAT TO LOOK AT
#   • Click P2's discard pile. The modal header reads "Their Discard".
#   • It lists TWO cards and BOTH must render as art, not a broken-image icon with the alt text "Card":
#       HMW_162  — a PREVIEW card, art file mock_HMW_162.webp   (the one that was broken)
#       LOF_107  — a RELEASED card, art file LOF_107.webp       (the control)
#   • Hover either image: the zoomed preview shows the full card face, not an empty frame.
#   • Do the same on P1's discard (HMW_171 preview + SEC_180 released) — the "my" side uses the
#     same builder and would fail identically.
#   • Check in Chromium AND Firefox (repo cross-browser rule); WebKit will not launch on this
#     machine, so say so rather than implying it was covered.
#
# The GIVEN state is the whole check — there are no WHEN steps.

## GIVEN
CommonSetup: ngw/ngw/{}
WithP1Discard: [HMW_171 SEC_180]
WithP2Discard: [HMW_162 LOF_107]

## WHEN

## EXPECT
P1DISCARDCOUNT:2
P2DISCARDCOUNT:2
