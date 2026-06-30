# Tests/Visual — visual-check schemas

Schemas in this folder exist **only to be loaded by hand in the Test Schema Editor**
(`zzTestSchemaEditor.php`) so a human can eyeball board rendering — icons, layout,
overlays, animations, etc. They assert nothing automatically.

## Ignored by regression

The regression endpoint (`zzRegressionSWUSim.php`) never runs these. Test discovery
only walks `Tests/Cases/`:

- `TestRunner` collects `*Test.php` files under `Tests/Cases/`.
- `Tests/Cases/SchemaBasedTest.php` registers a `test_*` function for every `.md`
  under `Tests/Cases/` (its `RecursiveDirectoryIterator` roots at `Tests/Cases/`).

`Tests/Visual/` is a sibling of `Tests/Cases/`, so neither mechanism sees it. No
opt-out marker is needed — just keep visual schemas here, not under `Cases/`.

## Format

Same GIVEN / WHEN / EXPECT schema as the `Cases/` `.md` files. For a pure
initial-state check, leave `## WHEN` empty — the editor sets up `## GIVEN` and shows
the board. `## EXPECT` is unused by the editor (kept only for documentation / in case
the schema is ever copied into `Cases/`).

To view one: open the Test Schema Editor, pick the `.md` via the file picker, and it
loads into the live SWUSim game iframe.
