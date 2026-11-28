# FaBSim commit scope — 22 November 2026

This topic branch publishes the accumulated FaBSim card fixes, native regressions and coverage/issue records through [High Roller](REPAIR_REVIEW_20261122.md). It includes the earlier uncommitted batches, not only the last repair. Previously committed framework and fixtures remain in the branch history.

The current qualified inventory is 4,202 scenarios: 3,075 two-live and 1,127 four-live; 1,350 tested primary identities and 1,224 representative groups. New files include 681 five-file fixtures; 11 existing Razor Reflex fixture sets also have previously qualified corrections. These are accumulated runtime-specific qualifications, not a new full-suite pass. High Roller’s three focused replays, two compatibility replays and component checks are detailed in the handoff. No full suite was run for publication.

Included metadata consists of canonical source specs, qualification/checkpoint receipts, the coverage/review/issue ledgers, necessary source/failure proof and reusable component checks. Raw `.recording-results.json`, `recording-runs/`, the volatile `last-recording-results.json`, and unreferenced intermediate/history files remain local and are excluded. Historical links to such recorder outputs are ancillary local evidence, not shipped runnable inputs. Absolute Windows workspace references in historical proofs also remain local references; they are not claims that those files ship. Nothing was deleted.

No Card Code response snapshot, credential/configuration file, generated output, unrelated PokeSim work or scratch `tmp/` file is added. Generated files remain ignored and must be produced by the existing generation workflow. High Roller’s cached native-generation and unverified live-Prepare boundary are stated in its handoff.

## Git line endings and historical hashes

Git’s existing attributes normalize committed text to LF, including fixture snapshots. The regression runner explicitly normalizes initial state/replacement strings and final-state comparison newlines. Saved raw-byte proof hashes still describe the original Windows recording context; they are not rewritten as if qualification ran on a fresh clone.

[Publication byte equivalence](review-evidence/publish-byte-equivalence-20261122.json) maps only inspected candidate files whose sole Git transformation is CRLF-to-LF. Each row is `[path, recorded_worktree_sha256, published_LF_sha256]`. This is a byte-conversion record, not new gameplay, semantic or full-suite evidence. Publication also normalizes repeated carriage returns and excess terminal blank lines in three historical Markdown reports; their words are unchanged. Other historical hash caveats remain unchanged. The staged blobs were checked against these candidate bytes before committing.

The commit is isolated on a topic branch. No default-branch update, merge, PR or deployment is requested. Review the diff and run the capped full-corpus command from the repair handoff before merging if desired.
