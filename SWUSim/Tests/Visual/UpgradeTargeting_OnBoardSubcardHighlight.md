# VISUAL CHECK — upgrade/shield targeting highlights ON THE BOARD, attached to its host
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it in the
# Test Schema Editor and PLAY the hand cards one at a time.
#
# WHAT CHANGED, AND WHAT TO LOOK FOR
# Effects that target an upgrade used to stage the candidates into a TempZone as bare card art and
# show a flat popup — no indication of WHICH unit each upgrade was attached to, and no way at all to
# tell two identical Shield tokens apart. They are now offered as SUBCARD mzIDs
# ("<zone>-<hostIdx>.u<subIdx>"), so the candidates highlight in place, on their hosts.
#
# Expected on an offer:
#   • Each eligible UPGRADE SLIVER (the strip peeking below a unit) gets an AMBER ring + soft glow,
#     pulsing slowly, and lifts above its host (z-index) so the ring is not clipped by the card.
#   • Each eligible SHIELD ORB (top-right of a unit) gets the same amber ring, rounded to a circle,
#     and becomes clickable — orbs are pointer-events:none decorations at every other time.
#   • The HOST UNITS themselves must NOT glow lime. Only the attachments are targets. If a whole
#     unit lights up, IsSelectableCard's subIndex guard has regressed.
#   • Ineligible attachments (wrong filter) stay completely unhighlighted.
#   • Hovering an eligible sliver/orb brightens it and tightens the ring; the cursor is a pointer.
#   • Clicking one answers the decision, and EVERY amber ring clears immediately — no orphan glow
#     left behind on any other unit (ClearSelectionMode sweeps .selectable-subcard separately from
#     .selectable-card, because a subcard is not a .selectable-card, it lives inside one).
#
# CARDS TO PLAY (P1 has 20 resources; CommonSetup rbw covers Aggression + Heroism, so no cost surprises
# for SEC_163/LOF_147; JTL_242 and JTL_056 are Villainy and WILL pay the off-aspect +2, which is fine
# at 20 resources and keeps one CommonSetup for all four cards.)
#   • JTL_242  Shuttle ST-149      → "take control of a TOKEN upgrade on a unit". Resolve the
#                                    take-control trigger first (EffectStack pick — it shares the
#                                    play with Shielded). Eligible: every Experience/Shield token on
#                                    the board, INCLUDING both Shields on the same unit, which must
#                                    be separately clickable. NOT eligible: the real upgrades.
#                                    After picking, you then pick a destination unit as usual.
#   • JTL_056  Hondo Ohnaka        → "take control of a NON-PILOT upgrade". Attack with him. The
#                                    eligible set is the opposite one: the real upgrades highlight,
#                                    the tokens do not.
#   • SEC_163  Outer Rim Constable → "You may defeat an upgrade." This is the DEFEAT case and it is now
#                                    ONE decision: every upgrade in play highlights at once — both
#                                    arenas, both sides, tokens included — and clicking one defeats it
#                                    on the spot. It used to be two decisions (pick a unit, THEN pick
#                                    from a popup of that unit's upgrades), so you had to commit to a
#                                    unit before you could see what was on it. Check that the pool
#                                    spans the SPACE arena too, and that picking the second upgrade on
#                                    a two-upgrade host kills that one and not its neighbour.
#   • LOF_147  Kit Fisto's Aethersprite → "defeat ANY NUMBER of upgrades on a unit". This is the one
#                                    upgrade flow still on the OLD two-step path (pick a host, then the
#                                    MZMultiChoose modal), because its multi-pick is scoped to a single
#                                    host. Included here as the CARD-ART regression check: that modal
#                                    used to render every tile as a broken image with the alt text
#                                    "Card", because it hand-built "<rootPath>/concat" (= ./SWUSim/concat,
#                                    a tree the shared-corpus migration deleted) instead of using
#                                    window.assetImageFolder. Every tile must now show real card art.
#
# CROSS-BROWSER: check Chromium, Firefox AND Safari — the highlight uses :not() with a class carve-out
# and a keyframed box-shadow, and the slivers sit in an overflow:visible span whose stacking differs
# between engines. Also check ?swuLayout=mobile, where slivers are smaller and orbs sit tighter.
#
# BOARD SHAPE (deliberately mixed so the filters visibly discriminate)
#   P1 ground 0 — SEC_164 wearing an Experience token AND a real upgrade  (token + non-token on ONE host)
#   P1 ground 1 — SOR_095 wearing TWO Shield tokens                       (two identical tokens, one host)
#   P1 space  0 — JTL_T01 wearing an Experience token                     (a second arena, for the sweep)
#   P2 ground 0 — HMW_107 wearing a real upgrade AND a Shield token       (enemy host, both kinds)
#   P2 ground 1 — SOR_095 with nothing                                    (a host with no attachments)
#
# No WHEN steps — interaction is manual.

## GIVEN
CommonSetup: rbw/nbk
WithP1Resources: 20

WithP1GroundArena: SEC_164:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:ASH_086
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP1SpaceArena: JTL_T01:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T01

WithP2GroundArena: HMW_107:1:0
WithP2GroundArenaUpgrade: 0:ASH_086
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_095:1:0

WithP1Hand: JTL_242 JTL_056 SEC_163 LOF_147

## WHEN

## EXPECT
# Not run by the regression runner — kept so the fixture can be validated by hand (and because a
# wrong board makes the visual check meaningless). ⚠ UPGRADECOUNT counts SHIELD tokens too: it is the
# live-upgrade ordinal space, which excludes only captives and removed entries. Hence P2 ground 0 is
# 2 (ASH_086 + Shield), not 1.
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:1
P2GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:SHIELDCOUNT:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1HANDCOUNT:4
P1RESCOUNT:20
