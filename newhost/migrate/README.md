# Old box → new box migration (merge)

The Hostwinds box (XAMPP, locked 2026-10-21) holds every account, deck, rating and stat up to the lockout. The new DigitalOcean box
has been live since ~2026-10-22 with its own accounts, decks and games. Owner decision 2026-10-24: **merge — keep both.**

Scripted, not a Mod-page button. The old box was taken down over a PHP remote-code-execution hole, so its web stack is the part we
trust least, a new button could not be deployed to it without shell access anyway, and a dump of the whole DB over one HTTP request
hits timeouts. With SSH, a script is safer and verifiable.

## The rule that drives the merge: old IDs keep their numbers

Old deck IDs are in links people have shared for months (`…gameName=<id>`, swustats.net/deck/…); old user IDs are what every stats row
points at. The new box's rows are a few days old. So **old data keeps its IDs, and the new box's rows are renumbered above the old
maximum.** The new box started its counters from scratch — `Games/GameIDCounter.txt` starts at 101 and every AUTO_INCREMENT at 1 — so
the new box's IDs collide with the old box's OLDEST real decks and accounts: a collision is the normal case, not an edge case.

What carries an ID:
- **User ID** — `users.usersId`, referenced as `usersId` (favoritedeck, favoritedeckmatchup, usercosmetic, lastdeck, swustats_links),
  `userId` (glicko_ratings, glicko_penalties), `winnerUserId`/`loserUserId` (glicko_results), `user_id` (oauth_*), `userID` /
  `opponentUserID` (matchhistory, teaminvite), `assetOwner` (ownership), `playerId` (savedsettings), `blockingPlayer`/`blockedPlayer`
  (blocklist), `ownerID`/`invitedBy` (team, teaminvite), `accountId` (swusim_bug_reports), `WinningPID`/`LosingPID` (completedgame).
- **Deck ID** — a FOLDER `<Root>/Games/<id>/` plus `ownership.assetIdentifier`, referenced by deckstats, carddeckstats,
  opponentdeckstats, opponentnamedbasestats, assetversions/assetautoversions/assetversionstats (`assetID`), matchhistory.deckID,
  deck_game_raw_data.deckID, and inside strings: `favoritedeck.decklink`, `favoritedeckmatchup.decklink`, `lastdeck.deckInput`.
- **Team ID** — team.teamID, users.teamID, teaminvite.teamID.
- Row IDs nobody references (matchhistory.matchHistoryID, glicko_results.id, assetautoversions.versionID…) are re-inserted without the
  ID and get a fresh one.

## Phases

| # | Where | Script | Changes anything? |
|---|---|---|---|
| 0 | new box, NOW | `inventory.sh` | no — row counts, max IDs, deck folders and counters per site |
| 1 | old box, over SSH | `export-old-box.sh` | no — dumps + tars into a directory on the old box |
| 2 | new box | `stage-on-new-box.sh` | loads the old dumps into `<db>__old` staging databases, never the live ones |
| 3 | new box | review (in `inventory.sh --compare`) | no — what the merge would do, and rows that look tampered with |
| 4 | new box, maintenance window | merge tool (NOT BUILT YET) | yes — backup first, dry run first |
| 5 | new box | `inventory.sh` again | no — verify |

Phase 4 is built after phase 0 shows how big the collision really is and the owner answers the open questions below.

## Compromised-box precautions (built into phases 1–3)

- **No stored code comes across.** Dumps are data + table definitions only (`--skip-triggers --skip-routines --skip-events`); a trigger
  or event planted on the old server would otherwise run on the new one.
- **No PHP files, no configs, no secrets come across.** The old box's API keys, DB passwords, OAuth client secrets and Patreon/Discord
  tokens must be treated as stolen: rotate them; `export-old-box.sh` does not copy them.
- **Review before merge.** Phase 3 lists accounts and rows changed in the days around the lockout, any user flagged as a mod, and text
  columns holding `<?php` / `<script`, so the owner can decide the cutoff before anything touches live data.

## Open questions for the owner (needed before phase 4)

1. Same person on both boxes: is a **matching email** the same account (merge the new-box account's decks/ratings into the old one)?
2. **Username taken on both** with different emails: rename the new-box account (e.g. `name_2`) and tell them?
3. **Ratings** for a user who played on both: keep the old Glicko rating, or replay the new-box results on top of it?
4. Which sites are in scope (the old box may host only SWUStats + Petranaki)?
5. Cutoff: take old data up to the lockout, or exclude anything after a "suspicious from" time phase 3 finds?

## Running it

```bash
# Phase 0 — on the new box (read-only):
sudo newhost/migrate/inventory.sh > ~/inventory-new.txt

# Phase 1 — on the old box (read-only), then pull the result to the new box:
scp newhost/migrate/export-old-box.sh root@OLD:/root/
ssh root@OLD 'bash /root/export-old-box.sh'          # → /root/tcg-export-<UTC>/  (prints the path)
rsync -av root@OLD:/root/tcg-export-<UTC>/ /var/backups/tcg-old/   # run on the new box

# Phase 2 — on the new box:
sudo newhost/migrate/stage-on-new-box.sh /var/backups/tcg-old

# Phase 3 — on the new box (read-only): collisions, shared accounts, moderator names, suspicious rows:
sudo newhost/migrate/inventory.sh --compare > ~/merge-review.txt
```

Tested 2026-10-24 against throwaway containers (MariaDB 10.4 as the XAMPP box, 10.11 as the new box) with a copy of the dev
`swudeck` data: the export skipped a planted trigger, the live sim games and an unrelated decoy file; root with and without a
password both worked; staging matched every row count, refused a dump carrying a trigger before loading anything, refused to
reload without `--replace`; `--compare` reported ID collisions, the shared email, the shared username, a moderator name held by a
different email on each box, a planted `<script>` username, and the overlapping deck folders. Not yet run on the real boxes.
