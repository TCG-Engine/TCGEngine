# DeployedOnAttackKeywords
#// TS26_03 Maul (leader deployed, 5/9) — On Attack: same "more keywords than Experience → +1 Experience &
#// 1 damage" effect. Deployed Maul attacks LAW_124; its On Attack targets the friendly SOR_063 (Sentinel),
#// giving it 1 Experience (2 → 3 power) and 1 damage.
## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# FrontKeywordsExpAndDamage
#// TS26_03 Maul (leader front) — Action [Exhaust]: choose a unit; if it has more different keywords than
#// Experience tokens on it, give it an Experience token and deal 1 damage. SOR_063 (Sentinel, 1 keyword, 0
#// Experience) qualifies → gains 1 Experience (2 → 3 power) and takes 1 damage.
## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# Front_OfferIsEveryUnitOnTheBoard_IncludingTheEnemyDeployedLeader
#// TS26_03 Maul (front) — "Choose a unit" is completely unqualified: friendly and enemy, ground and
#// space, and an enemy DEPLOYED LEADER (which is a unit) are all selectable. Asserted as the offer with
#// the decision left pending, since answering resolves it.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03;theirLeader:SOR_010;theirLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: IBH_076:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# Front_TwoKeywordsOneExperience_Fires
#// TS26_03 Maul (front) — the comparison is DIFFERENT KEYWORDS vs EXPERIENCE TOKENS, not "has any
#// keyword". JTL_118 MC30 Assault Frigate has 2 keywords (Overwhelm, Raid) and 1 Experience, so 2 > 1
#// fires: it gains a second Experience (upgrade count 1 -> 2) and takes 1 damage.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_118:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T01

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1LEADER:EXHAUSTED

---

# Front_NoKeywordsAtAll_NoEffect
#// TS26_03 Maul (front) — a vanilla unit (IBH_076 Rampaging Wampa, 0 keywords, 0 Experience) is a legal
#// TARGET but 0 > 0 is false, so nothing happens. Maul still exhausts: the cost is paid either way.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IBH_076:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:EXHAUSTED

---

# Front_EqualKeywordsAndExperience_NoEffect
#// TS26_03 Maul (front) — the gate is strictly MORE, not "at least". SOR_063 has 1 keyword (Sentinel)
#// and 1 Experience: 1 > 1 is false, so it keeps exactly its one Experience and takes no damage.
#// This is the boundary partner of Front_TwoKeywordsOneExperience_Fires.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Front_FewerKeywordsThanExperience_NoEffect
#// TS26_03 Maul (front) — the far side of the boundary: SOR_063 with 1 keyword and TWO Experience
#// tokens. 1 > 2 is false, nothing happens, and both Experience tokens stay.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# Front_StackingNumericKeywordCountsOnce
#// TS26_03 Maul (front) — "DIFFERENT keywords" counts DISTINCT keyword NAMES, so a numeric keyword the
#// unit both prints and is granted still counts ONCE. JTL_118 MC30 prints Overwhelm + Raid 1 and gains
#// another Raid 1 from SEC_140 Hondo Ohnaka ("each other friendly unit gains Raid 1") — that is still
#// 2 different keywords, not 3. With 2 Experience tokens, 2 > 2 is false and nothing happens.
#// A double-counting bug would read 3 > 2 and fire, so this is the discriminating case.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_118:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T01
WithP1SpaceArenaUpgrade: 0:SOR_T01
WithP1GroundArena: SEC_140:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:2

---

# Front_KeywordGrantedByAnUpgradeCounts
#// TS26_03 Maul (front) — keywords GAINED from an upgrade count toward the comparison, not just printed
#// ones. IBH_076 Rampaging Wampa prints no keywords, but SOR_166 Infiltrator's Skill grants Saboteur:
#// 1 > 0 fires, so it takes 1 damage and ends with the skill + a new Experience (upgrade count 2).

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IBH_076:1:0
WithP1GroundArenaUpgrade: 0:SOR_166

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# Front_NoUnitsInPlay_JustExhausts
#// TS26_03 Maul (front) — with an empty board there is no unit to choose. The action is still usable and
#// still costs the exhaust; it simply resolves with no effect and leaves no dangling decision.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1NODECISION

---

# Deployed_WhenDeployedWindowFires
#// TS26_03 Maul — his deployed side reads "When Deployed/On Attack:", i.e. TWO windows. Deploying him
#// with a friendly SOR_063 (1 keyword, 0 Experience) on board must fire the effect immediately: the
#// Wing Guard gains an Experience and takes 1 damage.
#// BUG THIS PINS: the When Deployed half was a silent in-game no-op. The handler was registered
#// correctly, but the generated ability stub listed TS26_03 only under On Attack — the detector matched
#// "When Deployed:" (colon) and "When Played/" (slash) but NOT "When Deployed/" (slash), which is how
#// this card is worded. The trigger therefore dispatched to nothing while the On Attack half worked,
#// which is exactly what made it look healthy. Fixed in zzCardCodeGenerator.php.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03;myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Deployed_MaulHimselfIsTheOnlyTarget_HeHasNoKeywords_NoEffect
#// TS26_03 Maul — deployed onto an empty board, the only unit he can choose is HIMSELF, and his deployed
#// side prints no keywords: 0 > 0 is false, so he takes no damage and gains no Experience. Confirms the
#// self-target is legal (not excluded) while still failing the comparison.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03;myResources:8}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Deployed_EqualKeywordsAndExperience_NoEffect
#// TS26_03 Maul — the deployed On Attack window runs the same "MORE keywords than Experience" comparison
#// as the front side, and it is strict. SOR_063 Wing Guard has 1 keyword (Sentinel) and 1 Experience:
#// 1 > 1 is false, so it takes no damage and keeps just that one upgrade.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Deployed_FewerKeywordsThanExperience_NoEffect
#// TS26_03 Maul — the deployed side below the boundary: 1 keyword against 2 Experience tokens. Nothing
#// happens, and no third upgrade is added.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# Deployed_StackingNumericKeywordCountsOnce
#// TS26_03 Maul — the deployed window counts DISTINCT keyword names too. JTL_118 MC30 prints Overwhelm +
#// Raid 1 and is granted a second Raid 1 by SEC_140 Hondo Ohnaka: still 2 different keywords, not 3.
#// Against 2 Experience tokens, 2 > 2 is false and nothing happens — a double-count would read 3 > 2.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_118:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T01
WithP1SpaceArenaUpgrade: 0:SOR_T01
WithP1GroundArena: SEC_140:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:2

---

# Deployed_KeywordGrantedByAnUpgradeCounts
#// TS26_03 Maul — the deployed window also counts GAINED keywords. IBH_076 Rampaging Wampa prints none,
#// but SOR_166 Infiltrator's Skill grants Saboteur: 1 > 0 fires, so it takes 1 damage and ends with the
#// skill plus a new Experience.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IBH_076:1:0
WithP1GroundArenaUpgrade: 0:SOR_166
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# Deployed_NoUnitQualifies_TheAttackStillResolves
#// TS26_03 Maul — when no unit on the board meets the condition the On Attack half does nothing, but the
#// attack itself is unaffected: the keyword-less SEC_080 is untouched while LAW_124 takes Maul's 5.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_03:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:5
