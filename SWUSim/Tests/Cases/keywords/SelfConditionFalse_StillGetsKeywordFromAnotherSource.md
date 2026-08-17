# Grit_LuminaraCoordinateOff_StillGetsGritFromVeiledStrength
#// ⚠ REGRESSION GUARDS for an ORDERING bug class in HasConditionalKeyword_* (found 2026-08-17).
#//
#// Several cards grant themselves a keyword under a condition ("while Coordinate is active", "while it
#// has 4 or more power"). Those live in a `switch ($obj->CardID)` whose cases RETURN AN EXPRESSION — so
#// when the condition is false the case returns false and, if the switch sits ABOVE the other grant
#// sources, it short-circuits the whole reader. The unit then loses keywords granted by a completely
#// unrelated card — an attached upgrade, an aura, a leader.
#//
#// The rule now enforced in the code: a self-conditional switch must be LAST, so `return false` means
#// "no SELF grant", never "no keyword from any source". Four readers were affected — Grit, Saboteur,
#// Overwhelm and Bounty — plus Sentinel, fixed earlier with its own guard on Koska Reeves.
#//
#// TWI_050 Luminara Unduli gains Grit only while Coordinate is active. She is alone here, so Coordinate
#// is OFF and her self-case returns false — but she wears LAW_128 Veiled Strength ("Attached unit gains
#// Grit"), which must still apply. Measured before the fix: no Grit at all.

## GIVEN
CommonSetup: ggw/bgw/{}
WithP1GroundArena: TWI_050:1:0
WithP1GroundArenaUpgrade: 0:LAW_128

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_050
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit

---

# Grit_LuminaraCoordinateOff_NoUpgrade_NoGrit
#// The control that makes the section above discriminating: identical board, upgrade REMOVED. With her
#// self-condition false and no other source, Luminara has no Grit — so a "Grit always on" bug cannot
#// satisfy the pair.

## GIVEN
CommonSetup: ggw/bgw/{}
WithP1GroundArena: TWI_050:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_050
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit

---

# Saboteur_CoordinateOff_StillGetsSaboteurFromInfiltratorsSkill
#// Same class, Saboteur reader. TWI_243 Republic Commando gains Saboteur only while Coordinate is
#// active; alone on the board it is OFF, so his self-case returns false. SOR_166 Infiltrator's Skill
#// ("Attached unit gains Saboteur") sat AFTER the self-switch and was therefore unreachable for him.
#// ⚠ The host is chosen so the UPGRADE cannot satisfy the host's own gate: Coordinate counts units,
#// and an upgrade does not add one. (An earlier draft used a host gated on POWER with an upgrade that
#// grants +3 power — it lifted the host past its own gate, so the section passed either way and proved
#// nothing.)

## GIVEN
CommonSetup: ggw/bgw/{}
WithP1GroundArena: TWI_243:1:0
WithP1GroundArenaUpgrade: 0:SOR_166

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_243
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# Saboteur_CoordinateOff_NoUpgrade_NoSaboteur
#// Control for the section above: same host, no upgrade, Coordinate still off — no Saboteur.

## GIVEN
CommonSetup: ggw/bgw/{}
WithP1GroundArena: TWI_243:1:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# Overwhelm_ResourceGateFalse_StillGetsOverwhelmFromAnUpgrade
#// Same class, Overwhelm reader — the worst of the four, with SIX grant sources below its self-switch.
#// HMW_118 Ryyk Blademaster gains Overwhelm only while you control 6+ resources; at FIVE its self-case
#// returns false. TWI_236 Grievous's Wheel Bike ("Attached unit gains Overwhelm") must still apply.
#// ⚠ Host and upgrade are deliberately mismatched in kind: the Wheel Bike grants +3 POWER, which cannot
#// satisfy a RESOURCE gate — so the upgrade can never accidentally switch the host's own case on.

## GIVEN
CommonSetup: ggw/bgw/{myResources:5}
WithP1GroundArena: HMW_118:1:0
WithP1GroundArenaUpgrade: 0:TWI_236

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_118
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm

---

# Overwhelm_ResourceGateFalse_NoUpgrade_NoOverwhelm
#// Control: same board at five resources with no upgrade — no Overwhelm from any source.

## GIVEN
CommonSetup: ggw/bgw/{myResources:5}
WithP1GroundArena: HMW_118:1:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
