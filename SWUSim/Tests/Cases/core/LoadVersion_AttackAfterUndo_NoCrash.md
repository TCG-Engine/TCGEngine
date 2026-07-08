# LoadVersion keeps a relative Location + owner PlayerID (regression for the replay "Play All" crash).
#
# A mid-game undo (SaveVersion→LoadVersion) reconstructs every zone object. LoadVersion must rebuild a
# unit exactly like ParseGamestate does — relative Location ('GroundArena') and its owner's PlayerID —
# because the engine composes zone names as "their" . Location (GetMzID / SWUGetValidAttackTargets).
#
# The bug: LoadVersion built units with the ABSOLUTE 'p2GroundArena' and PlayerID 0, so the next attack
# computed "their" . "p2GroundArena" = "theirp2GroundArena", GetZone() returned null, and combat fatalled
# on count(null) (CombatLogic.php:1932). In normal play a disk write + re-ParseGamestate after every
# action re-normalizes the objects, hiding it; an in-memory "Play All" replay skips that boundary and
# carried the corruption into the next action → HTTP 500.
#
# Repro: P2's ready 3/3 attacks P1's base straight after an UndoCycle. It must deal 3 (attack resolves),
# not crash.

## GIVEN
CommonSetup: grw/grw
WithP2GroundArena: SOR_095:1:0   # Battlefield Marine 3/3 (ready)

## WHEN
- P1>Pass          # hand the turn to P2
- P2>UndoCycle     # SaveVersion → LoadVersion: reconstruct all zones (P2's Marine included)
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
