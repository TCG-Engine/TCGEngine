> **22 November current checkpoint:** [High Roller repair and review pause](REPAIR_REVIEW_20261122.md) supersedes this historical report. The selected pass is93/93 scoped records resolved (17/17 remaining-pass),4202qualified scenarios (3075two-live+1127four-live). The earlier UPF self-target assertion was incorrect; see the correction in the current report.

# Current repair checkpoint — paused for review

**92/93 selected overlapping records resolved; 16/17 of the remaining pass resolved.** High Roller093 remains held on its conditional target-cardinality contract. **4,199 saved/qualified scenarios = 3,074 duel + 1,125 four-live; 1,349 exact identities and 1,223 representative groups.** Actual qualification runtimes are preserved; no full suite was run.

See [current final review](REPAIR_REVIEW_20261120.md) and [compact final evidence](review-evidence/repairs-final-20261120.json). The six specifically approved local operations, Tear, Read, Pox and Burn are now qualified in their stated scopes. All broader coverage is paused. The separately denied credentialed read was not retried; cached-only local generation succeeded. No staging, commit, push, deployment or remote changes.

The material below is historical evidence, not the current unresolved-action list.

---

## Historical 81/93 checkpoint (superseded above)

# Remaining FaBSim repairs — 20 November 2026

Paused for review: **five more original records resolved;81/93 overlapping records resolved in tested scope,12 open**. These are not81 distinct engine bugs. No breadth-only batches or full suite.

| Closed record | Exact saved native result | Behavior pinned |
| --- | --- | --- |
|097 Drowning Dire|43 actions /3,852 assertions|Fresh hit responses, owned real Hit choice, graveyard-bottom effect and cleanup.|
|101 Refraction Bolters|60 /5,213|Generated listener eligibility, responseable retained Modal, physical destruction, normal GA timing and real AP consumer.|
|109 Head Leads the Tail|55 /4,402|Earned attack trigger/NameCard, actual same-chain bonus and expiration.|
|195 Powder Keg|34 /4,622|Mechanologist Gun hit, retained optional choice, actual defending Equipment/Keg destruction and weapon cleanup.|
|200 Mark/Malice|37 /2,737|Actual lethal bound Zombie, both earned layers, real turn-down, exactly two Corpses and full cleanup. Only observed order native-tested.|

Burn125 has two qualified36/3,354 live-source cases, including a Dragon6-specific prevention control. It remains open: departed-source LKI and later Dragon ability-loss suppression are unresolved. A passing HP-only earlier snapshot used the wrong Aura damage source; independent review caught it. It was recoverably quarantined outside runner discovery, then replaced by corrected native recording. No incorrect case was registered.

## Verification and inventory

Seven saved cases passed301 actual native inputs /27,534 persisted assertions, independent source and saved review, and one clean replay each at final runtime `5cae26a34b6c22b5f197534fef70c1e165d65b1f14e0dd96326493f3ef8dbb32`. Three direct existing replays (Vest, Ebon Fold, Remembrance) and the small WTR check passed. All selected raw fixture files stayed unchanged during replay. A wrapper expected incorrect summary labels; the first successful replay was recovered from its complete log and was not duplicated. Applied candidate syntax and34 controlled checks passed; ten more Tear candidate checks passed but that repair is not active.

**4,188 saved = qualified/registered:3,074 duel +1,114 genuine four-live.** Exact evidence covers1,339/5,196 identities and1,213/3,324 representative groups;1,105 groups have four-live evidence. New fixtures add six group representatives; the second Burn source control adds zero identity/group credit. Historical fixtures retain actual qualification contexts;4188 is not a final-runtime full-suite result. Untested pitch siblings and branches receive no credit.

[Qualification](review-evidence/remaining17-5cae-qualified-saved.json), [independent saved review](review-evidence/remaining17-independent-saved-review-20261120.json), [targeted replay receipt](card-regression-replay-remaining17-5cae.json), [machine checkpoint](review-evidence/remaining17-checkpoint-20261120.json). Full logs and compact component/source reviews remain in the external task workspace, `remaining17-final-focused-replays` and `nov20-remaining*-prepared`.

## Exact remaining blockers

- **Seven records /six previously denied operations:**089 Illuminate,090 Mirage,144 Quantum,176/177 Hellbound/Vexing,184 Elliptical,188 Flutter. None retried. [Grouped exact files, risks and approval plan](REMAINING17_APPROVAL_BRIEF_20261120.md).
- **095 Tear:** captured-victim proposal/source review and10 checks passed. One normal guarded MacroCode emission failed: `Aethersling legacy target prefix changed.` All five proposed sources and both generated outputs were rolled back and verified. No active095 fix or after-fix native pass.
- **093 High Roller:** official rules confirm announcement targeting before AP. Conditional second-Intimidate declaration still needs authoritative resolution; first-target fix alone would be partial. Normal declaration/authoring integration remains blocked by the generation input guard.
- **125 Burn:** live-source timing/attribution repaired, LKI/later-suppression work remains. **147 Pox:** earlier GENERIC type fix retained, but new no-save source-specific prevention probe passes894 prefix checks over10 accepted inputs then expectsHP19/gets18; source5 protection remains unconsumed. Exact low-level packet UID was not directly observed. [Failure and command](review-evidence/pox147-source-prevention-failure-20261120.json); no Pox fixture saved or qualified. Both need real captured-source support through normal authored callbacks, not synthetic source aliases.
- **192 Read the Ripples:** automatic beginning-End scheduling/ordering plus owned actual StartTurn/Rearrange continuation before Arsenal/refill remain. Generic ability ownership does not provide that phase driver. No phase patch or new after-fix claim.

The prerequisite read-only snapshot diagnostic was separately **denied before process creation**. Target: normal configured HTTPS Card Code snapshot on soulmastersdb.net, GET through existing PHP client/token, no redirects. Auto-review classified it as private/unverified remote credentialed access outside local fixture scope. No diagnostic request ran; no retry or indirect workaround. [Exact denied action/reason](review-evidence/remaining17-snapshot-read-denial-20261120.json). Specific approval of that read, or current user-provided nonsecret Aethersling PrepareCard/ResolveCard source, is the smallest next input. No normal regeneration retry until the guard is resolved and reviewed. Generation-dependent repairs remain pending; no generated hand edit or remote source mutation.

## Diff, recovery and next step

Only four production files changed this restart: shared FaB lifecycle/event adapters in `FaBSim/Custom/GameLogic.php` and `ROSCards.php`, and Powder/Burn family producers in `DYNRuntime.php` /`UPRCards.php`. Repository Core and final generated outputs are unchanged. Authoring/build/codegen changes for095 were rolled back. Scoped `git diff --check` passed. HEAD remains b097e43e4de0369d44839fa95d3804aa1454ff1e;146 tracked unstaged entries include earlier and unrelated work. Nothing staged, committed, pushed or deployed; config/DB untouched.

Source beforeimage paths/hashes and metadata backups are in the machine checkpoint. Registration beforeimages: external workspace `remaining17-registration-preflight`; final metadata beforeimages: `remaining17-final-handoff/metadata-before`; wrong-source five-file snapshot: `remaining125-wrong-source-unregistered-quarantine`. Compare later edits before restoring individual files. No blanket reset.

Authoritative lookup remains [coverage-additions-after218.json](coverage-additions-after218.json) (scenario/card/group, exact creation and qualification runtimes, branches, path/replay/review proofs), [card-regression-review.json](card-regression-review.json) and [issues](card-regression-issues.json). Base indexes stay frozen through218; use the additions overlay. [Reusable repair authoring input](card-regression-remaining17-20261120.json) preserves intended cases and explicit protocol corrections. Existing diagnostic originals are unchanged.

The user-owned broader command was **not run**:

    & 'C:/xampp/php/php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --max-failures=10 --compact --log=C:/Users/maxim/AppData/Local/Temp/fabsim-after-remaining-repairs.log

Next: review the seven-record approval brief and the separate snapshot-read blocker; reconcile the changed generator input, then complete the captured-source, declaration and automatic-phase designs under focused validation. No coverage expansion before review. This handoff does not claim full merge readiness.
