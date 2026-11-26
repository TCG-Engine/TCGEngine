> **Historical checkpoint below.** The [latest20November continuation](REPAIR_CONTINUATION_20261120.md) has4,180 qualified scenarios,75/93 original records resolved in tested scope and18open. Dates/runtimes/limits below retain their historical scope.

> **Historical checkpoint below.** Superseded by the [20 November repair handoff](REPAIR_CHECKPOINT_20261120.md): 4,176 qualified scenarios;71/93 records resolved in tested scope;22 open. The service input block is resolved; Pox is partially repaired. Earlier runtimes, counts and blockers below describe their stated dates.

Current repair triage, 17 November 2026: **4,173 qualified scenarios;68/93 records resolved in tested scope;25 open.** [Grouped blockers, current fixes and validation](REMAINING_REPAIRS_20261117.md) is the current review handoff. Breadth is paused. Current runtime `5a8c088126ff8d9c581c7eb97e4b9cc77511e4011c8fd3faed3a5328b7b47379`. The previous checkpoint below is historical through the six earlier repairs.

---

Repair review checkpoint, 17 November 2026. **Breadth remains paused. Six additional existing issue records are qualified; this is not completion of all known repairs.**

**4,171 saved, qualified and registered scenarios: 3,074 duel + 1,097 genuinely four-live.** Saved inventory equals registry; no pending saved-only case. Coverage is 1,324/5,196 exact primary identities and 1,198/3,324 pitch-cycle or standalone groups (1,090 groups with four-live evidence). Pitch siblings and untested branches receive no credit. These totals preserve each case's actual historical qualification runtime; they are not a whole-suite pass on current `226b6bb82ab6fd9cf9d70254bf59762f846464765b221b06d53c3b662bc0db66`.

**66 of the original 93 records are resolved in their tested scope; 27 remain.** Records overlap shared defects and are not a count of distinct engine bugs. This continuation resolved the following existing failures, with correct native execution, exact saved review and one clean creation replay each:

| Record | Qualified branch | Native actions / saved assertions |
| --- | --- | --- |
| [155](review-evidence/expendable155-5bc9-qualified-saved.json) | Expendable Limbs Blue | 97 / 7,450 |
| [077](review-evidence/beatchest077-eea9-qualified-saved.json) | Assault and Battery Red / Beat Chest | 33 / 2,618 |
| [136](review-evidence/suraya136-1b19-qualified-saved.json) | Invoke Suraya Yellow | 7 / 732 |
| [180](review-evidence/bastion180-1bd9-qualified-saved.json) | Battlefront Bastion Blue | 32 / 907 |
| [181](review-evidence/scale181-933d-qualified-saved.json) | Scale Peeler | 33 / 1,662 |
| [164](review-evidence/dread164-226b-qualified-saved.json) | Dread Screamer Blue | 28 / 1,194 |

Expendable now keeps its next-Action permission through resets, expires it at the actual Action-end boundary and consumes it on accepted Banish play. Six numeric-only phase queries were corrected to existing `phase_is`; one durable final bank-absence check was added. Its early Prepare-grant/cancelled-reaction concern remains source-only and unqualified. Beat Chest now declares/reserves its discard, pays resource/AP assets first, then discards and earns a separately responseable Agility effect. Suraya retains the required selected Shield material; its already-Arena top transforms in place, avoiding redundant movement/material duplication. Bastion's sole-defend prevention and Scale's hit modifier now await fresh four-seat responses. Scale's supported thrown-dagger paths were retained through actual-caller controls. Dread runs its unchanged generated Prepare body, declares random quantity before assets, then performs real random-three banishing and earns go again only after payment. Only the named representative paths are Native-qualified.

The six qualified witnesses total 230 real inputs and 14,563 saved assertions. Two direct legacy cost replays (Rouse/Tome) passed after 077. Beat Chest and Sift passed directly affected compatibility replays at final runtime 226b. Components add assurance, not Native branch credit: 155 has 25 + 15 checks; 077 has 42; 136 has 9; 180 has 14; 181 has 27; 164 has 18 contracts and 3 deliberate counterfactual controls. Earlier failed/unqualified recordings and rejected source proposals remain outside passing coverage. Original mandatory no-save probes remain unchanged; repair completion witnesses are separate.

A separate **recorder diagnostics** correction preserves the primary setup/action error instead of crashing on uninitialized diagnostic getters. Two controlled setup failures and a throwing-getter check passed with 0 game actions and 0 fixtures. The retained reusable check is `C:\xampp\php\php.exe DevTools/FaB/CardRegression/test_recording_failure_diagnostics.php`. [Proof](review-evidence/recorder-preinitialization-diagnostics-20261117.json). This is test infrastructure, not another gameplay fix or card regression.

Current source changes are FaB-specific shared logic/cost/writer/trigger handling in `FaBSim/Custom/GameLogic.php`, `CardChoices.php`, `WTRCards.php`, `ROSCards.php`, plus set-specific `DTDCards.php`, `DTDRuntime.php`, `HVYCards.php`, `DYNCards.php`, `MSTCards.php`, `OUTRuntime.php`, `MONCards.php`. Test infrastructure is the existing recorder and its small new diagnostic check. **No repository `Core/`, canonical/builder, schema or generated files were written by this continuation.** [Focused source review](review-evidence/independent-repair-source-hygiene-20261117.json) binds all 12 changed source files to reviewed candidates and reports no new trailing whitespace/debug content. This does not review unrelated/prior worktree edits.

The known unexecuted 094 application remains **pending the specific approval answer**. Its same four functions/six edits are prepared against final source without applying or testing them: [current proposal/hold](review-evidence/ros094-rebased-after164-awaiting-approval-20261117.json). The scope only covers generic absent/empty/literal-ability ROS events; it does not admit named `iarBound` or automatically resolve every open timing record. All other previous denial holds remain untouched:073/102,089,090,144,176/177,184,188. No denied action was retried.

Generation input holds 091/147/157/158 remain: last configured read returned HTTP 403 and actual loopback read found mapped database `fabsim` absent (MySQL 1049). These unavailable operations were not retried. Restored authorized read access or an unmodified raw authored-row snapshot is needed. Other open records are 078, 093, 095, 097, 101, 103, 109, 125, 178, 179, 192, 195, 200; these retain declaration, interactive trigger, End-phase or bound-Zombie failures, not passing fixtures. [High Roller triage](review-evidence/highroller093-declaration-integration-triage-20261117.json) and [Burn triage](review-evidence/burn125-await-attribution-triage-20261117.json) explain why simple synchronous/late-target workarounds would be wrong. Earlier untested Smashing Item choice and other partial/source-only branches remain unqualified.

Git at this read: branch `main`, HEAD `b097e43e4de0369d44839fa95d3804aa1454ff1e`; **0 staged, 135 unstaged and 5,499 untracked entries in normal mode** (directories are entries, not individual file counts). The 13 PokeSim entries and all unrelated work remain untouched. Source/spec/fixture backups and complete focused logs remain under `C:\Users\maxim\Documents\Codex\2026-10-19\task\nov17-*`; exact paths/hashes are in proofs. This task did not stage, commit, push, deploy, reorganize fixtures or perform broad cleanup. Do not treat the entire existing dirty worktree as this six-repair diff.

No full suite or historical corpus replay was run. Broader validation remains user-owned; the bounded command appears below. Recommended next actions are review these six scoped repairs, answer the existing 094 question, restore authoring inputs, and assess the remaining 27 records before reopening breadth. This is a partial repair handoff, not a merge-ready/full-card claim.

The detailed checkpoint below retains earlier qualified scopes and dated history.

Current repair-only checkpoint, November 17, 2026. This replaces the continuation counts above the historical section below. **4,171 saved/qualified/registered scenarios: 3,074 duel + 1,097 four-live**, covering 1,324/5,196 exact primary identities and 1,198/3,324 pitch-cycle or standalone groups. Untested pitch siblings and branches receive no credit. Current source runtime `226b6bb82ab6fd9cf9d70254bf59762f846464765b221b06d53c3b662bc0db66`; these totals retain their actual historical qualification contexts, not a full-suite pass on this runtime.

**66 of the original 93 unresolved records are resolved in their tested scope; 27 remain.** Record counts overlap shared root causes. The current [issue ledger](card-regression-issues.json), [coverage lookup](coverage-additions-after218.json), and [machine checkpoint](review-evidence/repair-checkpoint-20261116.json) are authoritative for exact scenarios, proofs, scope and runtimes. Actual saved inventory at this read: 4171; saved-but-unregistered cases: none.

Resolved continuation record IDs: FAB-REG-071, FAB-REG-072, FAB-REG-105, FAB-REG-107, FAB-REG-112, FAB-REG-113, FAB-REG-114, FAB-REG-137, FAB-REG-193, FAB-REG-197, FAB-REG-202, FAB-REG-148, FAB-REG-153, FAB-REG-152, FAB-REG-145, FAB-REG-131, FAB-REG-159, FAB-REG-163, FAB-REG-162, FAB-REG-189, FAB-REG-096, FAB-REG-132, FAB-REG-104, FAB-REG-146, FAB-REG-151, FAB-REG-126, FAB-REG-108, FAB-REG-110, FAB-REG-117, FAB-REG-127, FAB-REG-130, FAB-REG-149, FAB-REG-111, FAB-REG-138, FAB-REG-139, FAB-REG-120, FAB-REG-122, FAB-REG-070, FAB-REG-123, FAB-REG-154, FAB-REG-201, FAB-REG-160, FAB-REG-121, FAB-REG-156, FAB-REG-190, FAB-REG-124, FAB-REG-079, FAB-REG-191, FAB-REG-196, FAB-REG-133, FAB-REG-074, FAB-REG-198, FAB-REG-098, FAB-REG-161, FAB-REG-128, FAB-REG-134, FAB-REG-119, FAB-REG-075, FAB-REG-076, FAB-REG-129, FAB-REG-155, FAB-REG-077, FAB-REG-136, FAB-REG-180, FAB-REG-181, FAB-REG-164. Saved repair scenarios have their meaningful native route, exact Saved review and one clean targeted replay, with partial branch limits in the linked proofs. Duplicate126 is explicitly different: its original6-input/609-assertion no-save probe passed under the existing071 fix;071 supplies the durable saved regression at its actual historical replay context.126 adds no scenario, group or new replay credit. 074 is the same distinction for the Data Doll shared producer: original32/2293 Induction no-save control passed, while133 supplies a separate saved33/2816 Hyper Driver regression and one clean replay.074 adds no saved scenario, replay or group. Directly affected Harvest/Restless/Steel compatibility checks also passed at their stated contexts. Source-verified private window-label errata for Channel/Cloud/Siren are separate from preserved effect/payment/identity assertions. Fresh149 required one supported-API target correction: computed power2 now queries the actual attack, with an additional exact physical counter assertion (2010 versus2009); actual damage2 and all25 actions stay unchanged. Its saved endpoint is deliberately Damage, not Close/End/Mark coverage. Component controls add assurance, not extra native branches or distinct bug counts.

Assembly145 required a callback entry initializer through canonical/builder/exact-legacy local generation. The first emission failed syntax because a multiline condition was miscompiled; all five files were restored from verified backups. The syntax-only R2 emission changed only the selected Assembly macro/await helper (unrelated PHP/counts preserved except timestamps), passed syntax and 33 actual emitted callback checks, then the original native6/664 and clean replay. The first output supplies no passing credit. Genis/Frosthaven's earlier timestamp-only emission is likewise retained separately. No generated hand edits or remote authoring writes.

Repair work is paused at this review checkpoint with 27 unresolved records; this is not a completed or merge-ready repair handoff. Generic virtual094 now has a concrete current-source six-edit/four-function W-only proposal and independent Root source-scope review, preserving the qualified Arcanic route and physical R4/R5. Its denied repository application remains known-unexecuted pending specific approval; current components/Native/replay remain unrun. The historical67/189 checks are historical only. See [current094 hold](review-evidence/ros094-rebased-after164-awaiting-approval-20261117.json). Specific approval of the physical Action/Instant lifecycle, including Glisten096, was received and R4 is applied. Glisten096 and Steadfast132 now have correct native saves/reviews/clean replays. Remembrance104 exposed the public same-source Stack-move API gate, now repaired by narrow R5 and qualified with its original native test, clean replay, and Sift/Gorganian compatibility checks. The API permits only the matching currently resolving PLAIN_SPELL source; unrelated Stack layers remain protected. No blanket promotion of the roughly47 overlapping routes: each existing failure still needs its own meaningful verification. Separately held virtual-await094 and all other specific denials remain untouched. Other unresolved records remain open for follow-up; no new coverage batches or full-suite/historical-corpus run. No staging, commit, push or deployment.

Lightning Press070 also needed seven private-window guards corrected from PRIO to the actual restored ATTACK window. The first failed probe had already executed declaration and answer; it saved no fixture. Source-verified stack-first passing preserves the required four responses before resolution, and all 33 actions and 2,341 effect/payment/identity assertions are retained. Stack/X-cost target coverage remains outside its qualified branch.

Bloodrot Pox147 remains unapplied: the appropriately authorized read-only GET returned **HTTP403 Forbidden** from `https://soulmastersdb.net/TCGEngine/CardEditor/API/CardCodeService.php`. An earlier default-sandbox URLError is historical, not evidence of a hosting outage. Required raw authored bodies were not received; a bounded cache lookup found digest-only snapshot metadata, not recoverable raw Pox rows. No local bridge/emission, remote authoring write or generated edit was attempted. See [the retained blocker](review-evidence/pox147-service-http403-block-20261116.json). Restored authorized read access or an unmodified raw authored-row export is required. Surface091 has a more specific input limitation: its current two canonical normalized bodies match the November4 verified remote-body hashes, but the configured emitter reads the Remote repository and has no inspected canonical-JSON snapshot input route. A loopback-only read-only availability check has now returned MySQL1049: the mapped FaBSim local database does not exist. No repository constructor/EnsureSchema, DDL, table/data/config/import or generation was run. No091 emission or new remote service retry occurred. See [the local input result](review-evidence/local-card-input-readonly-20261117.json). See [the bounded input triage](review-evidence/surface091-generation-input-triage-20261117.json). Other specific denial holds remain untouched. The weapon-grant packet's outer inventory guard caught the separate Fresh fixture because Root overlapped recordings; its complete Native results, exact four-folder reconciliation and subsequent sequential clean replays are recorded in the qualified proof. No game was re-recorded to hide that orchestration error. Future recording packets run sequentially.

Recent repair proofs also preserve separate qualification contexts: Darkest154 original34/2380, Escalate201 original9/1247, Sowing160 original5/504 (existing physical lifecycle; no additional engine edit), Courier121 corrected56/5229, Lens156 original6/766, Chorus190 original37/4509, Earthlore124 original10/1113, Deathmatch079 original32/2481, Oysten191 original36/4250, Shimmers196 original32/4128, Data Doll133 original33/2816, Wind198 original111/13746, Featherfoot098 original36/2928, Codex161 original5/570, Rift Bind128 original37/2990, Pound134 original42/3762, Disable119 original33/2941, Fusion075 original33/2470, Pulping076 all32/2436 and Embolden129 originalRed11/1211. Each has one clean targeted replay and exact Saved review. Courier removed one impossible repeated-target answer and its checkpoint: 77 correct checks were duplicates of the following correct checkpoint, while eight were invalid UI/state assumptions; every unique normative payment/boost/damage assertion remains. The W-only reviewer was also corrected to bind power/go-again to each Source-asserted current attack UID instead of always the first combat-chain slot; the recorder and fixture already had correct targets. Lens's wrapper prose mistakenly described a destruction cost; only that metadata was corrected to the actual Once-per-Turn Instant0 activation, with original recorded input hash retained separately and no fixture or replay change.

Earthlore124 captures one trigger carrying count2 from one actual grouped Action-draw event; its source/action attribution gates and other draw listeners remain unqualified. Deathmatch079 adds global Hero-only permission while preserving permanent adjacency, UPF focus and permission revalidation; original real Landmark cast and actual nonadjacent P3 damage passed. The existing small WTR/UPF/smoke checks passed at d125667b, with no full integration run. Courier121 V1 was safely retired as diagnostic-only/non-recordable; verified original bytes and retirement receipt remain recoverable, while V2 and its five fixture files are unchanged.



Oysten191 preserves existing Watery ordering while earning Gold separately after real lethal Ally damage and four-seat responses; trigger-order chooser/permutations remain unqualified. Shimmers196 retains its once-per-turn reservation but defers the physical Aura power counter through an earned layer, using installed entry epochs. Data Doll133 now leaves the boosted Item banished through the Hero response round, avoiding retroactive Hyper Driver resource/steam consumption; other interactive Item-entry paths remain unqualified. Featherfoot098 connects the actual Lunging explicit+1 notification to an earned layer, with independent destruction/attack GA and captured destruction-controller restoration. Other power application/replacement routes remain unqualified. These are scoped repairs, not complete card validation. Wind198 also preserves its existing scheduled effect across window resets: its full original route reaches the next Ira Action phase and actual two-Kodachi damage1+1, rather than stopping at a trigger snapshot. Native expiry/restored-passive remains unqualified; the earlier reset-drop observation was actual-function/source evidence, not a fabricated Native-before suffix.

Codex161 uses the existing recipient/creator-aware token factory: Ponder remains controlled/owned by its creator, while each opponent controls an Inertia owned by the Codex controller. Only the original empty-Deck route is qualified; other load/discard/end branches remain unqualified. Rift128 and Pound134 now earn separate played-event layers using installed per-play child bindings. Rift counts the captured controller's non-Attack Actions at resolution; Pound evaluates less life than any live opposing Hero at resolution. The original damage, costs, physical identity and defense restrictions remain asserted. Their alternative response branches have isolated component evidence, not extra Native coverage. Rift's local catalog has stale IF wording; current official WHEN controls. A separately noticed Invigorating Soul-condition snapshot concern remains source-only and unrun; its code is unchanged.

Disable119 now earns a separate synchronous Crush layer, retains its physical sole Arsenal card through four responses, then moves it to the actual owner Deck bottom. Its pre-existing multiple-Arsenal first-entry policy remains unfixed/unqualified; other Crush branches are unchanged. Fusion075 projects the originally offered Earth card to the writer-compacted Hand coordinate without changing live zones, await locals or effect/cost logic. Pulping076 owns the legacy attack macro and defers real draw/discard through an earned layer, protecting the same attack entry for conditional Dominate. Its first post-fix probe failed only because Source13 waited for ATTACK and passed the new trigger quorum. The independently reviewed correction changes exactly that one private until-window to PRIORITY; all32actions/2436query arrays remain unchanged, and the failed11-input/no-Save attempt is retained. A shared protocol-review path was later overwritten while binding the successor; the unavailable prior review hash is labeled historical/superseded, not reconstructed. Current bound review and exact one-leaf application gate are preserved. Embolden129 uses the same entry-effect implementation for its three verified pitch printings; only Red11/1211 has Native and replay credit. Its current another-nonTokenAura condition is checked at resolution; later beginning-Action +5/+4/+3 remains unqualified. The first isolated checker stopped at zero checks because two unrelated pure dispatch predicates were missing; the corrected checker installs actual hash-checked predicates and passed26 checks, with no gameplay-source change to fit that harness.

Chorus190 is only qualified for the original paid-Aura/first-Guardian-hit timing branch. Late Aura entry, pre-capture play/controller identity and multiple-Aura grant multiplicity remain source-only/unqualified continuous-grant findings linked in its proof. The larger global first-card tracking design remains unapplied. None of these partial proofs certifies every card branch.

Existing backup paths and older git checks below remain historical. Current source hashes and exact qualification references are in the machine checkpoint; no fresh final diff/status claim is made here.

Earlier17:05 checkpoint follows as historical context; its counts, hashes, code-path list and git checks describe that earlier snapshot, not the current continuation.













---













Repair checkpoint, November 16, 2026. Coverage expansion is stopped. The focused repairs below are verified; the broader repair backlog is **not complete**. Full-suite validation remains user-owned and was not run.















The current runtime is `0e8250ae60f0d6180065cf516588546c2b5e2533cb4951533ca1c6fbf004f40a`. The saved fixture inventory, review registry and compact index agree on **4,107 scenarios: 3,074 duel and 1,033 four-live**. Coverage records contain **1,260 primary identities /5,196** and **1,134 represented pitch-cycle or standalone groups /3,324**, including1,026 four-live groups. Pitch variants count separately in the identity denominator; representative coverage does not validate untested siblings or all branches. These are recorded qualification contexts, not4,107 fresh passes against final code. Existing historical review/hash/line-ending caveats remain.















| Repair | Verified behavior and evidence | Limits |







|---|---|---|







| Reincarnate199 | Earned random-discard ability leaves physical source public until responses, then moves the same graveyard entry to absolute Deck bottom.32 native inputs/3,965 assertions;118 V3 components; final affected-hook replay and actual implicit-owner2/4-seat unit block pass. [Checkpoint](card-regression-reincarnate199-v4-qualified-checkpoint.json). | Blue non-Berserk native branch. Original creation161af and qualification2439 remain historical; additional final0e825 replay is separate. Berserk and untested siblings are not certified. |







| Razor Reflex Red,070 caller extension | Mode then physical UID/link target before payment; no retarget at resolution.35 native inputs/289 assertions and one replay. All21 legacy Red cases plus Nip/Shred2 pass;11 old positive fixtures were natively re-recorded with all395 source checks retained and72 prompt checks added,455 persisted assertions. [Checkpoint](card-regression-razor-declaration-repair-qualified-4b6c-checkpoint.json). | Original Lightning Press070, other reaction enrollments/admission bugs and AA-earned-Hit timing remain open. No new overall identity/group credit. Twelve legacy authoring can_play guards are not persisted replay assertions. |







| Harvest194 | Existing beginning-Action dispatcher captures the earned layer before ordinary priority; four real responses precede destruction/life. Original12 inputs/1,408 assertions unchanged, one replay and43 isolated checks pass. [Checkpoint](card-regression-harvest194-qualified-0e825-checkpoint.json). | Native Blue only. Red/Yellow amounts and source-departure/reentry controls have component evidence only. Other ROSStart and virtual-await issues remain open. |















These repairs modify FaB-specific shared logic in `FaBSim/Custom/GameLogic.php`, `DYNRuntime.php`, `ROSCards.php`, `CardChoices.php`, `ROSRuntime.php` and `DTDCards.php`; no repository `Core/` or generated source was edited by these repairs. The changed DYN unit oracle now expects the real earned response window. Eleven canonical batch13 recipes gained declaration actions; the other124 recipes are unchanged. Native fixtures, explicit assertions and raw endpoints supply the evidence; snapshots were not manually blessed. Syntax checks passed for all installed changed code. `git diff --check` passed for the six source files and changed unit test; Git warned about future LF-to-CRLF conversion in GameLogic and the unit file, and no normalization was performed.















The highest-impact remaining proposal targets **`FaBSim/Custom/GameLogic.php`**: use the existing managed-resolution path for ordinary owned Action/Instant sources instead of a card-ID whitelist, preserving the owner and Gorganian Tome guards. About47 overlapping held routes may benefit; this is not a guaranteed unlock or bug count. It necessarily subsumes the previously denied Glisten096 change. The earlier application was definitively denied before process creation, so no write executed. The current proposal remains **unapplied and untested**, pending specific approval. Reviewable local proposal: `C:\Users\maxim\Documents\Codex\2026-10-19\task\nov16-plain-physical-spell-lifecycle-structural-unapplied-proposal-r3-after-razor-harvest\manifest.json` (manifest SHA`896ea005…`); its projected runtime is not active. The denial reason and exact prior operation are retained in the [machine checkpoint](review-evidence/repair-checkpoint-20261116.json).















Other denied operations remain untouched: Illuminate089, Mirage090, virtual-layer Blood Vest094, Quantum144, Hellbound/Vexing176–177, Elliptical184 and Flutter188. The ledger still has **93 records marked unresolved**, plus335 source-only held routes. These are overlapping records, not distinct current root causes. Not all are permission-blocked: Read the Ripples192, Tome of Aeo193 and the later native producer findings189–202 remain repair work. See [issue ledger](card-regression-issues.json) and [coverage lookup](coverage-additions-after218.json). Nothing here claims all known bugs are fixed.















Recoverable original source backups and the eleven original five-file fixtures remain outside the repository under `C:\Users\maxim\Documents\Codex\2026-10-19\task`: `nov16-reincarnate199-v3-root-application/before`, `nov16-reincarnate199-v4-root-application`, `nov16-reaction-declaration-root-application/before`, `nov16-razor-legacy11-native-rerecord/before`, and `nov16-harvest194-three-file-root-application/before`. No tests or user files were removed. Compact required proof is retained in `review-evidence`; no bulk source-review artifact tree was added.















Focused replay commands used the existing `replay_sparse_batch.py` wrapper with `--targeted-preservation`: new Razor1, legacy Red21, Nip/Shred2, Harvest1 and final directly affected Reincarnate1. Their complete native runner output is in the named `card-regression-replay-nov16-*.json` receipts. The legacy11 native replacements used existing `record_card_regressions.php ... --rerecord-authored` after verified backups. No full or historical-corpus suite was launched.















For user-owned broader validation from the repository root, a bounded diagnostic command is:















```powershell







& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --max-failures=10 --compact "--log=$env:TEMP\fabsim-review-$(Get-Date -Format yyyyMMdd-HHmmss).log"







```















The local worktree contains existing user and prior-session changes. This repair pass did not stage, commit, push or deploy. The machine checkpoint records the read-only git-status snapshot; normal untracked mode counts entries/directories, not individual files. This is focused readiness, not a merge-ready full-suite claim.















Next priorities are the specifically blocked shared physical lifecycle proposal, the separately held virtual-layer lifecycle, and the remaining known phase/trigger producers. Continue only authorized repair work; keep new coverage stopped. Once repairs/blockers are resolved, pause for user review before any new coverage.
