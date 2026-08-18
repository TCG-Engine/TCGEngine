# Default_UnrestrictedUpgradeReachesEveryUnitInPlay
#// ENGINE RULE (CR 2.e) — an upgrade that prints NO controller restriction may be played onto ANY unit in
#// play, including an ENEMY one, and that unit's controller then resolves anything it grants. The legal-host
#// default in SWUGetUpgradeValidTargets is therefore all four arenas, and every narrowing in that switch is
#// an exception to this rule.
#// SOR_120 Academy Training is the plainest possible probe: a vanilla +2/+2 with no text at all, so nothing
#// but the default can be under test. All four seats are legal hosts.
#// ⚠ This file exists because the default was INVERTED until 2026-08-17: it used to be friendly-arenas-only
#// with a 32-case fall-through group restoring the rules default one reported card at a time. That made the
#// switch an allowlist, and an upgrade nobody had thought about was silently friendly-only — which inverted
#// whole cards (LAW_127 Kill Switch, TWI_070 Perilous Position: pure drawbacks playable only on your own
#// units). If this section ever goes red, the default has been flipped back.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# PrintedFRIENDLYRestrictionNarrowsItBackToYourOwnArenas
#// The other half of the same rule: when the card DOES print "Attach to a FRIENDLY unit", the pool narrows
#// to the player's own two arenas. SEC_069 Nimble Prowess prints exactly that. Without this section the
#// inverted default could quietly swallow the four printed-friendly upgrades (SHD_251, SHD_124, LOF_091,
#// SEC_069), which had no switch case of their own while friendly-only was the default and needed one
#// added when it flipped.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: SEC_069

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# PrintedFRIENDLYPlusATraitFilter_BothApply
#// SHD_251 The Mandalorian's Rifle prints "Attach to a friendly NON-VEHICLE unit", so both narrowings have
#// to hold at once. Board: the friendly non-Vehicle SEC_080 is IN; the friendly Vehicle SOR_232 is OUT on
#// trait; and the enemy non-Vehicle SOR_046 is OUT on controller — the case that separates this from the
#// plain non-Vehicle group, which spans both sides.
#// With both filters applied the pool collapses to ONE host, so the attach auto-resolves and the end state
#// IS the assertion: the Rifle lands on SEC_080 while the Vehicle and the enemy unit carry nothing.

## GIVEN
CommonSetup: bbk/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SHD_251

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_232
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
