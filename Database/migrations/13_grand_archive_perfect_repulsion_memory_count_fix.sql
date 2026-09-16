-- 13_grand_archive_perfect_repulsion_memory_count_fix.sql
-- Fixes Perfect Repulsion (card_id gwj4f15joh, GrandArchiveSim) so its "prevent exact X damage"
-- shield actually snapshots X (the caster's memory count) correctly.
--
-- The ability's macro source computes $memoryCount = count(GetMemory($player)) BEFORE the
-- `await $player.MZChoose($targetStr)` target-resolution call, then references $memoryCount again
-- AFTER the await to build the TurnEffects tag ("PREVENT_EXACT_" . $memoryCount). The code
-- generator (zzGameCodeGenerator.php's TransformAwaitCode) splits ability_code at each `await`
-- into two separate PHP closures (cardActivatedAbilities[...] runs pre-await;
-- customDQHandlers[...:CardActivated-1] resumes post-await) -- PHP closures don't share local
-- variables across that split, so the post-await closure's $memoryCount was always undefined,
-- silently producing the tag "PREVENT_EXACT_" (empty suffix) instead of e.g. "PREVENT_EXACT_2".
-- CombatLogic.php's OnDealDamage consumer (`strpos($effect, "PREVENT_EXACT_")`) then never found a
-- numeric match, so the shield could never actually prevent damage.
--
-- Fix: recompute $memoryCount immediately after the target resolves, inside the post-await
-- closure, matching the ability's documented intent (memory is snapshotted as the shield resolves,
-- not at initial activation) -- see Tests/Integration/GrandArchiveSim/perfect-repulsion-prevent-x/.
--
-- Applies only to the GrandArchiveSim application database (table card_abilities). Idempotent: the
-- LIKE guard only matches the pre-fix adjacency (MZChoose(...) directly followed by AddTurnEffect),
-- which no longer matches once the REPLACE has inserted the recompute line; a second run is a no-op.
UPDATE card_abilities
SET ability_code = REPLACE(
    ability_code,
    '$target = await $player.MZChoose($targetStr);\nAddTurnEffect($target, "PREVENT_EXACT_" . $memoryCount);',
    '$target = await $player.MZChoose($targetStr);\n$memoryCount = count(GetMemory($player));\nAddTurnEffect($target, "PREVENT_EXACT_" . $memoryCount);'
)
WHERE root_name = 'GrandArchiveSim'
  AND card_id = 'gwj4f15joh'
  AND ability_code LIKE '%MZChoose($targetStr);\nAddTurnEffect($target, "PREVENT_EXACT_" . $memoryCount);%';
