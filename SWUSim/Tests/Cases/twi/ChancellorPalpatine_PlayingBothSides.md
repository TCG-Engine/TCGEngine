# Heroism_BounceCloneToken_NotADefeat_NoTrigger
#// JUDGE RULING (2026-08-13, supersedes the earlier defeat-instead encoding): a bounced token is NOT
#// defeated — it is removed from the game (it still counts as returned-to-hand/left-play, but fires no
#// defeat observers or flags). P1 plays TWI_191 to "return" its own Clone Trooper token (TWI_T02,
#// Heroism): the token ceases WITHOUT being defeated, so "a friendly Heroism unit was defeated this
#// phase" is NOT satisfied — the leader Action resolves to no effect (no draw, no heal, no flip; the
#// leader just exhausts).
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myResources:3;myBaseDamage:5;handCardIds:TWI_191}
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>UseLeaderAbility
## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_191
P1GROUNDARENACOUNT:0
P1BASEDMG:5
P1DECKCOUNT:2
P1LEADER:EXHAUSTED
P1LEADER:NOTDEPLOYED

---

# Heroism_BounceUnit_NoTrigger
#// TWI_017 "Flipatine" (HEROISM face) — a BOUNCE is not a defeat. P1 plays TWI_191 (return a friendly
#// non-leader non-Vehicle unit) to return its own Heroism Marine (SOR_095) to hand, then uses the leader
#// Action. Since no friendly Heroism unit was DEFEATED this phase, the Action resolves nothing (no draw,
#// no heal, no flip) — the leader is just spent (ruling 2).
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myResources:3;myBaseDamage:5;handCardIds:TWI_191}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>UseLeaderAbility
## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_191
P1GROUNDARENACOUNT:0
P1BASEDMG:5
P1DECKCOUNT:2
P1LEADER:EXHAUSTED
P1LEADER:NOTDEPLOYED

---

# Heroism_ConditionMet_DrawHealFlip
#// TWI_017 Chancellor Palpatine "Flipatine" (HEROISM face, Deployed=false) — Action [Exhaust]: If a
#// friendly Heroism unit was defeated this phase, draw a card, heal 2 from your base, then flip. P1's
#// Heroism Marine (SOR_095) attacks into the 3/7 and dies (friendly Heroism defeated), then P1 uses the
#// leader Action: draws 1 (deck 2→1), heals 2 (base 5→3), and flips to the Villainy face (Deployed=true).
#// Ruling 1: the leader stays EXHAUSTED after flipping.
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
## EXPECT
P1BASEDMG:3
P1DECKCOUNT:1
P1LEADER:EXHAUSTED
P1LEADER:DEPLOYED

---

# Heroism_ConditionUnmet_UsableNoEffect
#// TWI_017 "Flipatine" (HEROISM face) — Ruling 2: the Action can still be USED even with no friendly
#// Heroism unit defeated this phase, but NONE of the listed effects resolve — no draw, no heal, and NO
#// flip. The leader is exhausted (the action was taken) and stays on the Heroism face.
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myBaseDamage:5}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1LEADER:NOTDEPLOYED
P1BASEDMG:5
P1DECKCOUNT:2

---

# Heroism_DoesNotProvideVillainy
#// TWI_017 "Flipatine" (HEROISM face) provides Cunning+Heroism but NOT Villainy — proving the printed
#// all-three aspect list is NOT granted wholesale. A Villainy card (SOR_128 Aggression,Villainy) under a
#// Vigilance base pays the FULL penalty on BOTH pips (+4), costing 5 (6→1). If the leader wrongly provided
#// Villainy, it would cost 3 (→3).
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myResources:6;handCardIds:SOR_128}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1RESAVAILABLE:1

---

# Heroism_ProvidesHeroismAspect
#// TWI_017 "Flipatine" (HEROISM face) provides Cunning + Heroism. LAW_180 (Aggression,Heroism, cost 1)
#// played under a Vigilance base: Heroism is waived by Palpatine, only the Aggression pip is unmatched
#// (+2), so it costs 3 (6→3 resources). If Heroism were NOT provided it would cost 5 (→1).
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myResources:6;handCardIds:LAW_180}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_180
P1RESAVAILABLE:3

---

# Villainy_ConditionMet_TokenBaseFlip
#// TWI_017 "Flipatine" (VILLAINY face, Deployed=true, no arena unit) — Action [Exhaust]: If you played a
#// Villainy card this phase, create a Clone Trooper token, deal 2 to each enemy base, then flip back. P1
#// plays a Villainy unit (SOR_128) to arm SWU_PLAYED_VILLAINY, then uses the Action: creates a Clone
#// Trooper (TWI_T02), deals 2 to P2's base, and flips to the Heroism face (Deployed=false). Stays exhausted.
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true;myResources:4;handCardIds:SOR_128}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_T02
P1LEADER:EXHAUSTED
P1LEADER:NOTDEPLOYED

---

# Villainy_ConditionUnmet_UsableNoEffect
#// TWI_017 "Flipatine" (VILLAINY face) — Ruling 2 on the Villainy side: usable with no Villainy card
#// played this phase, but no effects resolve — no Clone token, no base damage, and NO flip. The leader is
#// exhausted and stays on the Villainy face. Also confirms a flipped Palpatine is NOT an arena unit
#// (P1GROUNDARENACOUNT:0 despite Deployed=true).
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true}
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1LEADER:DEPLOYED
P2BASEDMG:0
P1GROUNDARENACOUNT:0

---

# Villainy_DoesNotProvideHeroism
#// TWI_017 "Flipatine" (VILLAINY face, flipped) provides Cunning+Villainy but NOT Heroism. A Heroism card
#// (LAW_180 Aggression,Heroism) under a Vigilance base pays the full +4, costing 5 (6→1). Proves the flip
#// swapped the provided alignment away from Heroism (it would cost 3 if Heroism were still provided).
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true;myResources:6;handCardIds:LAW_180}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_180
P1RESAVAILABLE:1

---

# Villainy_ProvidesVillainyAspect
#// TWI_017 "Flipatine" (VILLAINY face, flipped) provides Cunning + Villainy (NOT Heroism). SOR_128
#// (Aggression,Villainy, cost 1) under a Vigilance base: Villainy is waived by Palpatine, only Aggression
#// is unmatched (+2), so it costs 3 (6→3). Proves the flip actually toggled the provided alignment to
#// Villainy. (Also: no phantom leader unit — the played unit is the only ground unit.)
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true;myResources:6;handCardIds:SOR_128}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1RESAVAILABLE:3

---

# FourSeats_VillainyFaceHitsEVERYEnemyBase
#// TWI_017 (VILLAINY face) — "deal 2 damage to EACH enemy base". "Each" is a fan-out, not a single
#// target, so at four seats BOTH opposing bases take 2. The clause used to deal to OtherPlayer() alone,
#// which silently spared the far opponent; this section fails on any single-seat form. Teammate P3 and
#// the caster's own base must be untouched — "enemy" is the qualifier that keeps them out.

## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true;myResources:4;handCardIds:SOR_128}
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
SEATCOUNT:4
P2BASEDMG:2
P4BASEDMG:2
P3BASEDMG:0
P1BASEDMG:0

---

# Villainy_PlayedUpgrade_CountsAndFlipsBack
#// ⚠ BUG REPORT (game 506505, 2026-09-17): "now the Villainy side flipping back to Heroism is failing —
#// i played Craving Power, so it should have counted to satisfy his condition."
#// LOF_091 Craving Power is a Command/VILLAINY **Upgrade**. The card says "if you played a Villainy
#// CARD this phase" — any type. But SWU_PLAYED_VILLAINY was armed inside the UNIT-ENTRY branch of the
#// play pipeline only (its own comment: "an event, an upgrade, and a Piloting card played as a pilot do
#// not" reach it), so an upgrade or event armed nothing and the Villainy face could never flip back.
#// The existing Villainy_ConditionMet_TokenBaseFlip section plays a UNIT, which is why it always passed —
#// same shape, different funnel.
#// Craving Power attaches to a friendly unit and deals damage equal to its power, so SOR_032 (2/3) is
#// seeded as the host and the 2 damage lands on P2's unit; the Action then flips Palpatine back.
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true;myResources:6;handCardIds:LOF_091}
P1OnlyActions: true
WithP1GroundArena: SOR_032:1:0
WithP2GroundArena: SOR_034:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1LEADER:NOTDEPLOYED
P2BASEDMG:2
P1GROUNDARENAUNIT:1:CARDID:TWI_T02

---

# Villainy_PlayedEvent_CountsAndFlipsBack
#// The same defect through the EVENT funnel: SOR_138 Force Lightning is an Aggression/VILLAINY event
#// (cost 1). Playing it must arm the condition, so the Action creates the Clone Trooper, deals 2 to each
#// enemy base and flips back to the Heroism face.
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true;myResources:4;handCardIds:SOR_138}
P1OnlyActions: true
WithP2GroundArena: SOR_034:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1LEADER:NOTDEPLOYED
P2BASEDMG:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T02

---

# Villainy_PlayedNonVillainyUpgrade_DoesNotCount
#// The CONTROL for the two sections above: a NON-Villainy upgrade must NOT arm the condition, so the
#// Action resolves nothing and the leader stays on the Villainy face. Without this, widening the flag to
#// "any card play" could pass by arming on every play whatsoever.
#// LAW_180 is the non-Villainy card the sibling aspect sections already use.
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true;myResources:6;handCardIds:LAW_180}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1LEADER:DEPLOYED
P2BASEDMG:0
