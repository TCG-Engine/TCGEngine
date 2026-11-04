# Positive_AnExhaustedUnitAttacks_OverwhelmSpillsToTheBase
#// IC27_077 Mon Mothma - Truth Is An Abyss (Unit, Ground 3/6, cost 4, [Command,Heroism]) — "When Played: You may attack
#// with another unit, even if it's exhausted. For this attack, it gains Overwhelm and can't attack bases."
#// Released analogue: SEC_103 Mon Mothma's rulings — "Units that can't attack bases can still damage bases through other
#// abilities, like Overwhelm." So the granted Overwhelm DOES spill onto the base (preview card; the ruling is a sibling's).
#//
#// COVERAGE: offer=Offer_AnotherUnitThatCanAttackAUnit + CantAttackBases_TheTargetPoolHoldsOnlyUnits ·
#//   decline=Decline_NoAttack · boundary=NoOverwhelmExcessWhenTheDefenderSurvives (excess only on a defeat) ·
#//   no-target=NoEnemyUnit_NothingIsOffered · duration=TheOverwhelmIsForThisAttackOnly · control=N/A ("another unit" is a
#//   unit you control; no owner-scoped zone) · reqboundary=RequestBoundary_BeforeThePick_TurnPassesOnce ·
#//   modes=2P only (no player reference, no friendly/enemy wording)
#//
#// The EXHAUSTED 4/7 Industrious Team attacks P2's only unit, a 3/3 (the no-base target auto-resolves): it dies, and the
#// granted Overwhelm puts the 1 excess on P2's base. The attacker exhausts again and takes 3.
## GIVEN
CommonSetup: ggw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: IC27_077
WithP1GroundArena: LAW_124:0:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:IC27_077

---

# Offer_AnotherUnitThatCanAttackAUnit
#// The pick is left PENDING. Offered: the READY Battlefield Marine and the EXHAUSTED Imperial Dark Trooper (even if it's
#// exhausted). Excluded: Mon Mothma herself ("another" — she would otherwise qualify, exhausted on entry), the Loth-Wolf
#// (can never attack), the X-Wing (space — P2 has no space unit, and it can't attack bases), and P2's unit.
## GIVEN
CommonSetup: ggw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: IC27_077
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:0:0
WithP1GroundArena: LOF_044:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P1GROUNDARENAUNIT:3:CARDID:IC27_077

---

# CantAttackBases_TheTargetPoolHoldsOnlyUnits
#// "can't attack bases": with two enemy units the attack target is a real choice, left pending — and P2's base is not in it.
## GIVEN
CommonSetup: ggw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: IC27_077
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# NoEnemyUnit_NothingIsOffered
#// With no enemy unit there is nothing the granted attack could hit (bases are off-limits), so the "you may" can only
#// fizzle and is not offered — and the exhausted unit must stay EXHAUSTED (BeginSWUAttack readies an "even if it's
#// exhausted" attacker before looking for a target; the LAW_065 4-LOM shape).
## GIVEN
CommonSetup: ggw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: IC27_077
WithP1GroundArena: SOR_095:0:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0
P1GROUNDARENACOUNT:2

---

# Decline_NoAttack
#// "You may": declining leaves the exhausted unit exhausted and the enemy untouched.
## GIVEN
CommonSetup: ggw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: IC27_077
WithP1GroundArena: LAW_124:0:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1NODECISION

---

# NoOverwhelmExcessWhenTheDefenderSurvives
#// Overwhelm only spills EXCESS: the 4-power Industrious Team into a 3/7 leaves it alive on 4 damage, and nothing reaches
#// P2's base.
## GIVEN
CommonSetup: ggw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: IC27_077
WithP1GroundArena: LAW_124:0:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:0

---

# TheOverwhelmIsForThisAttackOnly
#// "For this attack": once the attack ends the unit no longer has Overwhelm (still in the same phase).
## GIVEN
CommonSetup: ggw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: IC27_077
WithP1GroundArena: LAW_124:0:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# RequestBoundary_BeforeThePick_TurnPassesOnce
#// The pick ends the request; the attack runs in the next one, and the turn passes exactly once — the play owns the close.
#// ⚠ No P1OnlyActions — it would make TURNPLAYER blind.
## GIVEN
CommonSetup: ggw/rrk/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: IC27_077
WithP1GroundArena: LAW_124:0:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1
TURNPLAYER:2
NOEXTRAACTION

---

# Offer_AnUndamagedOggdoBogdoIsNotOffered
#// LOF_063 Oggdo Bogdo "can't attack while undamaged" — the same restriction the attacker glow reads. Undamaged he is not
#// offered (only the exhausted Marine is); with damage on him he is.
## GIVEN
CommonSetup: ggw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: IC27_077
WithP1GroundArena: LOF_063:1:0
WithP1GroundArena: SOR_095:0:0
WithP1GroundArena: LOF_063:1:1
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-2
