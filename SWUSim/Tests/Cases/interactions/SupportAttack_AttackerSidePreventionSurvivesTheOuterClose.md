#// BUG #1073 (game 1208106, 2026-09-24): "Amidala loses damage replacement when Support."
#//
#// A SUPPORT bonus attack is nested inside the deploy action that launched it, and the deploy's After Action
#// is allowed to run BEFORE the nested attack's damage step (GameLogic's bare SWU_TRIGGER_RESUME skips its
#// "don't finalise while a |COMBAT resume is queued" guard whenever SWU_COMBAT_SKIP_AFTERACTION is set — which
#// is exactly the Support case). SWUAfterAction closes the per-attack identity window by setting
#// SWU_CURRENT_ATTACKER_UID to '0', and _SWUOfferCombatPreventions uses that var as the ONLY way to find the
#// attacker: with 0 it resolves to a null mzID and skips its whole ATTACKER-side block. So every
#// attacker-side combat-damage replacement was silently dropped on a Support attack.
#//
#// The DEFENDER-side half is keyed on SWU_CURRENT_DEFENDER_UIDS, which nothing clears — hence "only when
#// Support", and hence the CONTROL sections below, which were green throughout.
#//
#// Reported board: P1 deployed ASH_009 Ahsoka Tano, Plotted the Naboo Royal Starship, and Support-attacked
#// with SEC_101 Queen Amidala into a Sentinel SEC_048 Captain Rex. P1 was offered Ahsoka's lent "+2/+0 to a
#// weaker unit" but never "defeat a trait-sharing friendly to prevent", so Amidala ate Rex's 7 and died.
#// The Plot is incidental — it only added a second legal prevention cost (the Starship is [Naboo]).
#//
#// Cards: SEC_101 Queen Amidala 5/3 [Naboo,Official] ("If damage would be dealt to this unit, you may defeat
#//   another friendly unit that shares a trait with this unit. If you do, prevent that damage.") ·
#//   SEC_T01 Spy token 1/1 [Official] — the prevention cost · SEC_048 Captain Rex 7/7 — the defender that
#//   kills her · ASH_009 Ahsoka Tano / ASH_014 The Mandalorian — the two Support lenders ·
#//   ASH_062 The Mandalorian - Devoted Rescuer + SOR_T02 Shield — the sibling replacement in the same block.
#
# CONTROL_PlainAttack_AmidalaIsOfferedThePrevention
#// No Support: nothing closes the action mid-attack, so the offer was always made. Pins that the fixture
#// itself (traits, a live cost, a defender with power) earns the offer.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: [SEC_101:1:0 SEC_T01:1:0]
WithP2GroundArena: SEC_048:0:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Defeat_a_trait-sharing_friendly_to_prevent_combat_damage_to_Queen_Amidala?

---

# CONTROL_PlainAttack_PayTheSpy_AmidalaLives
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: [SEC_101:1:0 SEC_T01:1:0]
WithP2GroundArena: SEC_048:0:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# RED_SupportAttack_AmidalaIsStillOfferedThePrevention
#// The report, minimised. Deploy Ahsoka (Epic Action) -> Support with Amidala -> attack Rex. Her lent On Attack
#// ("+2/+0 to a unit with less power than this unit") is declined FIRST, then the prevention must be offered.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: [SEC_101:1:0 SEC_T01:1:0]
WithP2GroundArena: SEC_048:0:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Defeat_a_trait-sharing_friendly_to_prevent_combat_damage_to_Queen_Amidala?

---

# RED_SupportAttack_PayTheSpy_AmidalaLives_AndTheTurnStillPasses
#// The Spy is defeated as the cost, Amidala takes none of Rex's 7 and still deals her 5. The deploy action
#// closes exactly once (NOEXTRAACTION + TURNPLAYER, no P1OnlyActions — see the Support after-action family).
#// ⚠ Ground count 2 alone cannot tell "the Spy paid" from "Amidala died" — both leave two units. CARDID at
#// index 0 is what discriminates them (SEC_101 here, SEC_T01 in the decline section below).
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: [SEC_101:1:0 SEC_T01:1:0]
WithP2GroundArena: SEC_048:0:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_101
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:5
TURNPLAYER:2
NOEXTRAACTION

---

# RED_SupportAttack_DeclineThePrevention_AmidalaDies
#// The mirror of the section above: the offer is real and DECLINABLE. Without this, an offer that auto-resolved
#// itself would satisfy the sections above. Declining leaves the Spy (index 0) and the deployed Ahsoka.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: [SEC_101:1:0 SEC_T01:1:0]
WithP2GroundArena: SEC_048:0:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P2GROUNDARENAUNIT:0:DAMAGE:5
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# RED_SupportAttack_MandalorianLender_NoDecisionFromItsOnAttack
#// The other Support lender, ASH_014. Its On Attack ("If you have the initiative, you may draw a card") is a
#// no-op here (P2 has the initiative), so the Support attack raises NO interactive decision of its own — the
#// premature close happens anyway. Pins that the bug is the outer close, not the request boundary a lent
#// decision creates.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_014}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: [SEC_101:1:0 SEC_T01:1:0]
WithP2GroundArena: SEC_048:0:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Defeat_a_trait-sharing_friendly_to_prevent_combat_damage_to_Queen_Amidala?

---

# RED_SupportAttack_Ash062ShieldPrevention_IsInTheSameSkippedBlock
#// ASH_062 The Mandalorian's "defeat a Shield on this unit to prevent damage to another friendly unit" is
#// dispatched from the SAME attacker-side block of _SWUOfferCombatPreventions, so it was lost identically.
#// SOR_095 Battlefield Marine 3/3 Support-attacks Rex and would take 7; ASH_062 holds one Shield.
#// ⚠ Ahsoka's lent On Attack raises NO decision here (nothing in play has less than the attacker's 3 power —
#// ASH_062 is 4, Rex is 7), so there is no buff answer to give; the trigger still bags, which is what puts
#// the attack on the resume path. An extra AnswerDecision here would silently DECLINE the prevention instead.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: [SOR_095:1:0 ASH_062:1:0]
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArena: SEC_048:0:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Defeat_a_Shield_on_The_Mandalorian_to_prevent_combat_damage_to_this_unit?

---

# CONTROL_SupportAttack_AmidalaAsDefender_WasNeverBroken
#// The defender half is keyed on SWU_CURRENT_DEFENDER_UIDS, which the premature close leaves intact. Green
#// before and after the fix — it is what localises the bug to the attacker branch.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SEC_101:0:0 SEC_T01:0:0]
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P2HASDECISION
P2DECISIONTOOLTIP:Defeat_a_trait-sharing_friendly_to_prevent_combat_damage_to_Queen_Amidala?

---

# SupportAttack_ClosesThePerAttackWindow_ALaterDefeatIsNotWhileAttacking
#// The other half of the fix, and the reason the window is closed by _SWUCombatFinishAction rather than just
#// left open: on the Support path NO SWUAfterAction ever runs for the attack (the outer deploy's already did,
#// ahead of the damage), so nothing else would close it. SOR_095 Battlefield Marine 3/3 Support-attacks the
#// base and SURVIVES; P2 then kills it with SEC_258 Grassroots Resistance ("Deal 3 damage to a unit. / Heal 3
#// damage from your base."). That is not "defeated while attacking", so SEC_158 Oppression Breeds Rebellion
#// must draw NOTHING — P1's hand ends empty rather than holding 3.
#// ⚠ The kill must come from an EVENT, not an attack: any attack would overwrite SWU_CURRENT_ATTACKER_UID
#// with its own attacker and hide the leak.
#// ⚠ And from a VANILLA victim: SEC_042 Cassian Andor was the obvious 2/2 here and silently survived — he
#// prevents 2 of any enemy ability damage, so Grassroots' 3 landed as 1.
#// P2's base ends at 0: the Marine's 3 went in and Grassroots healed exactly 3 back off.
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP2Resources: 4:SOR_095:1
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_158
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Hand: SEC_258
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_009
P1HANDCOUNT:0
P1DECKCOUNT:3
P2BASEDMG:0
