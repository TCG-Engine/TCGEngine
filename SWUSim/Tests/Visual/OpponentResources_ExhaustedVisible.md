# VISUAL CHECK — the opponent's spent resources are visible to you
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, viewing as P1.
#
# WHY THIS EXISTS
# Resources is a Visibility=Self zone, so the transport masks the opponent's cards behind a CardBack.
# It used to send NO cardJSON with them, which also hid whether each resource was EXHAUSTED — and that
# is public information in SWU (the physical card is visibly rotated; this zone already declares
# `Rotation: Status=0:9` and `Overlay: Status=0:exhausted`).
# parseResCountFromData counts every entry toward the total but can only count an exhausted one when the
# entry carries {"Status":0}, so the opponent's badge read N/N for the whole game — reported as
# "my opponent's resources do not go down as they play cards", while their own client looked correct.
# The transport now sends {"Status":N} with a masked resource. The card's IDENTITY still never travels.
#
# WHAT TO LOOK AT (as P1)
#   • P2 has 6 resources, 2 of them exhausted. The opponent resource badge reads 4/6 — NOT 6/6.
#   • Those 2 face-down cards render ROTATED / dimmed like your own exhausted resources do. This is new:
#     with no Status the client could not apply the zone's Rotation/Overlay to them.
#   • The cards stay FACE DOWN — you must not be able to tell WHICH cards they are. Only the count and
#     the ready/exhausted split are public.
#   • Your own side reads 3/5 and behaves as it always did (the control — this half was never broken).
#   • Play a card as P2 in a live game and watch P1's badge drop; the static board here cannot show the
#     reactive half, which is the part the report was actually about.
#
# ⚠ Requires a regenerated engine: `php zzGameCodeGenerator.php rootName=SWUSim`. The masked-resource
# payload is emitted into the gitignored GetNextTurn.php, so a stale build silently shows the OLD N/N.
#
# The GIVEN state is the whole check — there are no WHEN steps.

## GIVEN
CommonSetup: ngw/ngw/{}
WithP1Resources: 5
WithP2Resources: 6

## WHEN

## EXPECT
P1RESCOUNT:5
P2RESCOUNT:6
