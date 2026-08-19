# TransplantAbilities
#// ASH_230 Improvised Identity (Upgrade) — grants the host: "Action: search the top 3 for a ground unit and
#// discard it; then you may attack with this unit, gaining the discarded unit's abilities for this attack."
#// COVERAGE: offer=GrantSaboteur_BypassesTheEnemySentinel (+ its auto-resolve CONTROL) +
#//                 AttachGroundOnly_SpaceNotSelectable ·
#//           decline=DeclineSearch_TakeNothing + DiscardThenDeclineAttack ·
#//           boundary=NoGroundInTop3_NothingDiscarded + OncePerRound_NoSecondUse +
#//                 GainedAbilitiesExpire_TheHostIsPlainOnItsNEXTAttack (this attack vs the next) ·
#//           control=N/A (the upgrade attaches to a FRIENDLY ground unit and the granted action is used by
#//                 its controller; there is no owner-scoped zone and no take-control interaction) ·
#//           reqboundary=N/A (the grant lives on the combat's SUPPORT_GRANT carrier and is consumed inside
#//                 the same attack)
#// ⚠ SCOPE OF THE GRANT (measured 2026-08-18): keywords come from the printed-keyword arrays; triggered
#// and constant abilities ride the SUPPORT_GRANT carrier, the same one Support uses. Raid / Saboteur /
#// On Attack, the attack-MODE constants (Maul's attack-2, Retrofitted Airspeeder's cross-arena targeting,
#// The Stranger's deal-first) and the combat-hit triggers (Jango's draw, the AT-AT's excess routing) all
#// transplant. The old "needs a full ability-copy system" deferral was an OVERESTIMATE: the carrier
#// already existed and the gap was only which combat sites consulted it.
#// SOR_046 (wearing the upgrade) discards SOR_059 (On Attack: may heal 2 from another unit) and attacks P2's
#// base; the transplanted On Attack heals 2 from the damaged SOR_095 (2 → 0 damage).
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1GroundArena: SOR_095:1:2
WithP1Deck: [SOR_059 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_059
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# DeclineSearch_TakeNothing
#// ASH_230 Improvised Identity — the search may take nothing. P1 uses the action but declines to take a
#// ground unit; nothing is discarded and no attack follows.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_059 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P1DISCARDCOUNT:0
P1GROUNDARENAUNIT:0:READY

---

# DiscardThenDeclineAttack
#// ASH_230 Improvised Identity — the attack after discarding is optional ("you may attack"). P1 discards
#// SOR_059 but declines to attack, so the host stays ready and deals no damage.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1GroundArena: SOR_095:1:2
WithP1Deck: [SOR_059 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_059
- P1>AnswerDecision:NO
## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# AttachToGroundUnit
#// ASH_230 Improvised Identity lands as an upgrade on the chosen ground unit.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
WithP1Hand: [ASH_230]
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: SOR_178:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:ASH_230

---

# SearchDiscardsGroundFromMixedDeck
#// ASH_230 search — the top 3 hold a ground unit (SOR_095), a space unit (SOR_178) and an event (SOR_077);
#// only the ground unit can be discarded. Discarding it leaves 2 cards in the deck.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_095 SOR_178 SOR_077]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_095
- P1>AnswerDecision:NO
## EXPECT
P1DISCARDCOUNT:1
P1DECKCOUNT:2
P1GROUNDARENAUNIT:0:READY

---

# NoGroundInTop3_NothingDiscarded
#// ASH_230 search — when the top 3 contain no ground units (space unit SOR_178, upgrade SOR_069, event
#// SOR_077), nothing can be discarded. Taking nothing leaves the host ready and deals no damage.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_178 SOR_069 SOR_077]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
- P1>AnswerDecision:NO
## EXPECT
P1DISCARDCOUNT:0
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:0

---

# GrantOnAttackDealToBase
#// ASH_230 grants the discarded unit's On Attack ability for this attack. Wampa (SOR_164) discards
#// Cloud-Rider Veteran (LAW_181, "On Attack: Deal 2 damage to a base") and attacks P2's base: the gained
#// On Attack deals 2, plus Wampa's 4 combat damage = 6.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [LAW_181 SOR_178 SOR_077]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:LAW_181
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:6

---

# StillSearchesWhenExhausted
#// ASH_230's action has no exhaust cost, so an exhausted host can still search and discard. No attack is
#// offered because the host is exhausted.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_164:0:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_095 SOR_178 SOR_077]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_095
## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0

---

# OncePerRound_NoSecondUse
#// ASH_230's action is usable once each round. After using it once (take nothing, no attack), using it
#// again the same round is a no-op: no new search decision, nothing discarded, deck unchanged.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_095 SOR_178 SOR_077 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
- P1>AnswerDecision:NO
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1NODECISION
P1DISCARDCOUNT:0
P1DECKCOUNT:4

---

# AttachGroundOnly_SpaceNotSelectable
#// ASH_230 — attach condition is a GROUND unit; a friendly SPACE unit is not a legal host. With only a ground
#// (SOR_046) and a space (SOR_237) friendly present, ASH_230 auto-attaches to the ground unit (the sole legal
#// host) — the space unit is excluded (it gets no upgrade, and no cross-arena prompt appears).
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_230}
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# GrantRaid_ExtraBaseDamage
#// ASH_230 — the host gains the discarded unit's KEYWORDS for the attack. Discarding SOR_157 Cantina Braggart
#// (Raid 2) lets SOR_046 (3 power) deal 3 + 2 = 5 to P2's base.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_157 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_157
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:5

---

# AttackOfferedWhenNoGroundDiscarded
#// ASH_230 — "Then, you may attack" is unconditional: even with no ground unit in the top 3 (nothing discarded),
#// the host may still attack (with no ability grant). Deck top 3 are all space units.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_237 SOR_237 SOR_237]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3

---

# GrantSaboteur_BypassesTheEnemySentinel
#// ASH_230 — a granted KEYWORD changes what the attack may legally target, not just its numbers. SOR_239
#// Rebel Pathfinder has Saboteur, so the host gains it for this attack and may ignore the enemy Sentinel
#// (SOR_229 Cell Block Guard) and hit the base instead.
#// Asserted as the OFFER, left pending: the attack-target pool must contain the base. Answering instead
#// would prove the branch works but says nothing about what was legal.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_239 SOR_063 SOR_063]
WithP2GroundArena: SOR_229:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_239
- P1>AnswerDecision:YES

## EXPECT
P1DECISIONTOOLTIP:Choose_an_attack_target
P1SELECTABLEEXACT:theirGroundArena-0&theirBase-0

---

# CONTROL_NoSaboteurGranted_TheSentinelStillForcesTheAttack
#// ASH_230 — the control for the section above, identical but for the discarded unit. SOR_063 Cloud City
#// Wing Guard has no Saboteur, so nothing bypasses the enemy Sentinel and the base is NOT a legal target.
#// This pair is what proves the grant is doing the work: with Saboteur the pool has two entries, without
#// it exactly one.
#// ⚠ That single entry AUTO-RESOLVES, so there is no pending choice to inspect — the auto-resolution IS
#// the assertion here. The attack goes into the Sentinel (3 power kills the 3/3 Cell Block Guard) and the
#// base takes nothing; a base target was never offered.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_063 SOR_063 SOR_063]
WithP2GroundArena: SOR_229:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_063
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1NODECISION

---

# GainedAbilitiesExpire_TheHostIsPlainOnItsNEXTAttack
#// ASH_230 — "FOR THIS ATTACK, this unit gains the discarded unit's abilities." The grant is scoped to the
#// one attack, so it must be gone by the next one. Without this, an implementation that attached the
#// abilities permanently passes every other section in this file.
#// P1's host discards SOR_239 (Saboteur), attacks the base under its cover, and then — a phase later,
#// with the enemy Sentinel still up — attacks again with NO grant. The second attack has exactly one
#// legal target (the Sentinel), which auto-resolves into it: the base is no longer reachable.
#// The Improvised Identity action itself is once-per-round, so the second attack is a plain attack.
#// ⚠ BOTH decks are seeded: this section passes through a regroup, and an empty deck there adds 6 to that
#// player's base (3 per missed draw). Unseeded, P2's base reads 9 and the assertion looks like a grant
#// that never expired.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_239 SOR_063 SOR_063 SOR_063 SOR_063]
WithP2Deck: [SOR_063 SOR_063 SOR_063 SOR_063]
WithP2GroundArena: SOR_229:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_239
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:0
P1NODECISION

---

# GrantConstant_MaulsTwoDefenderAttack
#// ASH_230 — a CONSTANT ability transplants too, not just keywords and triggers. TWI_135 Darth Maul's
#// "This unit can attack 2 units instead of 1" changes the ATTACK MODE, so the host must be offered the
#// two-defender flow rather than an ordinary single-target attack.
#// Two enemy ground units are seated so the two-defender branch is reachable (it needs >=2 legal units).
#// Asserted as the OFFER, left pending: the prompt itself is the whole difference — a single-target
#// MZCHOOSE over the same three targets is what the card does WITHOUT the grant.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [TWI_135 SOR_063 SOR_063]
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:TWI_135
- P1>AnswerDecision:YES

## EXPECT
P1DECISIONTOOLTIP:Attack_the_base_or_two_units?

---

# GrantConstant_RetrofittedAirspeeder_GroundUnitReachesSPACE
#// ASH_230 — JTL_259 Retrofitted Airspeeder's "This unit can attack space units" is likewise constant.
#// A GROUND host that discards it must be able to aim at the enemy SPACE arena, which is otherwise
#// unreachable for it. Asserted on the offer pool: the enemy space unit appears alongside the ground one.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [JTL_259 SOR_063 SOR_063]
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:JTL_259
- P1>AnswerDecision:YES

## EXPECT
P1SELECTABLEHAS:theirSpaceArena-0

---

# GrantTriggered_JangosAttacksAndDefeatsDrawFires
#// ASH_230 — a TRIGGERED combat-hit ability transplants as well. SHD_138 Jango Fett's "When this unit
#// attacks and defeats a unit: Draw a card" must fire for the host that gained it.
#// The host (3 power) discards Jango, attacks SOR_128 Death Star Stormtrooper (3/1) and defeats it, so the
#// gained trigger draws a card. The search returns its 2 unpicked cards to the deck, so a hand of 1 can
#// only have come from the draw.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SHD_138 SOR_063 SOR_063 SOR_063]
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_028:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SHD_138
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P1HANDCOUNT:1

---

# GrantFromLAW054Maul_TakeControlTriggerFires
#// ASH_230 × LAW_054 Maul, Master of the Shadow Collective — the richest card the search can find, because
#// it carries BOTH halves of the grant at once: the Overwhelm keyword and a When-Attack-Ends trigger
#// ("if this unit dealt combat damage to a player's base, you may take control of a non-leader unit that
#// player controls").
#// The host discards Maul, attacks P2's BASE for 3, and the gained trigger then takes control of P2's
#// SOR_095 — which crosses the seat line: P1's arena goes 1 → 2 and P2's 1 → 0.
#// ⚠ TWO triggers are pending at attack end (the gained Maul trigger and Improvised Identity's own), so
#// the ordering prompt must be answered first — that prompt IS evidence the gained trigger was collected.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [LAW_054 SOR_063 SOR_063]
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:LAW_054
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0

---

# CONTROL_GrantFromADIFFERENTUnit_NoTakeControlOffer
#// The control for the section above: the same board and the same attack, but the search discards plain
#// SOR_063 Cloud City Wing Guard instead of Maul. No take-control trigger is gained, so P2 keeps its unit
#// and the attack simply deals 3 to the base with nothing pending.
#// Without this the section above could pass on a take-control that came from somewhere else entirely.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_063 SOR_063 SOR_063]
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_063
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1NODECISION

---

# GrantFromLAW054Maul_OverwhelmSpillFEEDSItsOwnTakeControlTrigger
#// ASH_230 × LAW_054 — the two halves of the transplant interacting, which is the sharpest thing this card
#// can do. Maul brings BOTH Overwhelm (a keyword, from the printed-keyword arrays) and the When-Attack-Ends
#// take-control (a trigger, via the grant carrier):
#//   1. the host (3 power) attacks SOR_128 Death Star Stormtrooper (3/1) and kills it with 1;
#//   2. the gained OVERWHELM spills the 2 excess into P2's base;
#//   3. that base damage satisfies the gained TRIGGER's own condition ("if this unit dealt combat damage
#//      to a player's base"), so the take-control fires and P1 takes SOR_095.
#// A grant that carried only keywords would stop at step 2 with nothing pending; one that carried only
#// triggers would never reach step 3, because without Overwhelm no damage reaches the base at all.
#// P2 is seated with a second unit precisely so the take-control has a legal target after the first dies.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [LAW_054 SOR_063 SOR_063]
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:LAW_054
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0

---

# CONTROL_NoMaul_NoOverwhelmSpillAndNoTrigger
#// The control for the cascade above: the same attack with a plain SOR_063 discarded instead of Maul. No
#// Overwhelm, so the 2 excess damage is LOST rather than spilling — P2's base takes nothing, no trigger
#// condition is met, and P2 keeps its second unit.
#// This is what makes the 2 base damage above attributable to the gained keyword specifically.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_063 SOR_063 SOR_063]
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_063
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1NODECISION

---

# GrantTriggered_JangoGAINSHISOWNAbilities_DrawsTWICE
#// ASH_230 × SHD_138 Jango Fett — the double-fire case, and the reason the grant dispatch deliberately
#// allows DUPLICATE card IDs. The host IS Jango and DISCARDS another Jango, so the same "when this unit
#// attacks and defeats a unit: draw a card" trigger fires twice: once as the attacker's own printed
#// ability, once as the gained one. P1 draws 2.
#// A dispatch that de-duplicated the effective card IDs — the obvious "tidier" implementation — would draw
#// only 1 here and pass every other section in this file.
#// The search returns its 2 unpicked cards, so the deck can only shrink by the draws: 5 → 4 after the
#// discard, then 2 draws leaves 2 with a hand of 2.
#// ⚠ TWO IDENTICAL triggers necessarily raise `Choose_trigger_to_resolve` — that ordering prompt is itself
#// proof the trigger was collected twice, and it MUST be answered or both draws sit pending and the
#// section reads as "the trigger never fired" (it did; it was waiting).

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SHD_138:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SHD_138 SOR_063 SOR_063 SOR_063 SOR_063]
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_028:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SHD_138
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P2GROUNDARENACOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:2

---

# GrantConstant_TheStrangerLAW086_DefenderDealsDamageFIRST
#// ASH_230 × LAW_086 The Stranger — "While attacking, you may have the defending unit deal combat damage
#// before this unit." A constant ability that reorders COMBAT ITSELF, so the transplant has to reach the
#// damage-ordering step, not just the trigger collection.
#// ⚠ ASH_230 is itself +0/+3, so the host is 3/6 while wearing it — the defender must out-damage SIX, not
#// three. IBH_076 Rampaging Wampa (6/3, vanilla) is the discriminator: it kills the host outright if it
#// strikes first, and dies to the host's 3 if damage is simultaneous.
#//   deal-first taken (this section): the Wampa's 6 kills the host, which never strikes → Wampa SURVIVES.
#//   simultaneous (the control):      both die.
#// Both boards therefore flip, which is what makes the reordering observable rather than inferred.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [LAW_086 SOR_063 SOR_063]
WithP2GroundArena: IBH_076:1:0
WithP2GroundArena: SEC_028:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:LAW_086
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2

---

# CONTROL_TheStrangerNotGranted_NormalSimultaneousTrade
#// The control: the same attack discarding a plain SOR_063. Damage is SIMULTANEOUS, so the host's 3 kills
#// the 3-HP Wampa and the Wampa's 6 kills the 6-HP host — both leave play. That is the ordinary outcome
#// the section above deliberately inverts.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_063 SOR_063 SOR_063]
WithP2GroundArena: IBH_076:1:0
WithP2GroundArena: SEC_028:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_063
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1

---

# GrantTriggered_BlizzardAssaultATAT_RoutesTheExcess
#// ASH_230 × SOR_088 Blizzard Assault AT-AT — "When this unit attacks and defeats a unit: You may deal the
#// excess damage from this attack to an enemy ground unit." A combat-hit trigger that reads the attack's
#// EXCESS, so the transplant has to carry both the trigger and the excess value with it.
#// The host is a 6-power Rampaging Wampa; SOR_128 Death Star Stormtrooper is 3/1, so 1 damage kills it and
#// FIVE is excess. The gained trigger routes all 5 into the second enemy, SOR_046 Consular Security Force
#// (3/7), which survives at 5.
#// Contrast Overwhelm: this routes the excess to a UNIT of the attacker's choosing, not to the base — so
#// P2's base must read 0, which is what separates the two transplanted mechanics.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: IBH_076:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_088 SOR_063 SOR_063]
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_088
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
P2BASEDMG:0

---

# GrantConstant_LothWolfLOF044_CANTATTACK_SuppressesTheOptionalAttack
#// ASH_230 × LOF_044 Loth-Wolf — "This unit can't attack." USER RULING (2026-08-18): the host gains the
#// RESTRICTION along with everything else, and **"cannot" beats "can"** — so because the attack clause is
#// a "you may", there is no resolution at all: the offer is never made.
#// The search still happens (Loth-Wolf is a legal ground-unit pick and is discarded), then the ability
#// simply ends. The host stays put, P2 is untouched, and NO decision is pending.
#// This is the section proving the transplant copies the discarded unit's abilities WHOLESALE rather than
#// cherry-picking the beneficial ones — every other grant section in this file is upside.
#// ⚠ Checked against the DISCARDED CardID, not the grant marker: the marker is not applied until after
#// this prompt would have been raised, so a marker-based check would be one step too late.
#// JTL_059 Corporate Defense Shuttle shares the printed restriction and rides the same shared roster
#// (_SWUCardIDCantAttack).

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [LOF_044 SOR_063 SOR_063]
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_028:1:0
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:LOF_044

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:2
P2BASEDMG:0
P1NODECISION
