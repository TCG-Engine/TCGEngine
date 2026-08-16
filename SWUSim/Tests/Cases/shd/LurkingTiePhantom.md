# ImmuneToAbilityDamage
#// SHD_187 Lurking TIE Phantom (3-cost space) — Raid 2 + "This unit can't be captured, damaged, or defeated
#// by enemy card abilities." Guard: P2's Daring Raid (deal 2 to a unit) targeting the Phantom is prevented.
#// COVERAGE: offer=EnemyCapture_PhantomIsStillOffered + EnemyDefeatEffect_PhantomIsStillOffered +
#//           EnemyEvent_ControllerPickerOffer_IncludesPhantom + ForceLightning_OfferIncludesPhantom
#//           (the immunity is a resolution-time refusal, NOT a targeting restriction — the Phantom stays
#//           in every enemy effect's pool) · reqboundary=SimulateRequestBoundary_CaptureImmunitySurvives
#//           (captor pick and victim pick are separate requests; the immunity is re-read from the
#//           serialized gamestate) · control=EnemyNoGlory_TakesControlFirstThenDefeats ("enemy" is
#//           evaluated at the moment the defeat is applied, so a take-control-then-defeat kills it) ·
#//           boundary pair=each enemy leg is paired with the same effect run by the Phantom's OWN
#//           controller — Takedown/RivalsFall, ImperialInterceptor/Devastator,
#//           DevastatingGunship/CountDooku — plus the two scoping negatives
#//           EnemyAttack_CombatDamageDefeatsIt (combat is not a card ability) and
#//           StateBased_ShrunkToZeroHp_IsDefeated (no-remaining-HP is not a defeat effect) ·
#//           decline=N/A (the ability is a constant with no optional component and no cost — nothing
#//           on this card is ever offered as a "you may")

## GIVEN
CommonSetup: yyk/rrk/{theirResources:1}
WithActivePlayer: 2
WithP1SpaceArena: SHD_187:1:0
WithP2Hand: SHD_178

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SHD_187
P1SPACEARENAUNIT:0:DAMAGE:0

---

# EnemyCapture_RelentlessPursuit_NotCaptured
#// SHD_187 — the CAPTURE leg of the immunity. P1's Cassian (SHD_148) is told to capture an enemy
#// non-leader unit costing the same or less; both P2 bodies qualify on cost (the 2-cost Battlefield
#// Marine and the 3-cost Phantom) and P1 picks the Phantom. The capture is refused: the Phantom stays
#// in P2's space arena, and Cassian gains no captive subcard (a successful capture attaches the
#// captured card face-down under the captor).

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_232
WithP1GroundArena: SHD_148:1:0
WithP1GroundArena: SOR_131:1:0
WithP1SpaceArena: SHD_137:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# EnemyCapture_PhantomIsStillOffered
#// SHD_187 — the immunity is a RESOLUTION-time refusal, not a targeting restriction: the Phantom is
#// still a legal choice for an enemy capture effect (it just does nothing). Same fixture as above with
#// the captor already chosen and the capture-target choice left PENDING, so the offer itself is the
#// assertion — both the 2-cost Marine and the 3-cost Phantom are in the pool.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_232
WithP1GroundArena: SHD_148:1:0
WithP1GroundArena: SOR_131:1:0
WithP1SpaceArena: SHD_137:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# EnemyAttack_CombatDamageDefeatsIt
#// SHD_187 — THE load-bearing negative: combat damage is NOT a card ability, so the immunity does not
#// touch it. P2's Punishing One (SHD_137, 3/4 space) attacks the 2/2 Phantom and kills it outright;
#// the Phantom's 2 counter-damage lands on Punishing One. Without this section the immunity could be
#// implemented as a blanket "nothing can ever hurt this unit" and every other section would still pass.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: SHD_137:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_137
P1SPACEARENAUNIT:0:DAMAGE:2

---

# EnemyUnitAbility_ImperialInterceptor_NoDamage
#// SHD_187 — the DAMAGE leg against an enemy UNIT ability (the existing section above covers an enemy
#// EVENT). P1 plays Imperial Interceptor (SOR_132), whose When Played deals 3 damage to a space unit,
#// and aims it at the Phantom: the whole instance is prevented, so the 2-HP Phantom survives at 0 damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_132
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2SPACEARENAUNIT:0:DAMAGE:0

---

# EnemyEvent_Takedown_NotDefeated
#// SHD_187 — the DEFEAT leg against an enemy EVENT. Takedown (SOR_077) defeats a unit with 5 or less
#// remaining HP; the 2-HP Phantom is the only unit in play, so it is the forced target. The defeat is
#// refused and the Phantom stays in the space arena undamaged, while Takedown itself still resolves to
#// P1's discard (the event is spent, only its effect is blocked).

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_077
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2SPACEARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1

---

# EnemyUnitAbility_DevastatingGunship_NotDefeated
#// SHD_187 — the DEFEAT leg against an enemy UNIT ability. Devastating Gunship (TWI_036) has
#// "When Played: Defeat an enemy unit with 2 or less remaining HP"; the 2-HP Phantom is the only
#// qualifying enemy so the mandatory pick lands on it, and the defeat is refused.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5}
P1OnlyActions: true
WithP1Hand: TWI_036
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_187
P1SPACEARENAUNIT:0:CARDID:TWI_036

---

# EnemyDefeatEffect_PhantomIsStillOffered
#// SHD_187 — the defeat immunity, like the capture one, does not remove the Phantom from an enemy
#// effect's target pool. A second qualifying enemy body (SOR_225, 2/1) is seated so the Gunship's
#// mandatory "defeat an enemy unit with 2 or less remaining HP" pick cannot auto-resolve; the choice is
#// left PENDING and the offer is the assertion — the Phantom is in it.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5}
P1OnlyActions: true
WithP1Hand: TWI_036
WithP2SpaceArena: SHD_187:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirSpaceArena-0&theirSpaceArena-1

---

# StateBased_ShrunkToZeroHp_IsDefeated
#// SHD_187 — the second load-bearing negative. Make an Opening (SOR_076) gives a unit -2/-2 for the
#// phase; that is not damage and not a defeat effect, it just lowers the printed body to 0/0, and a unit
#// with no remaining HP is defeated by the state-based rule. The immunity is scoped to enemy card
#// abilities CAPTURING/DAMAGING/DEFEATING the unit, so it cannot save the Phantom here.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3;myBaseDamage:3}
P1OnlyActions: true
WithP1Hand: SOR_076
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:1
P1BASEDMG:1

---

# OwnEvent_OpenFire_DamagesAndDefeatsIt
#// SHD_187 — "by ENEMY card abilities": its own controller's abilities are unaffected. Boundary partner
#// to the Daring Raid section at the top of this file (an enemy event dealing damage is prevented).
#// P1 owns the Phantom and plays Open Fire (SOR_172, deal 4 damage to a unit) at it: the damage lands
#// in full and the 2-HP Phantom dies.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP1SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:2

---

# OwnEvent_RivalsFall_DefeatsIt
#// SHD_187 — boundary partner to EnemyEvent_Takedown_NotDefeated: the same kind of effect (an event
#// that says "defeat a unit") works when the Phantom's own controller plays it. Rival's Fall (SHD_079)
#// defeats P1's own Phantom.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_079
WithP1SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:2

---

# OwnUnitAbility_Devastator_DamagesIt
#// SHD_187 — boundary partner to EnemyUnitAbility_ImperialInterceptor_NoDamage: a friendly UNIT ability
#// dealing damage is not prevented. P1 plays Devastator (SOR_090) with 10 resources; its When Played
#// deals damage equal to the resources controlled and P1 aims all 10 at their own Phantom, killing it.

## GIVEN
CommonSetup: ggk/ggk/{myResources:10}
P1OnlyActions: true
WithP1Hand: SOR_090
WithP1SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_090
P1DISCARDCOUNT:1

---

# OwnUnitAbility_CountDooku_DefeatsIt
#// SHD_187 — boundary partner to EnemyUnitAbility_DevastatingGunship_NotDefeated: a friendly unit
#// ability that says "defeat" works. Count Dooku (SOR_038) enters with two triggers (Shielded and his
#// When Played), so the resolution order is picked first; his "defeat a unit with 4 or less remaining
#// HP" then takes P1's own 2-HP Phantom.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_038
WithP1SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_038
P1DISCARDCOUNT:1

---

# EnemyEvent_ControllerPicksTarget_StillNotDefeated
#// SHD_187 — the immunity is about WHOSE CARD ABILITY it is, not who does the picking. Power of the
#// Dark Side (SOR_041) is P1's event but makes P2 choose one of their own units to defeat; P2 nominates
#// the Phantom. It is still an ENEMY card ability, so the defeat is refused and P2 keeps all four units.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
WithActivePlayer: 1
WithP1Hand: SOR_041
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_038:1:0
WithP2SpaceArena: SHD_187:1:0
WithP2SpaceArena: SOR_090:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:mySpaceArena-0

## EXPECT
P2SPACEARENACOUNT:2
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2GROUNDARENACOUNT:2
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1

---

# EnemyEvent_ControllerPickerOffer_IncludesPhantom
#// SHD_187 — same fixture with the opponent's choice left PENDING: the Phantom is offered to its own
#// controller alongside every other unit they control. Nothing filters it out of an enemy effect's
#// pool; the refusal happens when the defeat is applied.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
WithActivePlayer: 1
WithP1Hand: SOR_041
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_038:1:0
WithP2SpaceArena: SHD_187:1:0
WithP2SpaceArena: SOR_090:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0&mySpaceArena-1

---

# SimulateRequestBoundary_CaptureImmunitySurvives
#// SHD_187 — the capture effect spans two requests in production (pick the captor, then pick the
#// victim), so the immunity has to be re-read from the serialized gamestate rather than from anything
#// cached during the first pick. Mirrors EnemyCapture_RelentlessPursuit_NotCaptured with the boundary
#// inserted between the two answers.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_232
WithP1GroundArena: SHD_148:1:0
WithP1GroundArena: SOR_131:1:0
WithP1SpaceArena: SHD_137:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_187
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# ForceLightning_BlanksTheImmunityThenKillsIt
#// SHD_187 — the immunity is one of the Phantom's own ABILITIES, so an effect that makes it lose all
#// abilities takes the immunity with it. Force Lightning (SOR_138) blanks the chosen unit for the phase
#// and THEN, with a friendly Force unit in play (Secretive Sage LOF_061), pays 1 resource to deal it 2
#// damage — enough to kill the 2-HP Phantom. Order is load-bearing: the blank lands before the damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_138
WithP1GroundArena: LOF_061:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:1

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:1
P1RESAVAILABLE:1

---

# ForceLightning_OfferIncludesPhantom
#// SHD_187 — "choose a unit" is unrestricted, so the Phantom is in Force Lightning's pool alongside the
#// friendly Force unit. Choice left PENDING so the offer is the assertion.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_138
WithP1GroundArena: LOF_061:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirSpaceArena-0

---

# EnemyNoGlory_TakesControlFirstThenDefeats
#// SHD_187 — the CONTROL-CHANGE case. No Glory, Only Results (JTL_043) takes control of a non-leader
#// unit and THEN defeats it; by the time the defeat is applied the Phantom is P1's own unit, so the
#// "enemy card abilities" immunity no longer applies and it dies. The card still goes to its OWNER's
#// discard, so P2's discard is the one that grows.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5}
P1OnlyActions: true
WithP1Hand: JTL_043
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1

---

# IndirectDamage_IsNotPrevented_DamageSticks
#// SHD_187 — INDIRECT damage is UNPREVENTABLE, so this immunity does not stop it even though the source
#// is an enemy card ability. P1's First Order Stormtrooper (JTL_132) attacks P2's base; its On Attack
#// deals 1 indirect to P2, who assigns it to the Phantom rather than the base or the Wampa. The damage
#// sticks — every other enemy-ability damage section in this file zeroes out instead.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_132:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:mySpaceArena-0:1

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2SPACEARENAUNIT:0:DAMAGE:1
P2BASEDMG:2

---

# IndirectDamage_TwoTicksDefeatIt
#// SHD_187 — following on from the section above: because indirect damage accumulates normally on the
#// Phantom, two 1-point ticks kill the 2-HP body. Two First Order Stormtroopers attack P2's base in turn
#// and P2 assigns both indirect points to the Phantom.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_132:1:0
WithP1GroundArena: JTL_132:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:mySpaceArena-0:1
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:mySpaceArena-0:1

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:1
P2BASEDMG:4

---

# EnemyCollectedBounty_OnAFriendlyVal_IsPrevented
#// SHD_187 — a Bounty is printed on the unit that CARRIES it, but it is collected and resolved by that
#// unit's OPPONENT, so from the Phantom's side it is an ENEMY card ability even though Val (SHD_058) is
#// its own teammate. P1's Wampa defeats P2's Val; Val's own When Defeated (P2's ability) puts 2
#// Experience on the Phantom and that lands, while P1's collected "Bounty — deal 3 damage to a unit"
#// aimed at the Phantom is prevented. Both halves in one section is the point: the same dying unit
#// produces one friendly effect that works and one enemy effect that does not.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SHD_058:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>Drain
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENACOUNT:0

---

# EnemyDividedDamage_OverwhelmingBarrage_NoDamage
#// SHD_187 — divided/assigned damage is still card-ability damage. Overwhelming Barrage (SOR_092)
#// buffs P1's Cassian (SHD_148, 3/5 -> 5/7) and has him deal 5 damage divided among other units; 1 is
#// assigned to the enemy Phantom and 4 to a co-target. The Phantom's share is prevented in full while
#// the co-target takes its 4 — the split funnel must run the same prevention chain as the single-target
#// funnel, and the co-target proves the ability itself resolved.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_092
WithP1GroundArena: SHD_148:1:0
WithP2SpaceArena: SHD_187:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0:1,theirGroundArena-0:4

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:4
