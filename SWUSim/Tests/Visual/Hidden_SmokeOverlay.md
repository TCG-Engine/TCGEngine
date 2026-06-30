# VISUAL CHECK — Hidden smoke overlay (unattackable units)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor.
#
# A Hidden unit can't be attacked the phase it was played (CR). While it's in that
# unattackable state, a full-card smoke overlay (Assets/Overlays/smoke-overlay.webp)
# is shown UNDER the power/HP/damage badges (low DrawOrder). It clears once the phase
# ends and the unit becomes attackable.
#
# The overlay is driven by the SWU_PLAYED_UNIT flag, which is only set when a unit is
# actually PLAYED — a unit dropped straight into the arena via GIVEN is already
# attackable and shows no smoke. So this test plays a Hidden unit from hand:
#   LOF_107 Village Tender (Hidden, cost 1) in P1's hand; the WHEN step plays it.
#
# How to view:
#   1. Load the schema — P1 has LOF_107 in hand, 5 resources.
#   2. Step the WHEN ("P1 plays LOF_107"). The unit enters the ground arena.
#   3. The smoke overlay covers the card, BEHIND its power/HP badges.
#   4. (Optional) advance to the next phase — the smoke disappears as it becomes attackable.

## GIVEN
CommonSetup: bbk/grw
WithP1Hand: LOF_107
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:1:CARDID:LOF_107
