# VISUAL CHECK — arenas flooded with alternating token units
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor to eyeball arena layout/wrapping when
# both space and ground arenas are full on both sides.
#
# Per side (mirrored on P1 "my" and P2 "their"):
#   SPACE  — 3 TIE Fighter (JTL_T01) + 3 X-Wing (JTL_T02), ALTERNATING:
#            TIE, X-Wing, TIE, X-Wing, TIE, X-Wing
#   GROUND — 3 Battle Droid (TWI_T01) + 3 Clone Trooper (TWI_T02), ALTERNATING:
#            Droid, Trooper, Droid, Trooper, Droid, Trooper
#
# All units ready (:1), no damage (:0). Leaders/bases come from CommonSetup.
#
# What to look at:
#   • Both arenas on both sides hold 6 units that wrap/fill cleanly.
#   • The alternating token art reads correctly (TIE vs X-Wing, Droid vs Trooper).
#   • Arena borders + the new board-background filter render behind the full rows.

## GIVEN
CommonSetup: bbk/grw
WithP2Credits: 1
WithP1Resources: 10
WithP1Credits: 5
WithP1SpaceArena: JTL_T01:1:0
WithP1SpaceArena: JTL_T02:1:0
WithP1SpaceArena: JTL_T01:1:0
WithP1SpaceArena: JTL_T02:1:0
WithP1SpaceArena: JTL_T01:1:0
WithP1SpaceArena: JTL_T02:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T02:1:0
WithP2SpaceArena: JTL_T01:1:0
WithP2SpaceArena: JTL_T02:1:0
WithP2SpaceArena: JTL_T01:1:0
WithP2SpaceArena: JTL_T02:1:0
WithP2SpaceArena: JTL_T01:1:0
WithP2SpaceArena: JTL_T02:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T02:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T02:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T02:1:0
WithP1Hand: SEC_035 SEC_035 SEC_035 SEC_032 SEC_032 SEC_032 LOF_034 LOF_034 LOF_034 JTL_034 JTL_034 JTL_034
WithP2Hand: SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095

## WHEN

## EXPECT
P1SPACEARENACOUNT:6
P1GROUNDARENACOUNT:6
P2SPACEARENACOUNT:6
P2GROUNDARENACOUNT:6
