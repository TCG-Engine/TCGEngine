# VISUAL CHECK — Smuggle icon on a Plot card in the resource zone (Tech in play)
#
# Visual-only schema. Lives under Tests/Visual/, which the regression endpoint does
# NOT scan (TestRunner + SchemaBasedTest only walk Tests/Cases/), so this is never
# asserted automatically. Load it by hand in the Test Schema Editor to eyeball the
# board.
#
# Scenario: P1 controls SHD_248 "Tech" (Source of Insight) in their ground arena —
#   "Each friendly resource gains Smuggle." (CR Smuggle; HasConditionalKeyword_Smuggle).
# P1's resource zone holds a Plot card (SEC_034 Cad Bane) at index 0 plus four
# vanilla resources. Because Tech is in play, EVERY friendly resource gains Smuggle,
# so each resource card — including the Plot card — should render the smuggle.webp
# icon at its bottom (the resource-zone Image counter wired in GameLayout.php).
#
# What to look at:
#   • The Plot card (myResources-0) and the vanilla resources all show the smuggle icon.
#   • Remove SHD_248 from the arena (or load a schema without it) and the icons vanish.
#
# No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
CommonSetup: bbk/grw
WithP1GroundArena: SHD_248:1
WithP1Resources: 1:SEC_034:1,4:SOR_095:1

## WHEN

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SHD_248
P1RESCOUNT:5
