# Deployed_NonSeparatistThenSeparatist_StillExploit5
#// TWI_005 Count Dooku — DEPLOYED: Non-Separatist play does NOT consume the flag.
#// Deployed Dooku attacks → arms SWU_DOOKU_NEXT_SEPARATIST_EXPLOIT flag.
#// P1 then plays SEC_080 Imperial Dark Trooper (cost 2, Villainy, Imperial trait — NOT Separatist).
#// The flag must survive that play (non-Separatist card).
#// P1 then plays TWI_038 Providence Destroyer (Separatist) → still receives full Exploit 5
#// (2 printed + 3 from Dooku), proving the flag was not consumed by the non-Separatist play.
#//
#// SEC_080 (Imperial Dark Trooper):
#//   Cost 2, Ground, Command+Villainy aspects, Imperial/Droid/Trooper traits (NOT Separatist).
#//   No Exploit; plays straight to ground arena without any AnswerDecision.
#//   Dooku (Command+Villainy) covers both aspects fully; no penalty.
#//
#// TWI_038 after flag still armed: Exploit 5 → defeat 5 TWI_T01 Battle Droids → cost 0.
#//
#// Arena state before TWI_038 play (after SEC_080):
#//   Ground indices: 0-4 = TWI_T01 × 5, 5 = Dooku, 6 = SEC_080
#// Exploit 5 multichoose: defeat indices 0-4 (the 5 Battle Droids); skip 5 (Dooku) and 6 (SEC_080).
#// After defeats: ground = [Dooku (idx 0), SEC_080 (idx 1)]; space = [TWI_038 (idx 0)].
#//
#// Resource math (DeployLeader is FREE — epic action gate, no payment):
#//   Start: 14 ready resources. Gate: 14 ≥ 7 → deploy allowed.
#//   After DeployLeader: 14 remaining (no cost paid).
#//   After SEC_080 (cost 2): 12 remaining.
#//   After TWI_038 played for 0 (Exploit 5): 12 remaining.
#//   P1RESAVAILABLE:12 confirms full Exploit-5 discount (Exploit-2 would cost 4 → only 8 left).

## GIVEN
CommonSetup: bgk/ggk/{
  myLeader:TWI_005
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14:SOR_095
WithP1Hand: SEC_080
WithP1Hand: TWI_038
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:5:BASE
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2&myGroundArena-3&myGroundArena-4

## EXPECT
P1RESAVAILABLE:12
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_005
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:TWI_038
P1LEADER:EPICUSED

---

# Deployed_OnAttack_NextSeparatistExploit5
#// TWI_005 Count Dooku — DEPLOYED (Leader Unit): On Attack arms SWU_DOOKU_NEXT_SEPARATIST_EXPLOIT flag.
#// The next Separatist card played gains +3 Exploit (additive with printed Exploit).
#// Target: TWI_038 Providence Destroyer (Space, cost 8, Vigilance+Villainy, printed Exploit 2).
#// Effective Exploit = 2 + 3 = 5.  Five TWI_T01 Battle Droid tokens (cost 0, 1/1, Ground) are
#// friendly fodder in the ground arena at indices 0-4.  Dooku deploys and is placed at index 5.
#//
#// Deploy sequence:
#//   DeployLeader — epic action, NO resource cost. Gate requires having ≥ 7 total resources.
#//   Dooku enters ground arena at index 5 (Battle Droids occupy indices 0-4).
#//   AttackGroundArena:5:BASE → On Attack fires automatically; flag is armed. No AnswerDecision.
#//   PlayHand:0 → TWI_038 from hand (only card); Exploit 5 MZMULTICHOOSE (0..5) appears.
#//   Defeat all 5 Battle Droid fodder (indices 0-4): 5 × 2 = 10 discount → cost = max(0, 8-10) = 0.
#//
#// Aspect coverage:
#//   TWI_038 requires Vigilance + Villainy.
#//   SOR_020 (Vigilance base) covers Vigilance; Dooku leader unit (Command+Villainy) covers Villainy.
#//   No aspect penalty.
#//
#// Resource math (DeployLeader is FREE — epic action gate, no payment):
#//   Start: 12 ready resources. Gate check: 12 ≥ 7 → deploy allowed.
#//   After DeployLeader: 12 remaining (no cost paid).
#//   After TWI_038 played for 0 (Exploit 5, all 5 fodder defeated): 12 remaining.
#//   P1RESAVAILABLE:12 confirms the full Exploit-5 discount (Exploit-2 would cost 4 → only 8 left).
#//
#// Ground arena after play: only Dooku remains (index 0). All 5 Battle Droids defeated.
#// Space arena: TWI_038 at index 0.

## GIVEN
CommonSetup: bgk/ggk/{
  myLeader:TWI_005
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12:SOR_095
WithP1Hand: TWI_038
WithP1GroundArena: TWI_T01
WithP1GroundArena: TWI_T01
WithP1GroundArena: TWI_T01
WithP1GroundArena: TWI_T01
WithP1GroundArena: TWI_T01

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:5:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2&myGroundArena-3&myGroundArena-4

## EXPECT
P1RESAVAILABLE:12
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_005
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:TWI_038
P1LEADER:EPICUSED

---

# Leader_PlaysSeparatist_GrantsExploit1
#// TWI_005 Count Dooku -- Leader Action [Exhaust]: Play a Separatist card from hand.
#// It gains Exploit 1. Dooku is UNDEPLOYED (leader side).
#// P1 has exactly one Separatist in hand: TWI_230 Super Battle Droid (cost 3, Villainy,
#// Ground, no printed abilities -- truly vanilla Separatist Droid Trooper). One friendly
#// fodder unit (SEC_080, ready) in the ground arena.
#// UseLeaderAbility -> PASSPARAMETER auto-resolves TWI_230 -> TWI005_DOOKU_PLAY grants
#// gPlayGrantedExploit=1 -> SWUBeginPlayCard triggers Exploit 1 MZMULTICHOOSE (max 1).
#// Player defeats the fodder (myGroundArena-0) -> discount 2 -> cost = max(0, 3 - 2) = 1.
#// Dooku (Command+Villainy) + SOR_023 (Command) cover TWI_230's Villainy; no penalty.
#// Starting with 5 ready resources; 4 remain after paying 1 (vs 2 remaining at full cost 3).
#//
#// Key tokens learned:
#//   UseLeaderAbility -- fires leader-side [Exhaust] action
#//   PASSPARAMETER auto-fires when exactly 1 Separatist in hand (no AnswerDecision needed)
#//   Exploit MZMULTICHOOSE answer format: "myGroundArena-N" to defeat; "-" to decline

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:TWI_005;
  myBase:SOR_023
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5:SOR_095
WithP1Hand: TWI_230
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_230
P1RESAVAILABLE:4

---

# StacksWithPrintedExploit2_DefeatThree
#// TWI_005 Count Dooku — Leader Action [Exhaust]: Play a Separatist card from hand.
#// It gains Exploit 1. Here the target is TWI_037 Droideka Security (printed Exploit 2),
#// so effective Exploit = 2 + 1 = 3. Dooku is UNDEPLOYED.
#// P1 has exactly one Separatist in hand: TWI_037 (cost 6, Vigilance+Villainy, Exploit 2).
#// Three friendly fodder units (SEC_080, ready) in the ground arena.
#// UseLeaderAbility → PASSPARAMETER auto-resolves TWI_037 → TWI005_DOOKU_PLAY grants
#// gPlayGrantedExploit=1 → Exploit 3 MZMULTICHOOSE (min 0, max 3).
#// Player defeats all 3 fodder → cost = max(0, 6 − 6) = 0 → free.
#// Aspect coverage: TWI_005 (Command+Villainy) + SOR_020 (Vigilance) covers Vigilance+Villainy
#// for TWI_037; no aspect penalty.
#// Starting with 8 ready resources; 8 remain after the free play, proving the discount.

## GIVEN
CommonSetup: bgk/ggk/{
  myLeader:TWI_005
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8:SOR_095
WithP1Hand: TWI_037
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_037
P1RESAVAILABLE:8

---

# Leader_AffordableONLYViaTheExploitItGrants_IsStillOffered
#// ⚠ THE REPORTED BUG (player, 2026-09-21): "a friend tried to play a 3 drop separatist with the action
#// t1 with a unit out and 2 resources and the action did nothing."
#//
#// This is Leader_PlaysSeparatist_GrantsExploit1 with 2 resources instead of 5 — the ONE difference that
#// matters. The Action grants Exploit 1, worth 2 resources (`$exploitDiscount = 2 * $count`), so a 3-cost
#// Separatist costs 1 and IS payable from 2 resources. But `_SWUSeparatistHandPlayables` built its pool
#// with `$cost <= $ready` using the PLAIN cost: `$gPlayGrantedExploit` is not set until the play actually
#// begins, long after the pool is built. So the card was filtered out, the pool came back empty, and the
#// leader ability fell through to SWUAfterAction — the action was SPENT and nothing happened.
#//
#// ⚠ Why nothing upstream caught it: SWULeaderActionAffordable deliberately does NOT gate TWI_005
#// (CR 6.4.587.c — the [Exhaust] cost changes game state, so the Action stays usable). That is correct,
#// and it means the ONLY gate is this pool. A too-narrow pool therefore reads to the player as a dead
#// button rather than as a refusal.
#//
#// Same board as the 5-resource section otherwise: Dooku (Command+Villainy) + SOR_023 (Command) cover
#// TWI_230's Villainy, so no aspect penalty and the cost really is 3. Defeating the SEC_080 fodder takes
#// it to 1; 2 - 1 = 1 resource left.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:TWI_005;
  myBase:SOR_023
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2:SOR_095
WithP1Hand: TWI_230
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
# The fodder was exploited and the Droid took its place.
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_230
# Paid 1 of 2, not 3 — and NOT "nothing happened", which is what the pool bug produced.
P1RESAVAILABLE:1
P1HANDCOUNT:0

---

# Leader_NoFodderToExploit_TheExpensiveCardIsNotInTheOFFER
#// ⚠ THE NEGATIVE CONTROL for the section above — and it has to read the POOL, not the outcome.
#//
#// A first cut of this section had no fodder, one unaffordable card, and asserted "it stays in hand".
#// That was DECORATIVE: mutating the fodder cap to a constant (always grant the discount) left it GREEN,
#// because the card is admitted, auto-resolves, and then simply fails to pay — so it stays in hand
#// either way. The outcome is identical; only the OFFER differs. Mutation caught it, nothing else could.
#//
#// So: three Separatists in hand and NO fodder. The two 2-cost ones are affordable outright; TWI_230 at
#// cost 3 is not, because with no unit to defeat Exploit is worth 0. The pool must be exactly the first
#// two, which makes it an MZCHOOSE we can read. The decision is left PENDING on purpose.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:TWI_005;
  myBase:SOR_023
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2:SOR_095
WithP1Hand: TWI_080
WithP1Hand: TWI_080
WithP1Hand: TWI_230

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1HASDECISION
# The 3-cost Droid (myHand-2) is absent: no fodder means no Exploit discount to reach it.
P1SELECTABLEEXACT:myHand-0&myHand-1
