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
#//           (decline, both halves: WhenPlayed_Decline_NoShieldGiven and
#//           OnAttack_Decline_NoShieldGiven) ·
#//           control=ControlTaken_NewControllerResolvesOnAttack — the who-RESOLVES-it reading is
#//           live here even though the owner-vs-controller reading is not (a one-shot token grant
#//           leaves nothing behind to follow the unit to a new zone): a Ghost owned by P1 but
#//           controlled by P2 offers the shield to P2, and P2's pick is what lands ·
#//           boundary=WhenPlayed_ShieldsAnotherSpectre (Shielded self-token + the ability token
#//           resolve on the same play without cross-eating) vs
#//           WhenPlayed_TriggerOrder_AbilityBeforeShielded (the OTHER trigger order, same board),
#//           and WhenPlayed_NoOtherSpectre_NoOffer / OnAttack_NoOtherSpectre_NoOffer as the
#//           zero-legal-recipient half of the offer pair · reqboundary=N/A (resolves inside
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

---

# WhenPlayed_NoOtherSpectre_NoOffer
#// SOR_050 The Ghost — Intended no-valid-target branch on the When Played half. "ANOTHER Spectre
#// unit" excludes The Ghost itself, so with only a non-Spectre Battlefield Marine on the board
#// there is no legal recipient and no shield offer is raised at all. The two triggers still queue
#// together, so the trigger-order pick comes first; after it, Shielded has given The Ghost its own
#// token and nothing else is pending.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6;handCardIds:SOR_050}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1NODECISION
P1SPACEARENAUNIT:0:CARDID:SOR_050
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# OnAttack_NoOtherSpectre_NoOffer
#// SOR_050 The Ghost — the same no-valid-target branch on the ON ATTACK half. The Ghost is already
#// in the arena (so Shielded, a WHEN-PLAYED keyword, never fired and she carries no token) and is
#// the only Spectre in play. She attacks P2's base for her full 5 and no shield offer appears —
#// with a single trigger there is not even a trigger-order pick.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_050:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:5
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# OnAttack_Decline_NoShieldGiven
#// SOR_050 The Ghost — the decline branch on the ON ATTACK half (the existing decline section
#// covers only the When Played half). Chopper is a legal recipient and the "may" still prompts with
#// one candidate, but P1 declines: no token is created anywhere and the attack itself is unaffected
#// — P2's base still takes 5.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_050:1:0
WithP1GroundArena: SOR_188:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P2BASEDMG:5

---

# OnAttack_ShieldsEnemySpectre
#// SOR_050 The Ghost — Intended: "another SPECTRE unit" carries no "friendly" qualifier, so an
#// ENEMY Spectre is a legal recipient and the shield really can be handed across the table. The
#// offer section proves the enemy Spectre is in the pool; this one RESOLVES onto it — P2's Kanan
#// Jarrus ends the attack holding a Shield token while P1's own non-Spectre Marine has none.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_050:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_047:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_047
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P2BASEDMG:5

---

# OnAttack_GhostNeverShieldsHerself
#// SOR_050 The Ghost — the negative that proves BOTH gates on the attack path are load-bearing:
#// (a) "ANOTHER Spectre unit" excludes The Ghost, and (b) Shielded reads "when you PLAY this unit",
#// so an attack does not re-trigger it. She is seeded in the arena carrying no token, attacks, and
#// hands the one Shield to Chopper — ending the attack still on ZERO tokens of her own.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_050:1:0
WithP1GroundArena: SOR_188:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_188
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2BASEDMG:5

---

# WhenPlayed_TriggerOrder_AbilityBeforeShielded
#// SOR_050 The Ghost — Intended: Shielded and the When Played are two triggers in the same window,
#// so the controller orders them (CR: simultaneous triggered abilities are ordered by their
#// controller). The sibling section takes EffectStack-0 first; this one takes the OTHER order and
#// must land on the identical board — The Ghost holding her own Shielded token and Chopper holding
#// the granted one. Two token grants in one window must not eat each other whichever way round they
#// resolve.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6;handCardIds:SOR_050}
P1OnlyActions: true
WithP1GroundArena: SOR_188:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_050
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1NODECISION

---

# GrantedShieldAbsorbsEnemyDamage
#// SOR_050 The Ghost — Intended: what the clause hands over is a real Shield token, not a marker.
#// Chopper (1/3) takes the shield off The Ghost's attack, then P2's Battlefield Marine (3/3)
#// attacks him: the token absorbs the whole 3 (a Shield prevents the damage and is then defeated),
#// so Chopper survives on ZERO damage with no token left, and the Marine still eats his 1 power
#// back. Without the token the 3 would have killed him outright.

## GIVEN
CommonSetup: ggw/ggw
WithP1SpaceArena: SOR_050:1:0
WithP1GroundArena: SOR_188:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_188
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# ControlTaken_NewControllerResolvesOnAttack
#// SOR_050 The Ghost — the control axis's second reading: WHO resolves it. The Ghost is OWNED by
#// P1 but sits under P2's control (the end state after a take-control effect), so it is P2 who
#// attacks with her and P2 — not her owner — who is offered the shield and picks the recipient.
#// P2 hands it to their own Kanan Jarrus; P1's Chopper (equally legal, "another Spectre unit" is
#// controller-blind) gets nothing, and the attack damages P1's base, not P2's.

## GIVEN
CommonSetup: ggw/ggw
WithInitiativePlayer: 2
WithActivePlayer: 2
WithP1GroundArena: SOR_188:1:0
WithP2GroundArena: SOR_047:1:0
WithP2SpaceArenaControlled: SOR_050:1

## WHEN
- P2>AttackSpaceArena:0:BASE
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_050
P2GROUNDARENAUNIT:0:CARDID:SOR_047
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1BASEDMG:5
P2BASEDMG:0
