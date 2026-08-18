# VISUAL CHECK — the discard pile shows the LATEST card; opening it lists earliest → latest
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor and look at BOTH discard piles, then click one.
#
# WHY THIS EXISTS
# PopulateZone's Single branch renders zoneArr[0] unless told otherwise, so the collapsed pile showed
# the FIRST card ever discarded and never changed all game. The Discard zone now declares
# `Mode=Single(Latest)` (Schemas/SWUSim/GameSchema.txt — same mechanism as the Deck's Single(Stacked)),
# consumed by ShouldShowLatestInSingleZone in Core/UILibraries*.js.
# ⚠ Deliberately NOT `Sort: Reverse`: that reverses the whole zone array for every mode except Tile,
# which would ALSO flip the click-to-open popup — and that view must stay earliest → latest.
# Both halves are therefore worth eyeballing in the same screenshot.
#
# WHAT TO LOOK AT
#   • P1's discard pile shows SEC_180 (discarded LAST), not HMW_171 (discarded first).
#   • P2's discard pile shows LOF_107 (discarded LAST), not HMW_162 (discarded first).
#   • Click either pile: the modal lists the cards EARLIEST → LATEST, i.e. the preview card first
#     and the released card second — the opposite order to the face-up pile art. That inversion is
#     the whole point of the change and is what a Sort.Reverse implementation would have broken.
#   • Every card renders as art rather than a broken-image icon (bugs #970/#971 — the popup art path).
#   • Check in Chromium AND Firefox (repo cross-browser rule); WebKit will not launch on this machine,
#     so say so rather than implying it was covered.
#
# ⚠ Requires a regenerated engine: `php zzGameCodeGenerator.php rootName=SWUSim`. The zone metadata
# that carries the parameter lives in the gitignored GeneratedUI_*.js, so a stale build silently shows
# the OLD first-card behaviour and nothing else looks wrong.
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
