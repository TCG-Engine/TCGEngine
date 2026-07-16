# BounceEnemyGroundUnit
#// SOR_222 Waylay — bounce an enemy ground unit back to its owner's hand

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# BounceOwnUnit
#// SOR_222 Waylay — can also bounce your own unit back to hand

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# BounceSpaceUnit
#// SOR_222 Waylay — can target space arena units

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirSpaceUnit:0

## EXPECT
P2SPACEARENACOUNT:0
P2HANDCOUNT:1

---

# BounceTokenUnits
#// SOR_222 Waylay — bouncing token units sets them aside (not returned to hand or discard)
#// P2 has 3 ground tokens and 2 space tokens. P1 bounces all 5.
#// Expected: all tokens set aside, P2 hand/discard empty, P1 discard has 5 Waylays.

## GIVEN
CommonSetup: ybk/grw/{myResources:15;handCardIds:SOR_222,SOR_222,SOR_222,SOR_222,SOR_222}
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T02:1:0
WithP2GroundArena: SEC_T01:1:0
WithP2SpaceArena: JTL_T01:1:0
WithP2SpaceArena: JTL_T02:1:0
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>PlayHand:0
- P1>ChooseTheirSpaceUnit:0
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:5

---

# StripsUpgrade
#// SOR_222 Waylay — upgrades on a bounced unit are defeated (CR 9.3)
#// Non-token upgrade (LOF_215) goes to the upgrade owner's discard

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2DISCARDCOUNT:1
