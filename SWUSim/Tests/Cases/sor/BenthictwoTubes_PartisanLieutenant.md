# GrantsRaid2_DealsBonus
#// SOR_156 Benthic "Two Tubes" (Aggression unit, cost 1, 2/2, Rebel/Trooper) — "On Attack: Another
#// friendly [Aggression] unit gains Raid 2 for this phase." Benthic (idx1) attacks the base; its single
#// eligible recipient SOR_164 (Aggression, 4/5, idx0) auto-receives Raid 2. SOR_164 then attacks the
#// base and deals 4+2 = 6. Base total = 2 (Benthic) + 6 (SOR_164) = 8, and SOR_164 has the Raid keyword.

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
