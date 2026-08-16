# TokenDefeatedAtRegroup
#// SHD_225 Jetpack — "At the start of the regroup phase, defeat that token." After the regroup the
#// shield is gone; the Jetpack itself stays attached (+2/+0 persists).
#// COVERAGE: offer=AttachOffer_NonVehicleUnitsOnEitherSide (host pool left PENDING and read with
#//   P1SELECTABLEEXACT — Vehicles out, enemy non-Vehicles in) · control=EnemyHost_ShieldGrantedAndStill
#//   DefeatedAtRegroup (P1's Jetpack on a unit P2 controls; buff, granted token and the regroup defeat all
#//   land on the enemy host) · reqboundary=GrantedTokenMovedAway_ThenSpent_RegroupFindsNothingToDefeat
#//   (the host is picked at one decision, the token relocated at two more, damaged at a fourth and only
#//   then read at regroup) + Smuggle_WhenPlayedStillGivesTheShield (payment step → attach step) ·
#//   boundary=this section vs Regroup_DefeatsOnlyTheGrantedToken_HostsOwnShieldSurvives is the
#//   "defeat THAT token" pair (bare host ends at 0 Shields / host with its own Shield ends at 1), and
#//   TwoJetpacks_ShieldsStackOnOneHost vs TwoJetpacks_BothGrantedTokensDefeatedAtRegroup is the
#//   copy-count pair either side of the regroup step (3 Shields → 1) · decline=N/A — neither clause is a
#//   "you may": the When Played grant is mandatory and the regroup defeat is not optional; the nearest
#//   refusal-shaped branch is a granted token that no longer exists by regroup, which is
#//   GrantedTokenMovedAway_ThenSpent_RegroupFindsNothingToDefeat closing with nothing to do.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_225
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5

---

# WhenPlayed_Shield
#// SHD_225 Jetpack (+2/+0, non-Vehicle) — "When Played: Give a Shield token to attached unit."
#// Attached to the marine (single host → auto): 5/3 with 1 shield + the Jetpack (UPGRADECOUNT 2).

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_225
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5

---

# AttachOffer_NonVehicleUnitsOnEitherSide
#// THE OFFER AXIS for "Attach to a non-Vehicle unit." The restriction names no controller, so the legal
#// host pool is every non-Vehicle unit in play on EITHER side; Vehicles are out whoever controls them.
#// Board: P1's SOR_205 Jawa Scavenger (legal), P1's SOR_244 Snowspeeder (Vehicle → out) and P2's
#// SOR_164 Wampa (enemy, non-Vehicle → in). Two legal hosts stop the pick auto-resolving, so the offer
#// can be read while still PENDING — the decision is deliberately left unanswered.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_225
WithP1GroundArena: [SOR_205:1:0 SOR_244:1:0]
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Smuggle_WhenPlayedStillGivesTheShield
#// THE DISPATCH-PATH TWIN of WhenPlayed_Shield. Smuggle [4 resources, Cunning] is a second way onto the
#// board and the "When Played: Give a Shield token to attached unit" clause must fire on it too.
#// The smuggled card is itself a ready resource and exhausts toward its own cost (CR 8.22.e), so only 3
#// of the other 4 are spent and 1 stays ready; the vacated slot is replaced from the top of the deck, so
#// the resource COUNT is still 5.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 4:SOR_046:1,1:SHD_225:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:4

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_225
P1GROUNDARENAUNIT:0:POWER:5
P1RESCOUNT:5
P1RESAVAILABLE:1
P1DECKCOUNT:0
P1NODECISION

---

# Regroup_DefeatsOnlyTheGrantedToken_HostsOwnShieldSurvives
#// SHIELD IDENTITY. "At the start of the regroup phase, defeat THAT token" defeats ONE specific Shield —
#// the one this Jetpack granted — not every Shield the host happens to be wearing. SOR_205 already wears
#// a Shield of its own, so after the Jetpack lands it carries two identical-looking tokens (SHIELDCOUNT 2,
#// UPGRADECOUNT 3). Crossing into regroup must take exactly one of them and leave the unit's own Shield
#// standing. TokenDefeatedAtRegroup is the bare-host twin (0 shields after regroup because there was only
#// ever the granted one); this section is what separates "defeat that token" from "clear the host".
#// The Jetpack itself is unaffected either way — +2/+0 persists, so the 2/1 Jawa stays 4/1.
#// Both decks are seeded past the regroup draw; drawing from an empty deck deals 3 to that base.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_225
WithP1GroundArena: SOR_205:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_205
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:4
P1BASEDMG:0

---

# TwoJetpacks_ShieldsStackOnOneHost
#// Jetpack is non-unique, so a unit can wear two — and each copy grants its OWN Shield rather than the
#// second one being absorbed into the first. On a Jawa that already had a Shield the host ends up with
#// three Shields and five upgrades in total, at 2+2+2 = 6 power. A "does the host have a Shield?" style
#// implementation would cap this at two tokens, and only counting them can tell the difference.
#// No pass here — this is the pre-regroup snapshot; the regroup half is the next section.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4}
P1OnlyActions: true
WithP1Hand: [SHD_225 SHD_225]
WithP1GroundArena: SOR_205:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:5
P1GROUNDARENAUNIT:0:POWER:6
P1RESAVAILABLE:0

---

# TwoJetpacks_BothGrantedTokensDefeatedAtRegroup
#// The regroup half of the section above, and the count boundary that matters: TWO copies arm TWO
#// separate "defeat that token" instructions, so regroup takes two of the three Shields and stops. The
#// host's own Shield survives and both Jetpacks stay attached (UPGRADECOUNT 3 = 2 Jetpacks + 1 Shield,
#// still 6 power). One flag shared per host would leave two Shields; an unbounded sweep would leave none.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4}
P1OnlyActions: true
WithP1Hand: [SHD_225 SHD_225]
WithP1GroundArena: SOR_205:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1GROUNDARENAUNIT:0:POWER:6
P1BASEDMG:0

---

# GrantedTokenMovedAway_ThenSpent_RegroupFindsNothingToDefeat
#// The token can leave its original host: SHD_064 Survivors' Gauntlet moves an upgrade — a Shield token
#// included — to another unit the same player controls. Jetpack goes on SOR_095 (idx1, 1 Shield, no
#// others), the Gauntlet's On Attack relocates that Shield onto the Jawa (idx0, which already had one, so
#// it now has two), and SHD_178 Daring Raid then spends one of the Jawa's Shields. By regroup the granted
#// token is already gone, so the regroup clause has nothing left to take: the Jawa keeps a Shield and
#// takes no damage, and SOR_095 ends wearing only the Jetpack.
#// SHD_064 is a Vehicle, so it is never in the Jetpack host pool — the host pick is between the two
#// non-Vehicles and is answered explicitly. Costs: Jetpack 2 + Daring Raid 1+2 off-aspect = 5.
#// `.u1` addresses SOR_095's second subcard (Jetpack attached first, then its Shield).

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
P1OnlyActions: true
WithP1Hand: [SHD_225 SHD_178]
WithP1GroundArena: [SOR_205:1:0 SOR_095:1:0 SHD_064:1:0]
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:2:BASE
- P1>AnswerDecision:myGroundArena-1.u1
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_205
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SHD_225

---

# EnemyHost_ShieldGrantedAndStillDefeatedAtRegroup
#// THE CONTROL AXIS. "Attach to a non-Vehicle unit" names no controller, so P1 may hang the Jetpack on a
#// unit P2 controls (CR 2.e) — and both halves of the card then operate on that enemy host: the +2/+0
#// and the granted Shield land on P2's SOR_095 (3/3 → 5/3), and the regroup clause still reaches across
#// to defeat the token it made even though the flag was armed by P1. P2's own pre-existing Shield is the
#// control they keep: it must survive, or the regroup step is clearing the host rather than its own token.
#// P1's only unit is a Snowspeeder (Vehicle), leaving the enemy marine as the single legal host, so the
#// attach auto-resolves onto it — P1's Vehicle staying bare is the proof it was never in the pool.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_225
WithP1GroundArena: SOR_244:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# AbilityDamage_JetpacksShieldIsTheOneSpent_OriginalSurvivesRegroup
#// SHD_225 — "give a Shield token... at the start of the regroup phase, defeat THAT token" means the
#// granted token has an IDENTITY. The Jawa already carries its own Shield; Jetpack adds a second. Ability
#// damage (SHD_178 Daring Raid) must spend the JETPACK token, not the older one — so at regroup there is
#// nothing left for Jetpack to defeat and the Jawa keeps its ORIGINAL shield. Ends with 1 shield and the
#// Jetpack upgrade itself still attached (2 upgrades: Jetpack + the surviving shield).

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
P1OnlyActions: true
WithP1Hand: [SHD_225 SHD_178]
WithP1GroundArena: SOR_205:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_205
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# CombatDamage_JetpacksShieldIsTheOneSpent_OriginalSurvivesRegroup
#// SHD_225 — the same identity rule on the COMBAT dispatch path (the ability-damage twin is
#// AbilityDamage_JetpacksShieldIsTheOneSpent_OriginalSurvivesRegroup). The Jawa already has its own
#// Shield and Jetpack adds a second; it attacks a 3/1 Death Star Stormtrooper and takes 3 back, which a
#// Shield absorbs entirely (CR 8.31). The token spent must be the JETPACK one, so at regroup there is
#// nothing left for Jetpack to defeat and the Jawa keeps its ORIGINAL shield.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_225
WithP1GroundArena: SOR_205:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_205
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# GrantedTokenMovedToAnotherHost_IsDefeatedThereAtRegroup
#// SHD_225 — "defeat THAT token" follows the token, not the host. Jetpack goes on SOR_095, then SHD_064
#// Survivors' Gauntlet relocates the granted Shield onto the Jawa (which already had one of its own, so
#// it briefly holds two). No damage is dealt, so the granted token is still in play at regroup — and it
#// must be defeated ON ITS NEW HOST. The Jawa therefore ends with exactly ONE shield (its own), not two,
#// and SOR_095 keeps the Jetpack upgrade with no shield. Sibling of
#// GrantedTokenMovedAway_ThenSpent_RegroupFindsNothingToDefeat, which spends the token before regroup.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_225
WithP1GroundArena: [SOR_205:1:0 SOR_095:1:0 SHD_064:1:0]
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:2:BASE
- P1>AnswerDecision:myGroundArena-1.u1
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_205
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SHD_225
