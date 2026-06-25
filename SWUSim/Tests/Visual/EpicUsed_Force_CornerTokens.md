# VISUAL CHECK — Epic-Action-Used tokens + the Force token
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor.
#
# Three corner-token states on P1's center column, all set from the GIVEN state
# (no WHEN steps needed):
#
#   1. LEADER Epic Action used — SOR_014 Sabine Wren with epicActionUsed=true.
#      The leader card in its slot shows the Epic-Action-Used token (the once-per-game
#      "Deploy" epic is spent). Rendered by the core Card() renderer via the
#      epicActionUsed flag. P2's leader (SOR_009) is left available for contrast.
#
#   2. RARE BASE Epic Action used — SOR_022 Energy Conversion Lab (Rare base,
#      "Epic Action: Play a unit costing 6 or less from hand; give it AMBUSH") with
#      epicActionUsed=true. Its base card shows the spent Epic-Action token.
#      P2's base (SOR_024 Echo Base) has no epic action.
#
#   3. P1 HAS THE FORCE — WithP1Force. The force-token.webp shows in the TOP-RIGHT
#      corner of P1's base, rendered inside the card by the core Card() renderer via
#      the base's HasForce virtual — same path/positioning as the Epic-Action-Used
#      token (just top:4px instead of bottom:4px), so the two line up vertically.
#
# Leader/base spec fields used below:
#   Leader  CardID:ready:deployed:epicUsed   → SOR_014:1:0:1
#   Base    CardID:damage:epicUsed           → SOR_022:0:1   (3rd field = base epic used)
#
# What to look at:
#   • P1 leader slot (Sabine Wren): Epic-Action-Used token present.
#   • P1 base slot (Energy Conversion Lab): Epic-Action-Used token present AND the
#     Force token glowing in the top-right corner.
#   • P2 leader/base: no epic-used tokens, no Force token.

## GIVEN
P1LeaderBase: SOR_014:1:0:1/SOR_022:0:1
P2LeaderBase: SOR_009/SOR_024
WithP1Force: true

## WHEN

## EXPECT
P1LEADER:EPICUSED
P1BASE:EPICUSED
P1HASFORCE
P2LEADER:EPICAVAILABLE
P2BASE:EPICAVAILABLE
P2NOFORCE
