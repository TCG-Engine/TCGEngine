# WhenPlayed_DefeatNonUniqueUpgrades
#// SEC_149 Kaydel Connix (Unit, Aggression/Heroism, cost 3) — When Played: you may defeat all non-unique
#//   upgrades on a unit. (Plot keyword dormant from hand.) SOR_095 bears SOR_120 (non-unique) → defeated.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1Hand: SEC_149

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# WhenPlayed_ShieldTokensAreNonUniqueUpgrades
#// Discord 2026-10-25: "someone used Kaydel to try and remove a shield and it didn't work." CR: token upgrades follow the
#// same rules as upgrades and tokens are not unique, so a Shield is a non-unique upgrade. The handler skipped every token
#// ("tokens aren't upgrades here"), so a unit whose only upgrades are Shields was not even offered. P2's marine wears two
#// Shields and nothing else: it must be offered, and both Shields are defeated.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: [0:SOR_T02 0:SOR_T02]
WithP1Hand: SEC_149

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# WhenPlayed_TokensAndCardsGo_UniqueStays
#// Same rule with a mix: two Shields + an Experience token + a non-unique card upgrade (SOR_120) are all defeated; the
#// unique upgrade (SOR_053) stays — the control that "non-unique" still excludes something.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: [0:SOR_T02 0:SOR_T02 0:SOR_T01 0:SOR_120 0:SOR_053]
WithP1Hand: SEC_149

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_053
