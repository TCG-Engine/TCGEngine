# Galarian Meowth

Select **Galarian Meowth** in the deck menu. The list has 16 Pokemon, 32 Trainers,
and 12 Basic Metal Energy, with no Genesect. Import artwork and regenerate:

```sh
php DevTools/PokeSim/import.php --deck=PokeSim/Decks/galarian-meowth.txt
php DevTools/PokeSim/setup.php
php DevTools/PokeSim/meowth-tests.php
```

The bot starts Galarian Meowth when available and targets Treasure Rush for 70+
damage (seven cards in hand) on its first turn going second. Going first, its
earliest attack is turn two. Cheren grows the hand; Lillie's Determination resets
it to eight while six Prizes remain. Dudunsparce supplies later draw. The bot
builds Duraludon into Archaludon ex, uses Assemble Alloy to recover discarded
Metal Energy, and powers Mega Mawile ex as Gobble Down grows with Prizes taken.
Opening statistics track the 70+ Treasure Rush goal. Opening hands and Prize
placement can prevent the intended sequence.

The chosen printings are Galarian Meowth 30C 101, Dunsparce JTG 120,
Dudunsparce TEF 129, Budew PRE 4, Duraludon SCR 106, Archaludon ex SSP 130,
and Mega Mawile ex MEG 94. MEE 8 uses equivalent Crown Zenith Metal Energy art.
