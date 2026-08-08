# WhenDefeated_ShieldDamaged
#// LOF_064 Tauntaun (3/3) — When Defeated: may give a Shield token to a damaged non-Vehicle unit. It
#// attacks a 4/7 and dies; P1 shields its damaged friendly SOR_046.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_064:1:0
WithP1GroundArena: SOR_046:1:3
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# WhenDefeated_OnlyDamagedNonVehicleSelectable
#// LOF_064 Tauntaun — the shield target must be a DAMAGED, NON-VEHICLE unit (either player's). On defeat,
#// of P1's own units only the damaged non-Vehicle SOR_046 qualifies: the undamaged Wampa (LOF_164) is
#// excluded (not damaged) and the damaged vehicle Rogue Squadron Speeder (IBH_004) is excluded (Vehicle).
#// The enemy LAW_124 that Tauntaun just attacked is now damaged and non-Vehicle, so it is ALSO a valid
#// target — the card says "a damaged non-Vehicle unit" with no friendly restriction. Selectable is exactly
#// {SOR_046, LAW_124}. Intended: "no undamaged units or vehicles" (their setup had only friendlies).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_064:1:0
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: LOF_164:1:0
WithP1GroundArena: IBH_004:1:2
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# WhenDefeated_NoGloryControlChange
#// LOF_064 Tauntaun — When Defeated resolves for whoever CONTROLS the unit at defeat time. P2 plays No
#// Glory, Only Results (JTL_043) to take control of P1's Tauntaun and defeat it; the When Defeated choice
#// now belongs to P2, who shields their own damaged SOR_095. Intended: "should work with No Glory,
#// Only Results."

## GIVEN
CommonSetup: bbk/yyw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LOF_064:1:0
WithP2GroundArena: SOR_095:1:2
WithP2Hand: JTL_043
WithP2Resources: 13

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
