<?php
// CLI-only instrumentation. Both policies use the current legal-action enumerator and engine.
class GABotEvaluation {
    private array $args;
    private array $coverage = [];
    private array $times = [];
    private string $actionDigest = "";
    private float $started;
    public function __construct(array $args) { $this->args = $args; $this->started = microtime(true); }
    public function install(): void {
        if (empty($this->args['reportOut'])) return;
        if (!in_array($this->args['candidateSeat'] ?? 1, [1, 2], true)) throw new RuntimeException('Invalid candidate seat');
        if (!empty($this->args['baseline'])) {
            $dir = realpath($this->args['baseline']);
            if (!$dir) throw new RuntimeException('Missing baseline');
            $manifest = json_decode(file_get_contents($dir . '/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
            foreach ($manifest['files'] as $file => $hash) {
                if (hash_file('sha256', $dir . '/' . $file) !== $hash) throw new RuntimeException('Baseline hash mismatch: ' . $file);
            }
            require $dir . '/BotLegalActions.php';
            require $dir . '/BotHeuristic.php';
        }
        GABotRegisterChooser('heuristic', function ($actions, $legal) {
            $baseline = !empty($this->args['baseline']) && intval($legal['playerID']) !== ($this->args['candidateSeat'] ?? 1);
            $prefix = $baseline ? 'GAFrozenBot' : 'GABot';
            $start = hrtime(true);
            $chosen = ($prefix . 'ChooseActionHeuristic')($actions, $legal);
            $policy = $baseline ? 'baseline' : 'candidate';
            $this->actionDigest = hash('sha256', $this->actionDigest . json_encode($chosen));
            $this->times[$policy][] = (hrtime(true) - $start) / 1e6;
            // Count offered optional choices per chooser attempt, including controller retries.
            if (in_array($legal['decisionType'] ?? '', ['MZMAYCHOOSE', 'YESNO'], true)) {
                foreach ($actions as $action) {
                    $answer = $action['cardID'];
                    if (in_array($answer, ['PASS', 'NO'], true)) continue;
                    $score = ($prefix . 'OptionalScore')($legal['playerID'], $answer, $legal);
                    $ability = DecodeOpportunityAbilityChoice($answer);
                    $obj = $answer === 'YES' ? null : GetZoneObject($ability['mzID'] ?? $answer);
                    $identity = ['policy' => $policy, 'type' => $legal['decisionType'], 'handler' => $legal['handler'] ?? '', 'card' => $obj->CardID ?? '', 'ability' => $ability['abilityIndex'] ?? null];
                    $key = json_encode($identity);
                    $row = $this->coverage[$key] ?? ($identity + ['offered' => 0, 'selected' => 0, 'declined' => 0, 'unsupported' => 0]);
                    $row['offered']++;
                    $status = $score === null ? 'unsupported' : (($chosen['cardID'] ?? null) === $answer ? 'selected' : 'declined');
                    $row[$status]++;
                    $this->coverage[$key] = $row;
                }
            }
            if (($legal['decisionType'] ?? '') === 'MZMULTICHOOSE' && ($legal['minimum'] ?? 1) === 0) {
                $identity = ['policy' => $policy, 'type' => 'MZMULTICHOOSE', 'handler' => $legal['handler'] ?? '', 'card' => '', 'ability' => null];
                $key = json_encode($identity);
                $row = $this->coverage[$key] ?? ($identity + ['offered' => 0, 'selected' => 0, 'declined' => 0, 'unsupported' => 0]);
                $supported = str_starts_with($legal['handler'] ?? '', 'PAYFLOATING|');
                $row['offered']++;
                $row[!$supported ? 'unsupported' : (in_array($chosen['cardID'] ?? '-', ['-', 'PASS'], true) ? 'declined' : 'selected')]++;
                $this->coverage[$key] = $row;
            }
            return $chosen;
        });
    }
    public function save(array $result): void {
        if (empty($this->args['reportOut'])) return;
        $timing = [];
        foreach ($this->times as $policy => $values) {
            sort($values); $n = count($values);
            $timing[$policy] = ['calls' => $n, 'meanMs' => array_sum($values) / $n, 'p95Ms' => $values[max(0, (int)ceil($n * .95) - 1)]];
        }
        $result += ['actionDigest' => $this->actionDigest, 'seed' => $this->args['seed'] ?? 0, 'candidateSeat' => $this->args['candidateSeat'] ?? 1, 'elapsedSeconds' => microtime(true) - $this->started, 'timing' => $timing, 'coverage' => array_values($this->coverage), 'unsupportedPrompts' => array_values($GLOBALS['GABotUnsupportedOptionalDecisions'] ?? [])];
        if (file_put_contents($this->args['reportOut'], json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false) throw new RuntimeException('Cannot save evaluation report');
    }
}
