# DarkTrooper_CombatDamageSurvive_Exp
#// SHD_084 Phase-III Dark Trooper — "When combat damage is dealt to this unit: Give an Experience token
#// to this unit (if it survives)." Dark Trooper (3/3) attacks SHD_095 (2/3): it deals 3 (kills SHD_095)
#// and takes 2 counter-damage, surviving → gets an Experience token (→ 4/4 with 2 damage).

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SHD_084:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:POWER:4

---

# DarkTrooper_DEFENDS_ExperienceGoesToTheTrooper_NotTheAttacker
#// ⚠ ENGINE BUG, fixed 2026-08-27 — and a TWO-PLAYER one, found by a Twin Suns-flavoured read.
#// `_SWUOnUnitDamaged` had NO `global $playerID;` anywhere in its body, so the SHD_084 case's frame pin
#//     $shd084Saved = $playerID; $playerID = $shd084Ctrl;
#// wrote a LOCAL and left the GLOBAL untouched. `$obj->GetMzID()` reads the global, so it minted the
#// Trooper's mzID in the ATTACKER's frame — `theirGroundArena-0`. `DoGiveExperienceToken($ctrl, $mz)`
#// then re-resolves that string under `$ctrl`'s OWN frame, where `their…-0` means the attacker's index 0.
#// Net effect: when the Dark Trooper DEFENDED, its Experience token was handed to the unit that hit it.
#//
#// ⚠ THE EXISTING SECTION ABOVE CANNOT SEE THIS. There the Trooper ATTACKS, so the ambient frame already
#// IS its controller's, `my…` is minted, and the broken pin is harmless. Only a section where the
#// Trooper is the DEFENDER puts a foreign frame on the global at trigger time.
#// ⚠ It is specifically a 2-player bug: above two seats GetMzID takes its absolute `p{n}…` branch for a
#// foreign frame, which survives the re-resolve. The seat-count reasoning found it; the fixture is 2P.
#//
#// P2's SOR_063 (2/4) attacks P1's Dark Trooper (3/3, Sentinel so it is the only legal target). The
#// Trooper takes 2 and SURVIVES → its own Experience token, 3/3 → 4/4. The attacker takes the 3 counter,
#// survives at 3 of 4, and must end with NO token and its printed power.

## GIVEN
CommonSetup: ggk/bbw
WithActivePlayer: 2
WithP1GroundArena: SHD_084:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_084
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
