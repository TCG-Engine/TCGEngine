# VISUAL CHECK — a busy two-sided ground board: 5 damaged enemy units vs 3, one heavily Experienced
#
# Visual-only schema (Tests/Visual/ is NOT scanned by the regression endpoint). Load it by hand in the
# Test Schema Editor. There are no WHEN steps — the initial GIVEN state IS the whole check.
#
# The point is board LEGIBILITY at a realistic mid-game density, not any rule:
#   • a five-wide enemy ground row with mixed printed stats and mixed damage
#   • a three-wide friendly row where one unit is buffed far past its printed box
#   • the two resource rows in opposite states (all exhausted vs all ready)
#
# WHAT TO LOOK AT
#   1. THEIR ground row — 5 units, none dead, every one showing a DIFFERENT damage amount:
#        SOR_128 Death Star Stormtrooper  3/1  0 dmg   (undamaged, 1 HP — the fragile end)
#        SOR_095 Battlefield Marine       3/3  1 dmg
#        SOR_164 Wampa                    4/5  2 dmg
#        SOR_046 Consular Security Force  3/7  4 dmg
#        LOF_100 Kelleran Beq             7/7  3 dmg   (the big body)
#      Check the damage pips/counter stay readable at five-across and do not overlap the stat box.
#   2. MY ground row — 3 units, and ASH_195 Helgait carries FOUR Experience tokens (SOR_T01, +1/+1
#      each), so its printed 6/4 must render as 10/8. This is the main thing to eyeball: a
#      four-token stack plus a two-digit power AND a two-digit HP in the same card.
#   3. RESOURCES — theirs 0 ready of 6 (all exhausted), mine 6 ready of 6. The two rows should be
#      unmistakably different at a glance; this is the clearest side-by-side of the exhausted vs
#      ready resource treatment the suite has.
#   4. MY LEADER — JTL_002 Grand Admiral Thrawn, undeployed, in the leader zone.
#   5. MY HAND — one card, JTL_039 Chimaera (a SPACE unit while both arenas shown are ground).
#
# ⚠ Damage is deliberately kept BELOW each unit's HP so nothing dies during setup and the row stays
# five-wide — a dead unit would silently reduce the density this check exists to exercise.
#
# Aspect codes (CommonSetup {base}{leaderAspect}{leaderAlignment}):
#   ybw = Cunning base + Vigilance/Heroism leader   (them)
#   ybk = Cunning base + Vigilance/Villainy leader  (me) — overridden to Thrawn JTL_002 below

## GIVEN
CommonSetup: ybk/ybw/{myLeader:JTL_002;myhandCardIds:JTL_039}

# ── THEM: five mixed bodies, all damaged but alive, and every resource exhausted ──
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_164:1:2
WithP2GroundArena: SOR_046:1:4
WithP2GroundArena: LOF_100:1:3
WithP2Resources: 6:SOR_095:0

# ── ME: three bodies; Helgait at index 0 wears the four Experience tokens (6/4 → 10/8) ──
WithP1GroundArena: ASH_195:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: ASH_100:1:2
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01

# ── …plus a TWELVE-strong exhausted Battle Droid screen (TWI_T01, 1/1 token units) ──
# ⚠ Declared AFTER the three real bodies on purpose: the Experience upgrades above target ground
# index 0, so Helgait has to stay first. Put the droids ahead of it and all four tokens land on a
# 1/1 droid instead, which still loads clean and silently guts the check.
# `:0:` is the READY field — 0 = exhausted. Fifteen units on one side, twelve of them greyed out,
# is the densest friendly row in the suite and the real target of this fixture.
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP1GroundArena: TWI_T01:0:0
WithP1Resources: 6:SOR_095:1

## WHEN

## EXPECT
P2GROUNDARENACOUNT:5
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:4:CARDID:LOF_100
P2GROUNDARENAUNIT:3:DAMAGE:4
P1GROUNDARENACOUNT:15
P1GROUNDARENAUNIT:0:CARDID:ASH_195
P1GROUNDARENAUNIT:0:UPGRADECOUNT:34
# The buff is the whole point of the Helgait card, so assert the NUMBERS the art has to show —
# 6/4 printed + 4 Experience = 10/8. UPGRADECOUNT alone would still pass if the tokens rendered
# without applying, which is exactly the thing a visual check is looking for.
P1GROUNDARENAUNIT:0:POWER:40
P1GROUNDARENAUNIT:0:HP:8
# The two resource rows in opposite states — the other half of what this board is for.
# The droid screen: exhausted, and still holding index 3 onward (Helgait unmoved at 0).
P1GROUNDARENAUNIT:3:CARDID:TWI_T01
P1GROUNDARENAUNIT:3:EXHAUSTED
P1GROUNDARENAUNIT:14:CARDID:TWI_T01
P1GROUNDARENAUNIT:14:EXHAUSTED
P1RESCOUNT:6
P1RESAVAILABLE:6
P2RESCOUNT:6
P2RESAVAILABLE:0
P1HANDCOUNT:1
