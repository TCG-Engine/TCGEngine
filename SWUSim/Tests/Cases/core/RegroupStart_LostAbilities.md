# Fireball_Blanked_TakesNoRegroupDamage
#// CR 8 "lose all abilities": the card has no abilities, INCLUDING ones given by other cards, for the effect's duration.
#// A unit blanked "this round" (JTL_018 Kazuda Xiono's Action, which survives into regroup) has no "When the regroup
#// phase starts" ability. HMW meta run 2026-10-24: six Fireballs blanked by Kazuda still took their regroup 1, and two
#// died to it — _SWURegroupStartTriggerItems checked LostAbilities only for LAW_071/LAW_073.
#// Fireball is unique, so the untouched control is jtl/Fireball_AnExplosionWithWings.md::RegroupSelfDamage (same board,
#// no blank → 1 damage).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_198:1:0:JTL_018

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_198
P1SPACEARENAUNIT:0:DAMAGE:0

---

# Lander_Blanked_TakesNoRegroupDamage
#// TS26_23: "When the regroup phase starts: Deal 4 damage to this unit." Blanked → no 4.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_23:1:0:JTL_018
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TS26_23
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# ZilloBeast_Blanked_DoesNotHeal
#// TWI_067 The Zillo Beast: "When the regroup phase starts: Heal 5 damage from this unit." Blanked → the damage stays.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_067:1:6:JTL_018

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_067
P1GROUNDARENAUNIT:0:DAMAGE:6

---

# Dume_Blanked_GivesNoExperience
#// LOF_055 Dume: "When the regroup phase starts: Give an Experience token to each other friendly non-Vehicle unit."
#// Blanked → the Marine gets nothing.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [LOF_055:1:0:JTL_018 SOR_095:1:0]

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# UWing_Blanked_GivesNoAdvantage
#// ASH_159 Alphabet Squadron U-Wing: "When the regroup phase starts: Give an Advantage token to a unit." Blanked → none.

## GIVEN
CommonSetup: rrw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: ASH_159:1:0:JTL_018

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_159
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
#// No "Give an Advantage token" prompt in front of the regroup resource step.
P1DECISIONTOOLTIP:Resource_up_to_1_card

---

# HeightenedAwareness_BlankedWearer_GetsNoAdvantage
#// ASH_227 Heightened Awareness: "Attached unit gains: 'When the regroup phase starts: give an Advantage token to this
#// unit.'" A GAINED ability — CR 8: a unit that lost all abilities loses the ones given by other cards too. Blanked
#// wearer → no Advantage; the second, untouched wearer still gets its token.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0:JTL_018 SOR_095:1:0]
WithP1GroundArenaUpgrade: [0:ASH_227 1:ASH_227]

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
