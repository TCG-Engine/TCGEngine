# WhenPlayedTokenThenDealPower
#// LAW_039 Latts Razzi (2/1) — When Played: give a Shield or Experience token to this unit, then she
#// deals damage equal to her power to an enemy ground unit. Choose Experience (2/1 -> 3/2), deal 3 to
#// the enemy SOR_046 (3/7).

## GIVEN
CommonSetup: bgw/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_039

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Experience

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_039
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayedShieldThenDealTwo
#// LAW_039 Latts Razzi (2/1) — When Played: choose the Shield token (power stays 2), then she deals 2
#// (her power) to an enemy ground unit. Shield does NOT raise power, so the damage is 2, not 3.

## GIVEN
CommonSetup: bgw/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_039

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_039
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenPlayedNoEnemyGroundUnitNoDamage
#// LAW_039 — she still gains the chosen token, but with no enemy ground unit there is nothing to damage;
#// the enemy space unit is untouched and Latts ends exhausted in the ground arena.

## GIVEN
CommonSetup: bgw/bgw/{myResources:3}
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_039

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Experience

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_039
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:DAMAGE:0

---

# WhenPlayedShield_DamagesEnemyGroundLeaderUnit
#// "An enemy ground unit" includes a deployed enemy LEADER UNIT. Latts takes the Shield (power stays
#// 2), the damage pool offers both the marine and the deployed Cad Bane, and picking the leader unit
#// deals 2 to him.

## GIVEN
CommonSetup: bgw/rrk/{myResources:3; theirLeader:ASH_011:1:1:1}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_039

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:ASH_011
P2GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OfferPool_EnemyGroundOnlyIncludingDeployedLeader
#// LAW_039 Latts Razzi — offer assertion for "deals damage equal to her power to an ENEMY GROUND unit".
#// Both restriction words get a violator: a friendly ground unit (SOR_095) and Latts herself are out by
#// "enemy", while an enemy SPACE unit (SOR_225) and a friendly space unit (SOR_237) are out by "ground".
#// What remains is exactly the two enemy ground bodies — the Consular Security Force and the deployed Cad
#// Bane leader unit at theirGroundArena-1, which is a unit and therefore in. The existing sections each
#// answer a pick; only a pending pool can show that the friendly and space bodies were never offered.
#// COVERAGE: offer=OfferPool_EnemyGroundOnlyIncludingDeployedLeader (pending SELECTABLEEXACT: friendly and
#//           space bodies excluded, deployed enemy leader unit included) · decline=N/A (both steps are
#//           mandatory — "give a Shield token or an Experience token" is a forced OPTIONCHOOSE between two
#//           tokens and the damage has no "may") · boundary pair=WhenPlayedTokenThenDealPower (Experience
#//           -> power 3 -> 3 damage) vs WhenPlayedShieldThenDealTwo (Shield -> power 2 -> 2 damage) ·
#//           control=N/A (one-shot damage plus a token that rides the chosen unit; no seat-bound marker) ·
#//           reqboundary=not encoded (the token choice and the damage target are separate requests in
#//           production; no serialize round-trip section exists yet)

## GIVEN
CommonSetup: bgw/rrk/{myResources:3; theirLeader:ASH_011:1:1:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_039

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_039
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
