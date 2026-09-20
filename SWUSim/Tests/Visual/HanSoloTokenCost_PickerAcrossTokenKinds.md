# VISUAL CHECK — Han Solo's "[defeat a friendly token]" cost is picked ON THE BOARD
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it in the Test
# Schema Editor and use P1's leader ability.
#
# WHAT CHANGED, AND WHAT TO LOOK FOR
# LAW_017 Han Solo's cost used to be a flat text-button popup whose labels were raw internal keys
# ("Exp~myGroundArena-0~0", "Unit~myGroundArena-0", "Credit0"), and the list it offered was a
# whitelist of token kinds that silently excluded Advantage tokens. The cost now offers the tokens
# themselves as mzIDs, so it uses the ordinary on-board picker — the same one
# UpgradeTargeting_OnBoardSubcardHighlight.md documents.
#
# Expected when the leader ability is used:
#   • NO text-button popup with tilde-separated labels anywhere.
#   • Every friendly token highlights IN PLACE: the Experience and Advantage slivers under their
#     hosts, the Shield orb on its unit, the Credit in the resource row, and the Force token on
#     P1's base. Both arenas are in the pool — the SPACE unit's Advantage must highlight too.
#   • P2's tokens (the Shield on its unit and its Credit) must NOT highlight. "Friendly" follows
#     CONTROL, and an enemy token can never pay this cost.
#   • The HOST UNITS themselves must not glow — only the attachments and the token cards.
#   • Clicking one defeats exactly that token, every ring clears, and the prompt moves straight on
#     to "Deal 1 damage to a unit".
#
# DEPLOYED SIDE (worth a second pass): deploy Han with his Epic and attack. His On Attack offers the
# same pool, repeatedly, and the offer is DECLINABLE — the pass/decline control must be present,
# because "any number" includes zero. Declining ends the loop and deals no damage.
#
# CROSS-BROWSER: check Chromium, Firefox AND Safari, plus ?swuLayout=mobile — this is the shared
# subcard-highlight UI, whose rings and stacking differ between engines.
#
# BOARD SHAPE (one of every token kind, deliberately split across arenas and both players)
#   P1 ground 0 — SOR_095 wearing an Experience token AND a Shield token
#   P1 space  0 — ASH_167 wearing an ADVANTAGE token   (the kind the old whitelist refused)
#   P1 also holds a Credit token and the Force token
#   P2 ground 0 — SOR_046 wearing a Shield token, plus a Credit  (enemy tokens: never selectable)
#
# No WHEN steps — interaction is manual.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028
}
SkipPreGame: true
WithP1Resources: 20
WithP1Force: true
WithP1Credits: 1

WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1SpaceArena: ASH_167:1:0
WithP1SpaceArenaUpgrade: 0:ASH_T02

WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2Credits: 1

## WHEN

## EXPECT
# Not run by the regression runner — kept so the fixture can be validated by hand, because a wrong
# board makes the visual check meaningless.
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
P1CREDITCOUNT:1
P1HASFORCE
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2CREDITCOUNT:1
