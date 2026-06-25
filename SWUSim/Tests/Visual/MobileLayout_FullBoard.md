# VISUAL CHECK — full board for the mobile (vertical-stack) layout
#
# Visual-only schema. Lives under Tests/Visual/, which the regression endpoint does
# NOT scan (it only walks Tests/Cases/), so this is never asserted automatically.
# Load it by hand in the Test Schema Editor and view with ?swuLayout=mobile to
# eyeball GameLayoutMobile.php against a populated board.
#
# Board (P1 = me, P2 = opponent):
#   • Me (P1):   Darth Vader leader (JTL_006) + Amnesty Housing base (SEC_025, 16 dmg)
#                Space: 2× TIE Fighter tokens (JTL_T01, one Shielded/SOR_T02), Remnant Interceptor
#                       (ASH_095, +XP/SOR_T01), Marrok's Fiend Fighter (ASH_241, 2 dmg + Boba Fett JTL_189)
#                Ground: 4× Battle Droid tokens (TWI_T01) — 2 ready, 2 exhausted
#                Hand:  Craving Power (LOF_091)
#                Discard: 2× Air Superiority (JTL_125)
#                5 resources
#   • Them (P2): Luke Skywalker leader (JTL_012) + Data Vault base (JTL_024, 23 dmg)
#                Space: T-6 Shuttle (ASH_109, 2 dmg), Alphabet Squadron U-Wing (ASH_159)
#                Ground: R2-D2 (LAW_145, 1 dmg + XP), Grogu (ASH_155, 5 dmg + Yoda's Lightsaber
#                        LOF_102 + Shield)
#                Hand:  Blue Leader (JTL_096), Resistance Blue Squadron (JTL_102)
#                Discard: 2× Air Superiority (JTL_125), 1× System Shock (JTL_175)
#                5 resources
#
# What to look at:
#   • Their control band (top), their hand, their Space|Ground row, their Leader/Base,
#     my Leader/Base, my Space|Ground row, my hand, my sticky control band (bottom).
#   • Two-row arena grids scroll horizontally; my 4 space units exercise that.
#
# No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
SkipPreGame: true
P1LeaderBase: JTL_006/SEC_025:16
P2LeaderBase: JTL_012/JTL_024:23
WithP1Resources: 5
WithP2Resources: 5

WithP1SpaceArena: JTL_T01:1:0
WithP1SpaceArena: JTL_T01:1:0
WithP1SpaceArena: ASH_095:1:0
WithP1SpaceArena: ASH_241:1:2
# Shield on a TIE token (idx 0); XP on Remnant Interceptor (idx 2);
# Boba Fett (JTL_189, Cunning/Villainy pilot) attached to ASH_241 (idx 3) + 2 dmg.
# Shield/XP tokens are the sim's canonical SOR_T02 / SOR_T01 (UILibraries + the
# shield-break animation only recognize those; JTL_T04/JTL_T03 reprints don't render).
WithP1SpaceArenaUpgrade: 0:SOR_T02
WithP1SpaceArenaUpgrade: 2:SOR_T01
WithP1SpaceArenaUpgrade: 3:JTL_189

WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0

WithP2SpaceArena: ASH_109:1:2
WithP2SpaceArena: ASH_159:1:0
WithP2GroundArena: LAW_145:1:1
WithP2GroundArena: ASH_155:1:5
# XP on R2-D2 (idx 0); Yoda's Lightsaber + Shield on Grogu (idx 1)
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 1:LOF_102
WithP2GroundArenaUpgrade: 1:SOR_T02

WithP1Hand: LOF_091
WithP2Hand: JTL_096 JTL_102

# Discard piles: 2 Air Superiority each; their pile also holds 1 System Shock
WithP1Discard: JTL_125 JTL_125
WithP2Discard: JTL_125 JTL_125 JTL_175

## WHEN

## EXPECT
P1SPACEARENACOUNT:4
P2SPACEARENACOUNT:2
P1GROUNDARENACOUNT:4
P2GROUNDARENACOUNT:2
P1RESCOUNT:5
P2RESCOUNT:5
P1HANDCOUNT:1
P2HANDCOUNT:2
P1DISCARDCOUNT:2
P2DISCARDCOUNT:3
