# Card-ability database patches (GrandArchiveSim)

The Grand Archive engine's per-card behaviour is generated from the **CardEditor ability database**
(`card_abilities` table, or a remote CardCodeService). Git does not track that data, so any fix that
needs a database row changed ships as a patch here and has to be applied to **each** environment
(your local DB, staging, prod) and followed by a regenerate.

## Apply

```bash
# 1. dry run: shows what would change, writes nothing
php DevTools/card-db-patches/apply-card-ability-patch.php --patch=DevTools/card-db-patches/ga-card-ability-fixes-verified.json

# 2. write it
php DevTools/card-db-patches/apply-card-ability-patch.php --patch=DevTools/card-db-patches/ga-card-ability-fixes-verified.json --apply

# 3. regenerate (the runner reminds you), then run the engine tests
php zzGameCodeGenerator.php rootName=GrandArchiveSim
php DevTools/RunIntegrationTests.php --root=GrandArchiveSim
```

The runner uses the same repository as the CardEditor, so it follows your configuration: local MySQL, or
a remote CardCodeService if `CARD_CODE_REMOTE_CONFIG` / the Generator Workspace connection file defines
one for `GrandArchiveSim`. `MYSQL_DATABASE_NAME`, when set, overrides the database it picks.

Every op is **idempotent** and reports `APPLIED` (already done), `PENDING` (will change), `BLOCKED`
(database matches neither the old nor the new state, so nothing is written) or, with `--apply`, `FIXED` /
`FAILED`. A write is only called `FIXED` after a re-read confirms it. Exit status is non-zero on
`BLOCKED` or `FAILED`. Rerunning is always safe.

## The patches

### `ga-card-ability-fixes-verified.json`

| Op | Card | Change | Why |
|---|---|---|---|
| `cleansing-reunion-macro` | Cleansing Reunion (`xpnjvt9y59`) | macro `Enter` -> `CardActivated` | An ACTION card's effect only runs from `$cardActivatedAbilities`; under `Enter` it never fired. |
| `strike-of-singularity-tag` | Strike of Singularity (`AMv1u54B2s`) | `soO3hjaVfN_DOUBLE` -> `AMv1u54B2s_DOUBLE` in the OnAttack code | The tag was Rending Flames' card id (copy-paste), so the damage-doubling clause never fired. |

Status: on the author's machine the generated macro code (`GeneratedMacroCode.php`, 2026-10-05) already
contains both corrections, and the matching fixtures pass (`cleansing-reunion-banish-graveyard`,
`strike-of-singularity-onattack-empty-hand-double-damage`). **Apply this patch on every other environment.** Without it those two
cards misbehave there even though the code in git is right.

### `ga-lorraine-pantheon-abilities-unverified.json`

Authors database rows for Charm of Anticipation (`vkL2RFh0yM`, `ActivateAbility` + Crowd's Favor
prereq) and Unity's Gale (`uUWsgLmyTk`, `CardActivated`), the two Lorraine Pantheon cards that had no rows.
Today they run on **hand-written stand-ins** in `GrandArchiveSim/Custom/GameLogic.php`
(`$activateAbilityAbilities["vkL2RFh0yM:0"]`, `$cardActivatedAbilities["uUWsgLmyTk:0"]`), and that is
correct and tested as it stands. **This patch is optional and unverified**:

- `GeneratedMacroCode.php` loads *after* `Custom/GameLogic.php`, so once these rows exist the generated
  bodies **replace** the stand-ins. Nobody has regenerated with them yet.
- After applying, regenerate and run the Charm of Anticipation / Unity's Gale fixtures
  (`DevTools/RunIntegrationTests.php --root=GrandArchiveSim --test=<name>`). If they fail, remove the rows
  in the CardEditor and regenerate; the stand-ins take over again.
- Once the Charm row is generated, `$CardActivateAbilityCountData["vkL2RFh0yM"]` comes from the generated
  code, so the special case `if($cardID === "vkL2RFh0yM" ...) $staticAbilityCount = 1;` in
  `DoActivatedAbility()` (`GameLogic.php`, near line 8815) becomes redundant and can be removed.
- Unity's Gale's `uUWsgLmyTk:0:Target-1` continuation stays hand-written either way.

## Self-test

```bash
php DevTools/card-db-patches/selftest.php
```

Runs every op type against an in-memory stand-in for the repository (dry run writes nothing, apply,
idempotent rerun, blocked, no-overwrite, revision conflict). **It does not touch a real database.** The
runner has not been run against a live MySQL or CardCodeService; run the dry run first and read the output.

## Notes

- This is why not the CardEditor's SQL export (`CardAbilitySqlTransfer`): that dump does `DELETE` + `INSERT`
  of **every** row for the root, which would overwrite unrelated work in the target database. These ops
  touch only the named cards.
- The three one-off scripts these ops came from (`DevTools/fix-cleansing-reunion-macro-classification.php`,
  `fix-strike-of-singularity-double-tag.php`, `fix-lorraine-pantheon-final-three-abilities.php`) are kept for
  history. Prefer this runner. Those scripts live under `DevTools/`, so they resolve their database as the
  app "DevTools" unless you set `MYSQL_DATABASE_NAME` or pass `rootName=GrandArchiveSim`; this runner sets it
  from the patch file.
- To add a patch: copy a JSON file, change `ops`, run the self-test, dry-run it, then apply.
