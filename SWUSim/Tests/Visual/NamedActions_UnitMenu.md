# VISUAL CHECK — named Action abilities: the unit menu
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint). Load it in the Test Schema Editor,
# or run DevTools/ui-harness/swusim-named-actions-xbrowser.mjs (Chromium + Firefox + WebKit, desktop + phone).
#
# Clicking a unit that has any Action opens a menu: "Attack" (when it can attack) and one button per Action, named
# after the card that gives it. An Action it can't use right now is shown GREYED with the reason, and does nothing.
#   myGroundArena-0  Crosshair, EXHAUSTED, 4 resources                   → Crosshair [2 resources] ·
#                                                                          Crosshair [Exhaust] (greyed "Exhausted") ·
#                                                                          Satine Kryze (greyed "Exhausted")
#   myGroundArena-1  Battlefield Marine (P2's Satine grants every unit)  → Attack · Satine Kryze
#   myGroundArena-2  Chewbacca (deployed leader — the builder adds it after the listed units) + Improvised Identity
#                                                                        → Attack · Chewbacca · Improvised Identity ·
#                                                                          Satine Kryze
#   theirSpaceArena-0  P2's Mercenary Gunship ("any player may use")  → Mercenary Gunship — a MENU even with one usable
#                    Action (a unit click never runs an Action outright: one tap would spend 4 and take control).
#
# What to look at:
#   • Each label reads as above; greyed buttons are visibly dimmed and show their reason under the label.
#   • Clicking a greyed button does nothing and the menu stays.
#   • On a phone the menu stays on screen.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009;myLeaderDeployed:1;myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArenaUpgrade: 2:ASH_230
WithP1GroundArena: SHD_087:0:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: TWI_047:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SHD_256:1:0
WithP1Deck: [SOR_157 SOR_063 SOR_063]

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_087
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:2:CARDID:HMW_009
P1ACTIONENTRIES:myGroundArena-0:DISABLED:SHD_087.1&TWI_047.0
P1ACTIONENTRIES:myGroundArena-1:EXACT:TWI_047.0
P1ACTIONENTRIES:myGroundArena-2:EXACT:HMW_009.u0&ASH_230.0&TWI_047.0
P1ACTIONENTRIES:theirSpaceArena-0:ENABLED:SHD_256.0
