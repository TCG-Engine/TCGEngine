# AttachToNonVader_NoEffect
#// SOR_136 Vader's Lightsaber — the deal-4 is conditional on the host being Darth Vader.
#// Attached to Battlefield Marine (not Vader), the upgrade still attaches but its When Played
#// does nothing: the enemy unit takes no damage and no decision is pending. Absence guard.

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_136
WithP1GroundArena: SEC_080:1:0    # non-Vader friendly host
WithP2GroundArena: SEC_080:1:0    # enemy unit — must be untouched

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# AttachToVader_Deals4
#// SOR_136 Vader's Lightsaber (Upgrade) — Attach to a non-Vehicle unit. When Played: If
#// attached unit is Darth Vader, you may deal 4 damage to a ground unit. P1 plays it onto
#// Darth Vader (SOR_087, the only friendly non-Vehicle unit); the host IS Vader, so on YES the
#// enemy Battlefield Marine (3 HP) is dealt 4 and defeated.

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_136
WithP1GroundArena: SOR_087:1:0    # Darth Vader (non-Vehicle host)
WithP2GroundArena: SEC_080:1:0    # enemy ground unit — the deal-4 target

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
