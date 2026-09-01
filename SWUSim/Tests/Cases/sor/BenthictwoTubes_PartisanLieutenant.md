# GrantsRaid2_DealsBonus
#// SOR_156 Benthic "Two Tubes" (Aggression unit, cost 1, 2/2, Rebel/Trooper) — "On Attack: Another
#// friendly [Aggression] unit gains Raid 2 for this phase." Benthic (idx1) attacks the base; its single
#// eligible recipient SOR_164 (Aggression, 4/5, idx0) auto-receives Raid 2. SOR_164 then attacks the
#// base and deals 4+2 = 6. Base total = 2 (Benthic) + 6 (SOR_164) = 8, and SOR_164 has the Raid keyword.
#// COVERAGE: offer=Offer_AnotherFriendlyAggressionUnitOnly (pending SELECTABLEEXACT with two
#//           eligible recipients; proves all three exclusions at once — self, non-Aggression, enemy) +
#//           OnlyEnemyAggressionPresent_Fizzle · boundary=Raid2_AppliesWhileAttackingOnly_NotWhile
#//           Defending (+2 attacking / +0 defending) + Raid2_ExpiresNextPhase (this phase / next) ·
#//           control=UnderEnemyControl_GrantsToTheControllersUnits · reqboundary=SimulateRequest
#//           Boundary_Raid2GrantSurvives · decline=N/A — the printed clause has no "you may" and the
#//           recipient is not chosen from a hidden zone, so the grant is a mandatory MZCHOOSE; with no
#//           eligible recipient it fizzles silently instead of prompting (the two Fizzle sections).

## GIVEN
CommonSetup: rrw/rrk/{}
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_156:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P2>Pass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:8
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid

---

# NoAggressionTarget_Fizzle
#// SOR_156 Benthic "Two Tubes" — "Another friendly [Aggression] unit". With only a non-Aggression
#// friendly unit (SOR_095, Heroism) present, Benthic's On Attack has no eligible recipient and fizzles:
#// no decision is offered and the bystander gains no Raid. (Self is excluded — Benthic can't pick itself.)

## GIVEN
CommonSetup: rrw/rrk/{}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_156:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P2BASEDMG:2

---

# Raid2_ExpiresNextPhase
#// SOR_156 Benthic "Two Tubes" — the granted Raid 2 is "for this phase". After Benthic attacks (granting
#// SOR_164 Raid 2), both players pass to reach the regroup phase, where the centralized turn-effect
#// expiry strips the grant. SOR_164 no longer has Raid.

## GIVEN
CommonSetup: rrw/rrk/{}
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_156:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# SimulateRequestBoundary_Raid2GrantSurvives
#// SOR_156 Benthic "Two Tubes" — the granted Raid 2 is written during Benthic's attack request and read
#// during a LATER attack request, so in production the grant must live in the serialized gamestate.
#// Mirrors GrantsRaid2_DealsBonus with a boundary between the two attacks: SOR_164 still deals 4+2 = 6,
#// base total 8, and it still carries the Raid keyword after the round-trip.

## GIVEN
CommonSetup: rrw/rrk/{}
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_156:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P2>Pass
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:8
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid

---

# Offer_AnotherFriendlyAggressionUnitOnly
#// SOR_156 Benthic "Two Tubes" — OFFER axis. "ANOTHER FRIENDLY [Aggression] unit" carries three
#// independent exclusions, and the pool proves all three at once. On the board: two eligible friendly
#// Aggression units (SOR_164 Wampa at idx 0, SOR_128 at idx 1), a friendly NON-Aggression unit
#// (SOR_095 at idx 2), Benthic himself (idx 3, excluded by "another") and an ENEMY Aggression unit
#// (LAW_180, excluded by "friendly"). Two eligible recipients keep the choice genuinely pending, so
#// the offer is the assertion rather than the outcome.

## GIVEN
CommonSetup: rrw/rrk/{}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_128:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_156:1:0
WithP2GroundArena: LAW_180:1:0

## WHEN
- P1>AttackGroundArena:3:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# OnlyEnemyAggressionPresent_Fizzle
#// SOR_156 Benthic "Two Tubes" — the "friendly" scope exclusion, isolated. Benthic is P1's only unit
#// and the only other Aggression body on the board belongs to the OPPONENT. The On Attack has no
#// eligible recipient: no decision is raised and the enemy Aggression unit gains nothing.

## GIVEN
CommonSetup: rrw/rrk/{}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_156:1:0
WithP2GroundArena: LAW_180:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P2BASEDMG:2

---

# Raid2_AppliesWhileAttackingOnly_NotWhileDefending
#// SOR_156 Benthic "Two Tubes" — the granted keyword is Raid 2, i.e. "+2/+0 WHILE ATTACKING". Benthic
#// attacks the base and its single eligible recipient SOR_164 (4/5) auto-receives Raid 2. On the
#// opponent's action LAW_124 (4/7) attacks SOR_164, which is now the DEFENDER: it deals only its
#// printed 4 back, not 6. LAW_124 ends at 4 damage — the boundary that separates a Raid grant from a
#// flat +2/+0 buff.

## GIVEN
CommonSetup: rrw/rrk/{}
SkipPreGame: true
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_156:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P2BASEDMG:2

---

# UnderEnemyControl_GrantsToTheControllersUnits
#// SOR_156 Benthic "Two Tubes" — CONTROL CHANGE. Benthic sits in P2's arena but is OWNED by P1.
#// "Another FRIENDLY Aggression unit" is read from the CONTROLLER's seat, so P2's Wampa receives the
#// Raid 2 and P1's Aggression unit — the owner's side — is not even a candidate (the grant
#// auto-resolves onto the single eligible body, which is the proof that P1's was excluded).
#// Controlled units seat after the plain ones, so P2's arena is [Wampa, Benthic].

## GIVEN
CommonSetup: rrw/rrw/{}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArena: SOR_164:1:0
WithP2GroundArenaControlled: SOR_156:1
WithP1GroundArena: SOR_128:1:0

## WHEN
- P2>AttackGroundArena:1:BASE

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Raid
P2GROUNDARENAUNIT:1:CARDID:SOR_156
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1BASEDMG:2
