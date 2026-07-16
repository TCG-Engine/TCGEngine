# AttachToNonVehicleBuffs
#// SOR_054 Jedi Lightsaber — Upgrade (+3/+3), "Attach to a non-VEHICLE unit."
#// P1 has a Vehicle (AT-AT idx 0) and a non-Vehicle (Battlefield Marine idx 1).
#// The Vehicle is filtered out, so the only valid target is the Marine → auto-attach.
#// Marine becomes 3+3 / 3+3 = 6/6 with one upgrade; the Vehicle is untouched.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3;handCardIds:SOR_054}
WithP1GroundArena: SOR_148:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:CARDID:SOR_148
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_054
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:6

---

# ForceHostDebuffsDefender
#// SOR_054 Jedi Lightsaber — when attached to a FORCE unit it grants:
#//   "On Attack: Give the defender −2/−2 for this phase."
#// Host = Mace Windu (SOR_149, Force, 5/7) + saber → 8/10 attacker.
#// Defender = SOR_119 (6/9) carrying a Shield so the 8 combat damage is fully
#// absorbed (shield), letting us read its post-attack stats cleanly:
#//   On-Attack shrink −2/−2 → power 6−2=4, HP 9−2=7. (Shrink is not damage, so the
#//   defender survives at 7 HP with 0 damage; the shield only stopped the combat hit.)

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_149:1:0
WithP1GroundArenaUpgrade: 0:SOR_054
WithP2GroundArena: SOR_119:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_119
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:7
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# NonForceHostNoDebuff
#// SOR_054 Jedi Lightsaber — the On-Attack shrink is granted ONLY to FORCE hosts.
#// Host = SOR_046 (Rebel/Trooper, non-Force, 3/7) + saber → 6/10 attacker.
#// Defender = SOR_119 (6/9) carrying a Shield (combat damage absorbed).
#// Host is not a Force unit, so no grant fires → defender keeps its printed 6/9.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_054
WithP2GroundArena: SOR_119:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_119
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:HP:9
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
