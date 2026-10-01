# AttackThenThreeAdvantage
#// ASH_184 Follow Me (Event, cost 1) — Attack with a unit. After completing the attack, give 3 Advantage
#// tokens to a unit. P1 has two ready units (SOR_095 ground, SOR_237 space); plays the event, attacks the
#// base with SOR_095 (P2 has no units → auto-targets base), then chooses to give 3 Advantage tokens to
#// SOR_095. The post-attack grant fires regardless of which unit attacked.
## GIVEN
CommonSetup: rrw/rrk/{myResources:1;handCardIds:ASH_184}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# GiveAdvantageToNonAttacker
#// ASH_184 Follow Me — the post-attack 3 Advantage may go to a unit that did NOT attack. SOR_095 attacks
#// the base, but P1 gives the tokens to the space SOR_237 instead.
## GIVEN
CommonSetup: rrw/rrk/{myResources:1;handCardIds:ASH_184}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# NoReadyAttacker_NoEffect
#// ASH_184 Follow Me — the event attacks with a unit first. With only an exhausted A-Wing (SEC_213) and no
#// legal attacker, playing it has no effect: no attack happens and no Advantage tokens are given.
## GIVEN
CommonSetup: rrw/rrk/{myResources:1;handCardIds:ASH_184}
WithP1SpaceArena: SEC_213:0:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P2BASEDMG:0
P1NODECISION

---

# AttackerAlreadyHasAdvantage_ShedFirst_KeepsTheThreeNew
#// Bug #1103 family. SOR_095 already carries one Advantage token (+1 → 4 power) when Follow Me attacks
#// with it. Two "attack ends" effects meet: the old token's "Defeat THIS upgrade" and Follow Me's "give 3
#// Advantage tokens to a unit" (here: the attacker). Only the token attached when the attack ENDED is
#// shed, so the attacker ends with exactly the 3 new ones — not 0 (the shed used to defeat every
#// Advantage on the unit, including the three that arrived after the attack ended).
#// Trigger order: the token shed resolves FIRST (old token gone), then Follow Me gives 3.
## GIVEN
CommonSetup: rrw/rrk/{myResources:1;handCardIds:ASH_184}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
P1NODECISION

---

# AttackerAlreadyHasAdvantage_GrantFirst_KeepsTheThreeNew
#// Bug #1103 family. SOR_095 already carries one Advantage token (+1 → 4 power) when Follow Me attacks
#// with it. Two "attack ends" effects meet: the old token's "Defeat THIS upgrade" and Follow Me's "give 3
#// Advantage tokens to a unit" (here: the attacker). Only the token attached when the attack ENDED is
#// shed, so the attacker ends with exactly the 3 new ones — not 0 (the shed used to defeat every
#// Advantage on the unit, including the three that arrived after the attack ended).
#// Trigger order: Follow Me resolves FIRST (4 tokens on the unit), then the shed defeats only the old one.
## GIVEN
CommonSetup: rrw/rrk/{myResources:1;handCardIds:ASH_184}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
P1NODECISION
