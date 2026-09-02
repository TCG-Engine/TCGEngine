# Dengar_UpgradePlayed_MayDeal1
#// SHD_133 Dengar — "When you play an upgrade on a unit: You may deal 1 damage to that unit." With Dengar
#// in play, P1 plays SOR_069 onto SOR_046; Dengar's reaction deals 1 to SOR_046.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SHD_133:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1

---

# FortifyUpgradeOnABase_DoesNotTrigger
#// THE ARENA/HOST NEGATIVE. Dengar reads "when you play an upgrade ON A UNIT" - a Fortify upgrade
#// ("attach this to your BASE, not a unit") is still an upgrade being played, but its host is a base, so
#// the trigger must not fire and there is nothing for the 1 damage to land on.
#// HMW_081 Alliance Shield Generator is the inert Fortify choice: its only ability prevents damage of 5
#// or more to the attached base, which nothing here deals.
#// Without this, a trigger keyed on "an upgrade was played" rather than on the HOST being a unit passes
#// the file's existing section and fires on every Fortify play.
## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_081]
WithP1GroundArena: SHD_133:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P2NODECISION
P1GROUNDARENAUNIT:0:CARDID:SHD_133
P1GROUNDARENAUNIT:0:DAMAGE:0
