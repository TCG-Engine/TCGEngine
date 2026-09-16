# Luke (JTL) DV opener — Claude's attempt

> ⚠ **"Luke DV" is ambiguous — there are TWO, and both play Green Data Vault.**
> - **Luke (JTL)** — *Luke Skywalker, Hero of Yavin* (JTL_012). **This deck.** Soft aggro / midrange SPACE.
>   94 entries, 52.5% over 575 matches.
> - **Luke (ASH)** — *Luke Skywalker, I Can Save Him* (ASH). A different deck: grindy midrange blue-green Hero,
>   defensive, heal-based. 35 entries, 44.8% over 221 matches.
>
> They share the archetype key `Luke · Green Data Vault` except for the set tag, so dropping the tag merges two
> decks that are 7.7 points apart and play nothing alike. Everything below — and every Luke DV matchup number
> quoted in this session — is **Luke (JTL)**.

⚠ **This is INFERENCE, not ground truth.** Written by Claude from the decklist and card text alone (2026-09-16),
as a counterpart to the owner-written guides in [bot-openers-research.md](bot-openers-research.md). Those are
knowledge; this is a hypothesis. Where the two disagree, the owner's file wins — and the disagreement is the
interesting part, so corrections here are worth more than agreement.

Deck: `normal_luke_datavault` — Luke Skywalker, Hero of Yavin (JTL_012) · Green Data Vault.
Source list: melee.gg 438966, #6 of 72 (5-2-1).

## What the deck is

A **space Fighter deck with a small ground top-end**. Every one of its 13 space units has the Fighter trait,
which is the hinge of the whole deck:

- **Leader front side** — `Action [Exhaust]: If you attacked with a Fighter unit this phase, deal 1 damage to a
  unit.` A free ping every round, but only *after* a Fighter has attacked. **Attack before using the Action.**
- **Leader Epic Action (6 resources)** — deploy as a 5/6 ground unit, **or** as an upgrade (+4/+5) on a friendly
  Vehicle *without a Pilot*. On a Fighter the attached unit gains `On Attack: You may deal 3 damage to a unit.`
- Data Vault = 33 HP and a 60-card minimum.

**It RACES** (owner, 2026-09-16). This is a **soft aggro** deck, not a grind deck — my first read that the 60-card
Data Vault build wanted to trade early and win on card quality was wrong. What the extra HP and the top-end buy is
the ability to *survive past the flip turn*: Admiral Ackbar refills the board after a race has emptied it. So the
plan is pressure first, with a second wind, rather than attrition.

**The two Plot cards** (played from resources when the leader deploys, paying cost): **Cinta Kaz** (6 — 5/5
ground, `When Played: You may attack with a unit`) and **Sudden Ferocity** (3 — a +3/+0 upgrade).

---

## Luke · Green Data Vault | Midrange space aggro (Plot Cinta Kaz → Luke pilot)
<sub>`normal_luke_datavault` — Luke Skywalker, Hero of Yavin - Data Vault</sub>

Generally want to resource **one Cinta Kaz and one Sudden Ferocity** to Plot on the flip turn, plus Kelleran Beq
(7) — it is the most expensive card in the deck and the least castable. Note 6 + 3 = 9 > the 6 resources the flip
turn has, so the two Plot cards are **alternatives, not a package**: bank one of each and choose at flip time.

### Turn 1
- **Red Squadron Y-Wing — the best T1 overall** (owner). 1/3 reads as a weak body, but `On Attack: 3 indirect to the
  defending player` is unpreventable damage that ignores blockers entirely, and in a racing deck that is the point.
  I had this as a matchup-dependent alternative; it is the default
- **Jedi Starfighter into Vader yellow** (owner) — 1/4 walls that deck's small Fighters and the On Attack ping picks
  off its 1-HP tokens
- Phoenix Squadron A-Wing — 3/2, the most raw power for 2. (My original first pick; demoted — raw power is not what
  this deck's T1 is for)
- two Crackshot V-Wings if both are in hand: the second sees a friendly Fighter, so it skips its own self-damage —
  2/1 + 2/2 across two bodies for 2 resources
- a lone Crackshot V-Wing is the weakest of these: with no other Fighter it damages itself down to 2/1

### Turn 2
- Red Five — 3/4 for 3 with `On Attack: 2 damage to a damaged unit`, which combos with anything that chipped last turn
- Blue Leader for Ambush if there is a space unit worth killing right now; otherwise it is a vanilla 3/3
- otherwise a 2-drop plus a 1-drop to keep adding bodies
- **attack with a Fighter first, then use Luke's leader Action** — the Action is dead until a Fighter has attacked
  this phase, and the free 1 damage often finishes what the attack started

### Turn 3
- Danger Squadron Wingmen (4/5) is the best body; the Advantage token On Attack also pushes base damage
- Resistance Blue Squadron if you already have 3+ space units — `When Played` damage equal to your space unit count
  is removal that costs you no tempo
- Air Superiority if they have a relevant GROUND unit and you have more space units — this is the deck's only clean
  answer to a ground board, and it is conditional, so do not resource both copies away
- otherwise two 2-drops

### Turn 4
- Admiral Ackbar is the tempo play: defeat him to search the top 10 and play space units with combined cost ≤ 5
  for free — Red Five + an A-Wing off one card
- Kit Fisto's Aethersprite (4/5 Saboteur) if they have gone tall with upgrades — it defeats any number of them
- **Chewbacca is not a turn-4 pilot** (owner). He is good as a pilot **early** — the 3-cost Piloting upgrade making
  an early Fighter undefeatable is a real tempo play — or **late, after the flip turn, as a pump**. By turn 4 that
  window has closed: *"if you've missed the window to early Chewbacca, then he's an auto-resource."* So the turn-4
  question is not "protect the pilot slot from Chewbacca", it is "Chewbacca is resource fodder by now"
- leave a **ready, unpiloted Fighter** for turn 5 — ideally **Red Five**, so the pilot bonus lands on a body that
  already has its own On Attack (see the flip turn: this is the Hotshot target)

### Turn 5 — flip turn
The Epic Action gates on *controlling* 6 resources and spends none, so the whole pool is still available to pay
for Plot — but only if you **deploy before you spend**. Any ordinary play made first takes the Plot budget with it.

- **Order: deploy → Plot → attack.** Deploy Luke as an upgrade onto a ready, unpiloted Fighter. That unit becomes
  a leader unit at +4/+5 and gains `On Attack: deal 3 damage to a unit`
- **Plot Cinta Kaz (6) — the default** (owner): *"Cinta is overall the better play to get Luke's damage
  guaranteed."* Her `When Played: You may attack with a unit` is a free attack outside your action, so the
  freshly-piloted Fighter swings immediately and Luke's granted 3 damage is **guaranteed to happen this turn**
  rather than depending on surviving to your next attack. That guarantee is the reason to prefer her, not the 5/5
  ground body. Costs the entire 6
- **Plot Sudden Ferocity (3)** is the alternative when you want 3 resources left up — +3/+0 on the piloted Fighter,
  with 3 for a Red Five or an Air Superiority. Weaker by default, because it does not force the damage through
- **Hotshot Maneuver (1) goes on Red Five with Luke piloting it** (owner) — that specific pairing. Red Five's own
  `On Attack: 2 damage to a damaged unit` plus Luke's granted `On Attack: 3 damage` means the unit has TWO On Attack
  abilities, so Hotshot deals 2+2 to two different enemy units *and then attacks*, triggering both again. This is
  why Red Five is the Fighter to hold back on turn 4
- **deploy Luke as a ground UNIT instead** when you have no unpiloted Fighter worth the upgrade, or when a 5/6 body
  is what the board actually needs — putting +4/+5 on a 1-power chump is how you waste the flip
- ⚠ a piloted Fighter is one removal spell away from losing both halves. Against a deck holding removal, consider
  attacking with everything else first so you are the last actor, and deploy into a board they can no longer answer

---

## Scorecard — what the owner corrected (2026-09-16)

All five of my flagged uncertainties were answered. Verdict: *"your analysis was mostly good."* Recording which
parts of an inferred guide held up, because it calibrates how much to trust the next one.

| # | my read | owner's answer | verdict |
|---|---|---|---|
| 1 | Cinta = all-in closer, Ferocity = flexible | **Cinta is simply better** — it *guarantees* Luke's damage | right shape, wrong default |
| 2 | Hotshot is the ceiling | agreed, and specifically **on Red Five with Luke piloting** | right, under-specified |
| 3 | A-Wing best T1 (3/2 power) | **Y-Wing is best T1**; Jedi Starfighter into Vader yellow | **wrong** |
| 4 | don't let Chewbacca take the pilot slot on T4 | Chewbacca is a pilot **early** or a post-flip pump; by T4 he is an **auto-resource** | **wrong premise** |
| 5 | Data Vault ⇒ grind deck | **it races** — soft aggro, with Ackbar to refill past the flip | **wrong** |

**The pattern in my three misses:** I read the deck off its *statistics* — raw power for T1, high HP and 60 cards
for a grind plan, the pilot-slot restriction as a turn-4 constraint. The owner reads it off its *plan*: the deck
races, so unpreventable indirect beats raw power on T1, and Chewbacca's value is a window in time rather than a
slot on a board. Card text supports both readings; only the plan distinguishes them, and the plan is not in the
decklist.

That is worth keeping in mind for the bot: the heuristic stack reasons from statistics almost exclusively.

## Still open

- Does the **racing** read change which Fighter holds back on turn 4? Red Five is the Hotshot target, but a racing
  deck may simply want it attacking every turn from the moment it lands.
- Is there a branch point where Luke should deploy as a **ground unit** rather than a pilot? I listed "no unpiloted
  Fighter worth it", but that may never come up in a deck that always has Fighters.
