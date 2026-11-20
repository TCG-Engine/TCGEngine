# OnAttack_GainsAnExperience_ThisAttackDealsSix
#// IC27_081 Mace Windu - Dropping By (Unit, Ground 5/5, cost 6, [Command,Heroism]) — "Ambush. On Attack: Give an
#// Experience token to this unit." (Ambush is the printed keyword, generic; the card's own clause is the On Attack.)
#//
#// COVERAGE: offer=N/A (no choice — "this unit" is fixed) · decline=N/A (the On Attack is mandatory; Ambush's own YES/NO is
#//   the keyword's: Ambush_Declined_NoAttack_NoExperience) · boundary=the exact 5 → 6 (On Attack resolves BEFORE damage)
#//   + OnAttack_StacksOnAnExistingExperience (adds, not "if it has none") · control=N/A ("this unit" names no
#//   owner-scoped zone) · reqboundary=Ambush_RequestBoundary_BeforeTheYes · modes=2P only (no player reference, no
#//   friendly/enemy wording)
#//
#// The On Attack resolves in the attack's On Attack window, before combat damage — so the token's +1/+1 counts for THIS
#// attack: 6 to the base, not 5. Mace ends 6/6 with one upgrade.
## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_081:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6

---

# OnAttack_AttackingAUnit_TheExperienceCountsInCombat
#// The same On Attack on a UNIT attack. Into P2's 3/7 Consular Security Force: the defender takes 6, Mace takes 3 and
#// survives on 6 HP.
## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_081:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:0

---

# OnAttack_StacksOnAnExistingExperience
#// "Give an Experience token" adds one every attack — it is not "if this unit has none". Seated with one already, Mace
#// attacks with two: 7 to the base, 7/7, two upgrades.
## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_081:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:7

---

# Ambush_PlayedFromHand_TheAmbushAttackGainsTheExperience
#// The other dispatch path: played from hand, Ambush readies it and attacks P2's only unit (the target auto-resolves, so YES
#// is the whole answer). The On Attack fires on the Ambush attack too: the 3/7 takes 6.
## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IC27_081
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_081
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:6
P1RESAVAILABLE:0

---

# Ambush_Declined_NoAttack_NoExperience
#// The negative: the token is an ON ATTACK, not a When Played. Declining the Ambush means no attack, so no token.
## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IC27_081
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_081
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Ambush_RequestBoundary_BeforeTheYes
#// The Ambush YES/NO ends the request; the attack — and its On Attack — run in the next one.
## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IC27_081
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:6
