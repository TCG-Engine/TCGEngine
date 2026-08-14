# OnAttack_ShieldsAnotherSpectre
#// SOR_050 The Ghost (5/5, Space) — When Played/On Attack: You may give a Shield token to
#// another SPECTRE unit. Tested via On Attack: The Ghost attacks the enemy base; the trigger
#// offers a shield to another Spectre unit. The only other Spectre is Chopper (SOR_188) →
#// auto-resolves and he gains a Shield. Battlefield Marine (non-Spectre) is NOT a valid
#// target and stays unshielded — guards the Spectre trait filter.
#// COVERAGE: offer=OnAttack_Offer_IncludesEnemySpectre_ExcludesNonSpectreAndSelf (pending
#//           SELECTABLEEXACT: "another SPECTRE unit" spans BOTH sides, excludes non-Spectres and
#//           The Ghost itself) · decline=OnAttack_ShieldsAnotherSpectre's sibling file sections
#//           use the may-pick; the decline branch is WhenPlayed_Decline_NoShieldGiven ·
#//           control=N/A (one-shot token grant; nothing follows the unit) ·
#//           boundary=WhenPlayed_ShieldsAnotherSpectre (Shielded self-token + the ability token
#//           resolve on the same play without cross-eating) · reqboundary=N/A (resolves inside
#//           the play/attack ceremony)

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_050:1:0     # The Ghost (ready) — attacker, idx 0
WithP1GroundArena: SOR_188:1:0    # Chopper (Spectre) — idx 0, the only other Spectre
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (non-Spectre) — idx 1, must be ignored

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2BASEDMG:5

---

# WhenPlayed_ShieldsAnotherSpectre
#// SOR_050 The Ghost — Intended: the WHEN PLAYED half of "When Played/On Attack: You may give a
#// Shield token to another SPECTRE unit". P1 plays The Ghost (cost 6, Vigilance/Heroism —
#// on-aspect via a Vigilance/Heroism leader): Shielded gives The Ghost its own Shield, and the
#// When Played offers a Shield to another Spectre — Chopper (SOR_188), the sole other Spectre,
#// still prompts (a "may") and is picked. The non-Spectre Marine stays unshielded.
#// Shielded and the When Played queue together → the trigger-order pick comes first.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6;handCardIds:SOR_050}
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_050
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1NODECISION

---

# WhenPlayed_Decline_NoShieldGiven
#// SOR_050 The Ghost — Intended: the shield-a-Spectre is a "you may": declining it leaves the
#// other Spectre unshielded, while The Ghost still keeps its own Shielded token.
#// Shielded and the When Played queue together → the trigger-order pick comes first.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6;handCardIds:SOR_050}
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# OnAttack_Offer_IncludesEnemySpectre_ExcludesNonSpectreAndSelf
#// SOR_050 The Ghost — Intended: "another SPECTRE unit" has no "friendly" qualifier — the pool
#// spans BOTH sides. The Ghost attacks the base; the shield offer is left PENDING: it must hold
#// exactly P1's Sabine Wren (SOR_142, Spectre) and P2's Kanan Jarrus (SOR_047, Spectre) — not
#// the non-Spectre Marine, and not the attacking Ghost itself.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_050:1:0
WithP1GroundArena: SOR_142:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_047:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
