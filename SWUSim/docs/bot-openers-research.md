# Ideal Openers for Decks

What a deck is *trying* to do on each of its first four turns — the cards it looks for, in priority order.

Owner-supplied ground truth for the SWUSim bots. Every bot test today is a synthetic micro-board, so nothing
here is derivable from the repo: this is the only record of what a real opening should look like.

**How it gets used.** Each deck's list becomes a check against the bot playing that fixture
(`SWUSim/Tests/BotFixtures/meta-2026-09/<fixture>.txt`): does it look for the same cards, in the same order,
by the same turn? A divergence is a place to look, not automatically a bug — several lines are usually
defensible. Where a divergence is clearly wrong, it becomes a unit test.

**Most useful notes**, beyond the card lists:
- *Why* a card is wanted (ramp / tempo / a trade / setting up a combo), where it isn't obvious.
- **Branch points** — "if they have X by turn 3, I stop hitting base and trade instead." These are the most
  valuable lines in the document: the bot currently never re-evaluates its plan mid-game, and a branch point
  is exactly the rule it's missing.
- What you'd resource, when it isn't just "the worst card in hand".
- Anything that changes on the play vs on the draw.

Partial is fine — a deck with only turns 1-2 filled is still usable. Leave a deck empty if you'd rather not
guess at it.

> **Start here:** `ahsoka_blue` and `luke_datavault`. Their head-to-head is the current
> investigation — real tournaments have it at 52.3% for Ahsoka, our bots at 19.0%, a 33-point miss.

---

## Ahsoka · Yellow 30HP | Aggro, mixed arenas / mostly space
<sub>`ahsoka_yellow` — Ahsoka Tano, Trust in the Force - Chopper Base</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---
---

## Chewbacca · Alliance Outpost | Hyper aggro
<sub>`chewbacca_outpost` — Chewbacca, Hero of Kessel - Alliance Outpost</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Greef Karga · Green Data Vault | Go-wide aggro / soft aggro
<sub>`greef` — Greef Karga, Gracious Magistrate - Data Vault</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---
---

## Aurra Sing · Green Data Vault | Midrange control (value trades)
<sub>`aurra_datavault` — Aurra Sing, Assassin - Data Vault</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Aurra Sing · Red 30HP | Hard control
<sub>`aurra_red` — Aurra Sing, Assassin - Dragonsnake Bog</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Dedra Meero · Blue Colossus | Hard control
<sub>`dedra_colossus` — Dedra Meero, Not Wasting Time - Colossus</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Krennic · Blue Splash | Credit ramp control (soft/midrange control)
<sub>`krennic_splash` — Director Krennic, Amidst My Achievement - Daimyo's Palace</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Lando · Blue 30HP | Credit ramp tempo soft control
<sub>`lando_blue` — Lando Calrissian, Full Sabacc - Fortress of the Great Mothers</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## The Mandalorian · Blue Colossus | Hard control with late-game bombs
<sub>`mando_colossus` — The Mandalorian, We Can't Keep Running - Colossus</sub>

this deck plays under curve an plays minimalistic to be able to take initiative and draw for Mando's leader side ability. the goal is to build a huge hand for 6R Hammerhead strikes and for Aggressive Negotiations closers.

### Turn 1
- if going first, then just take initiative and draw
- if opponent goes first, and they play something, then just take initiative and draw
- if opponent goes first, and they don't take initiative, then play something for 2R that can attack (so NOT Loth Wolf)

### Turn 2
- if you have both Reckless Sacrifice and a 1-drop Villainy unit in hand, and opponent's T1 play has no shield, then use Reckless Sacrifice. this will fully activate Anakin later
- otherwise, play a 2-drop so you can take initiative and draw
- however, if going against space, and they have two space units that both get cleared by "Let's Call it War" while you have initiative, then take that play instead 
- against Space decks, Green Leader is also a great 2-drop to play this round to still take initiative and draw

### Turn 3
- 3-drop control pieces like Crushing blow, Sentinels, Let's call it war
- do NOT auto-play Hera here unless she is the only 3-drop in hand
- it would actually be better to play a 1-drop Villainy unit for 3 with aspect penalty than play Hera at this time. 
- if their T1 play was a 2-cost ship and their T2 play was to put Chewbacca JTL pilot on it. then this is a good time to spend all 4R to use "The Tree Remembers" on that unit since it would be unwise to let them swing more than once with that normally unkillable unit

### Turn 4
- 4-drop control pieces in removal Tree Remembers or Direct Hit against space
- Zeb for 5R here is not bad if it can set up a double draw next round (Support attack with Zeb plus Mando's attack), so the leader ability whiff here is not as bad
- also good here would be to play a 2-drop plus Reckless Sacrifice
- Koska is another good choice if you lose your 2 or 3-drop unit at this time
- Crushing Blow is good here if no other play

## Turn 5
- ideal flip turn is to attack with another unit to get the most draw
- another good option is to stall out until they flip their leader if not already flipped, then use Rebellious Hammerhead to deal hopefully 6-8 damage to that leader and clearing it in one action
- otherwise, continue the defensive strategy of sentinels and softening up their units while stalling for 7R+ bombs
- if against space, you need to survive an hold onto Hyperspace Disaster to wipe their Space arena

---

## Piett · Blue 30HP | Capital Ship soft control
<sub>`piett_blue` — Admiral Piett, Commanding the Armada - Shield Generator Complex</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Thrawn · Green Data Vault | Midrange / soft control (bombs)
<sub>`thrawn_datavault` — Grand Admiral Thrawn, ...How Unfortunate - Data Vault</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Thrawn · Yellow 30HP | Midrange soft control (When-Defeated combo)
<sub>`thrawn_yellow` — Grand Admiral Thrawn, ...How Unfortunate - Mount Tantiss</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## The Armorer · Nabat Village | Midrange aggro go-tall (upgrades)
<sub>`armorer_nabat` — The Armorer, Steel Shapes Us - Nabat Village</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Greef Karga · Green Data Vault | Mixed midrange aggro go-wide
<sub>`greef_datavault` — Greef Karga, Gracious Magistrate - Data Vault</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Luke · Green Data Vault | Midrange space aggro (Plot Cinta Kaz → Luke pilot)
<sub>`luke_datavault` — Luke Skywalker, Hero of Yavin - Data Vault</sub>

### Turn 1
- ideal T1 is Red Squad Y-Wing
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Maul · Blue Force | Tempo / midrange (Force)
<sub>`maul_blueforce` — Darth Maul, Sith Revealed - Nightsister Lair</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Obi-Wan · Vergence Temple | Midrange go-wide Force
<sub>`obiwan_vergence` — Obi-Wan Kenobi, Courage Makes Heroes - Vergence Temple</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Piett · Red Splash | Capital Ship midrange
<sub>`piett_red` — Admiral Piett, Commanding the Armada - Stygeon Spire</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---

## Mother Talzin · Yellow Force | Aggressive midrange tempo (Force)
<sub>`talzin_force` — Mother Talzin, Power Through Magick - Crystal Caves</sub>

### Turn 1
- 

### Turn 2
- 

### Turn 3
- 

### Turn 4
- 

---
