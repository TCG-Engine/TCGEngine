# Integration runner output and failure limits

Run from the repository root. Existing commands retain unlimited execution and
their normal console output unless these optional flags are supplied.

```powershell
$fabRunLog = Join-Path $env:TEMP ("fabsim-" + [guid]::NewGuid().ToString("N") + ".log")
& 'C:\xampp\php\php.exe' DevTools/RunIntegrationTests.php --root=FaBSim --max-failures=10 --compact "--log=$fabRunLog" 2> "$fabRunLog.stderr"
$LASTEXITCODE
```

- `--max-failures=N` accepts a positive integer. The runner finishes the failing
  fixture, then stops before starting another fixture once the limit is reached.
  Omit this flag to run the entire selection.
- `--compact` suppresses passing/step output and bounds each diagnostic headline.
  It requires `--log=PATH`; complete stdout is written before console filtering.
  This limits diagnostic line length, not the total number of console lines.
- `--log=PATH` creates a **new** local file in an existing directory and refuses
  to overwrite an existing file. PHP/process stderr is separate; the command
  above preserves it in the `.stderr` file.
- `--test=<slug>` still selects a single fixture and works with these flags.

The final summary reports selected fixtures discovered, total run, passed,
failed, not run, and skipped. `Total` retains its old meaning of fixtures run.
The normal runner has no skip path, so `Skipped` is zero. An early stop also
prints `[STOP]`; unrun fixtures are not counted as passed. Exit status is `0`
when all selected fixtures pass and `1` when any fixture fails, including a
capped run. Argument/log setup errors also fail; a log write/flush error uses `2`.

Controlled CLI checks (temporary fixtures and stub runtime, no game corpus):

```powershell
$runnerPath = (Resolve-Path .\DevTools\RunIntegrationTests.php).Path
$runnerHash = (Get-FileHash -LiteralPath $runnerPath -Algorithm SHA256).Hash.ToLowerInvariant()
& 'C:\xampp\php\php.exe' DevTools/test-integration-runner-options.php "--runner=$runnerPath" "--expected-sha=$runnerHash"
```

Do not use `--update-snapshots` to resolve unexplained failures. Inspect the
complete log and validate the intended game behavior before editing expectations.
