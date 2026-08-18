# OnAttackGrantRaid
#// LAW_182 Weazel (2/3) — On Attack: another friendly unit gains Raid 2 for this phase. Attacks the
#// base; grant Raid 2 to SOR_095.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_182:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid

---

# OnAttackGrantRaid_SurvivesTheRequestBoundary
#// LAW_182 Weazel — request-boundary guard. The On Attack grant is queued mid-attack and its "for this phase"
#// Raid 2 is applied only after the target answer, which in production arrives in a fresh process. Same flow
#// as OnAttackGrantRaid but with a THIRD friendly unit so the "another friendly unit" choose is genuinely
#// pending (MZMAYCHOOSE over myGroundArena-1 & myGroundArena-2) rather than auto-resolving, and a serialize
#// round-trip inserted before the answer. SOR_095 still ends up with Raid.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_182:1:0 SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid

---

# OnAttackOffer_AnotherFriendlyUnitAnyArena
#// LAW_182 Weazel — OFFER assertion for "ANOTHER FRIENDLY unit gains Raid 2 for this phase." Discriminating
#// board: Weazel itself (myGroundArena-0, the attacker) is OUT on "another"; the friendly SOR_095 and P1's
#// DEPLOYED leader at ground idx 2 are IN (a deployed leader is a friendly unit); the friendly SPACE X-Wing
#// is IN (the grant has no arena restriction); the enemy SOR_046 is OUT on controller scope. Pool must be
#// exactly the three other friendly units.
#// COVERAGE: offer=this section (self-exclusion + friendly scope + cross-arena + deployed leader) ·
#//           reqboundary=OnAttackGrantRaid_SurvivesTheRequestBoundary · decline=N/A (MZMAYCHOOSE decline is
#//           generic prompt behavior; declining just skips the grant) · control=N/A (the grant is applied
#//           to the chooser's own unit for the phase) · boundary pair=OnAttackGrantRaid (single candidate
#//           auto-target) vs this section (three candidates, pool pinned)

## GIVEN
CommonSetup: rrw/bgw/{myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: [LAW_182:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-2&mySpaceArena-0
P1GROUNDARENAUNIT:0:CARDID:LAW_182
P1GROUNDARENAUNIT:2:ISLEADERUNIT

---

# TheGrantedRaidActuallyADDSPowerOnTheNextAttack
#// LAW_182 Weazel — the existing sections assert the Raid KEYWORD is present, which a cosmetic keyword
#// stamp would also satisfy. This one spends it: Weazel attacks the base, grants Raid 2 to the friendly
#// SOR_095 Battlefield Marine, and the Marine then attacks the base itself for 3 printed + 2 Raid = 5.
#// P2's base ends on 2 (Weazel) + 5 = 7.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_182:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
P2BASEDMG:7
