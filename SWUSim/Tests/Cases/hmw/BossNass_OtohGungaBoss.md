# WhenPlayed_DefeatShieldOnFriendlyGungan_CreatesBeastWithShield
#// HMW_077 Boss Nass, Otoh Gunga Boss — cost 4, [Vigilance][Heroism], Ground 4/6, unique,
#// Traits: Gungan, Official.
#// Text: "When Played/On Attack: You may defeat a Shield token on a friendly Gungan unit. If you do,
#//        create a Beast token and give a Shield token to it."
#//
#// TWO trigger windows sharing one effect. The target pool is doubly restricted — FRIENDLY, GUNGAN, and
#// (implicitly) must actually HAVE a Shield token to defeat — so each of those three gates gets its own
#// negative. "a friendly Gungan unit" has no "another", so Boss Nass (a Gungan himself) is a legal
#// target for his own ability whenever he is shielded.
#//
#// The payoff is gated by "If you do", so it measures whether the shield was really defeated rather
#// than assuming the attempt worked. "give a Shield token to IT" means the created Beast — passed as
#// the rider to the BATCH create API so it survives Moff Jerjerrod's "create twice that number
#// instead" (a rider stamped on the returned UID would leave his extra token bare).
#//
#// COVERAGE: offer=WhenPlayed_ShieldedNonGungan_AndEnemyGungan_NotOffered (SELECTABLEEXACT: trait gate
#//                 AND friendly gate proven together, 2 legal + 2 excluded)
#//           decline=WhenPlayed_Decline_NothingHappens + OnAttack_Decline_NothingHappens
#//           boundary=WhenPlayed_GunganWithTwoShields_OnlyOneDefeated (exactly one, not all)
#//           control=N/A (no owner-scoped zone; the friendly-vs-enemy split is the offer test above,
#//                 and tokens created by this card go to its own controller by construction)
#//           reqboundary=N/A (no state written before the decision and read behind it)
#//
#// Fixture note: LOF_247 Gungan Warrior (cost 3, 3/2, non-unique) is Shielded, so it is the natural
#// "friendly Gungan carrying a Shield token" — the shield is seeded explicitly since fixture placement
#// does not run entry keywords.
#// Board after resolution: LOF_247 (idx 0), Boss Nass (idx 1, played), Beast token (idx 2, created).

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_077
WithP1GroundArena: LOF_247:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:LOF_247
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:HMW_077
P1GROUNDARENAUNIT:2:CARDID:HMW_T03
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1
P1GROUNDARENAUNIT:2:POWER:3
P1GROUNDARENAUNIT:2:HP:3

---

# WhenPlayed_Decline_NothingHappens
#// The "you may" decline: the shield is KEPT and no Beast is created. Declining must not spend the
#// shield — the defeat is the whole point of the "If you do".

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_077
WithP1GroundArena: LOF_247:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:LOF_247
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:HMW_077

---

# WhenPlayed_ShieldedNonGungan_AndEnemyGungan_NotOffered
#// OFFER cell, proving BOTH restrictions at once against an otherwise-identical board. The decision is
#// left PENDING so the pool itself can be inspected.
#//   • SOR_095 (shielded, NOT a Gungan) must be excluded → the Gungan trait gate is load-bearing.
#//   • P2's shielded LOF_247 must be excluded → the "friendly" gate is load-bearing.
#// TWO legal targets are seeded (LOF_247 is non-unique) so the pool is genuinely inspectable rather
#// than collapsing to a lone entry.
#// Layout: LOF_247 idx0, LOF_247 idx1, SOR_095 idx2, Boss Nass idx3 (played last).

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_077
WithP1GroundArena: [LOF_247:1:0 LOF_247:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP1GroundArenaUpgrade: 2:SOR_T02
WithP2GroundArena: LOF_247:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# WhenPlayed_GunganWithNoShield_NoPrompt
#// The has-a-shield gate. A friendly Gungan with NO Shield token gives the ability nothing to defeat,
#// so the whole thing is skipped — no prompt at all, rather than an offer that can only fizzle.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_077
WithP1GroundArena: LOF_247:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# WhenPlayed_GunganWithTwoShields_OnlyOneDefeated
#// Quantity discrimination: "a Shield token" is exactly ONE. A Gungan carrying two Shield tokens loses
#// precisely one, and exactly one Beast is created.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_077
WithP1GroundArena: LOF_247:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:2:CARDID:HMW_T03
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1

---

# WhenPlayed_TwoShieldedGungans_OnlyOneShieldDefeated_OneBeast
#// Quantity discrimination across UNITS: the ability defeats one shield on ONE chosen Gungan, not one
#// per Gungan. The unchosen Gungan keeps its shield and only a single Beast appears.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_077
WithP1GroundArena: [LOF_247:1:0 LOF_247:1:0]
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 1:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1GROUNDARENAUNIT:3:CARDID:HMW_T03
P1GROUNDARENAUNIT:3:SHIELDCOUNT:1

---

# OnAttack_DefeatsHisOwnShield_CreatesBeastWithShield
#// The SECOND trigger window, and the self-target case: the text says "a friendly Gungan unit" with no
#// "another", and Boss Nass IS a Gungan — so his own Shield token is a legal thing to defeat.
#// He attacks the base (no counter-damage, so the shield can only leave via the ability).
#// The Beast is created mid-attack at ground index 1; base takes his printed 4.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_077:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_077
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# OnAttack_TargetsAnotherFriendlyGungan
#// The On Attack window reaches OTHER friendly Gungans too, not merely the attacker — the two windows
#// share one target pool and neither is self-only.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: [LOF_247:1:0 HMW_077:1:0]
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:LOF_247
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:2:CARDID:HMW_T03
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1

---

# OnAttack_Decline_NothingHappens
#// Decline on the On Attack window specifically — it must be wired on BOTH windows, not just the play
#// path. The shield survives, no Beast, and combat still resolves normally.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_077:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# OnAttack_NoShieldedGungan_NoPromptAndCombatStillResolves
#// No-valid-target on the combat window. An empty target pool must skip the ability cleanly WITHOUT
#// stalling the attack — a mid-combat offer that hangs is the failure mode here, so assert both that
#// no decision is pending and that the damage landed.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_077:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:4
P1GROUNDARENACOUNT:1

---

# OnAttack_EnemyShieldedGunganOnly_NoPrompt
#// The friendly gate on the combat window: an ENEMY shielded Gungan is the only Gungan with a shield,
#// so there is nothing to defeat and no prompt — and crucially the enemy's shield is untouched.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_077:1:0
WithP2GroundArena: LOF_247:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
