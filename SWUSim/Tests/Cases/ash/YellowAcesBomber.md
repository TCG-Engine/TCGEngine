# DealTwoToBase_SelectableIsEitherBase
#// ASH_253 Yellow Aces Bomber (Space, 2/4, Support) — On Attack: if this unit is upgraded, deal 2 damage to
#// A base (either player's). Carrying a Shield (SOR_T02) it is upgraded; attacking the enemy base, the
#// deal-2 target choice offers BOTH bases (it is not a choose-nothing "may").
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_253:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T02
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# DealTwoToOwnBase
#// The deal-2 may hit the FRIENDLY base. Upgraded (Shield), the bomber attacks the enemy base for 2 combat
#// and directs the extra 2 at its OWN base: friendly base 2, enemy base 2.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_253:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T02
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:2
P2BASEDMG:2

---

# SupportNotUpgraded_NoBaseDamage
#// The deal-2 is gated on the ATTACKER being upgraded. Via Support, an un-upgraded friendly unit (SOR_164
#// 4/5) is chosen to attack the enemy base: no deal-2 is offered, so the base takes only the 4 combat
#// damage.
## GIVEN
CommonSetup: grk/grk/{myResources:6;handCardIds:ASH_253}
WithP1GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:4
P1BASEDMG:0

---

# SupportUpgraded_DealsTwoToBase
#// Via Support, an UPGRADED friendly unit (SOR_164 carrying a Shield) is chosen; the granted On Attack
#// deals 2 to a base on top of the 4 combat damage → enemy base 6.
## GIVEN
CommonSetup: grk/grk/{myResources:6;handCardIds:ASH_253}
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:6

---

# NotUpgraded_NoBaseDamage
#// ASH_253 Yellow Aces Bomber (Space, 2/4) — the On Attack deal-2 is gated on this unit being upgraded.
#// Attacking on its own with NO upgrade, no deal-2 is offered; the enemy base takes only the 2 combat damage.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_253:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:2
P1BASEDMG:0
P1NODECISION
