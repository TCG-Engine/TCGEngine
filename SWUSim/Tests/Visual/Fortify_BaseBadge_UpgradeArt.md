# VISUAL CHECK — the base's Fortify badge popup shows real card art (bug #970)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, then HOVER the badge on P1's base.
#
# WHY THIS EXISTS
# A base's attached Fortify upgrades are not drawn inline — the Base zone declares
# `Subcards: Flow=Badge` plus `Counters: UpgradeCount=Badge(...,PopupFrom=UpgradeCardIDs)`
# (Schemas/SWUSim/GameSchema.txt), so the ONLY way to see them is the hover popup.
# That popup used to build its image URL from the APP ROOT — ./SWUSim/concat/<id>.webp — a tree the
# shared-corpus migration deleted, so every card in it 404'd to broken alt text. SWU card art is one
# shared corpus at AppCore/SWU/Images/{concat,WebpImages}; the fix derives the folder from
# window.assetImageFolder (Core/CounterRendering.js), mirroring Card() and the lineage subFolder.
# ⚠ It was NOT a preview-card problem, even though it was reported as one: resolveCardImageID has
# already applied the mock_ prefix by that point, so a released card (SOR_120 on the enemy unit here)
# failed identically. The report only looked preview-related because Fortify exists solely in HMW.
# ⚠ PROD MASKED IT — prod still carries the old per-app tree, so this reproduces on local/dev only.
#
# WHAT TO LOOK AT
#   • P1's base shows a "2" badge (bottom-left) — two Fortify upgrades attached.
#   • HOVER the badge: a panel titled "Attached Upgrades" opens with TWO card images —
#     HMW_081 Alliance Shield Generator and HMW_171 Trap Field. Both must render as ART,
#     not as a broken-image icon or alt text.
#   • HOVER one of those images: the zoomed preview (ShowSubcardDetail) shows the full card,
#     i.e. the WebpImages face with readable rules text, not a blank frame.
#   • The enemy Battlefield Marine wears SOR_120 Academy Training — its own upgrade sliver art
#     is the released-card control; it renders through the arena path, which was never broken.
#   • Check in Chromium AND Firefox (per the repo's cross-browser rule); WebKit will not launch
#     on this machine, so say so rather than implying it was covered.
#
# The GIVEN state is the whole check — there are no WHEN steps.

## GIVEN
CommonSetup: bbw/bbw/{}
WithP1BaseUpgrade: HMW_081
WithP1BaseUpgrade: HMW_171
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
