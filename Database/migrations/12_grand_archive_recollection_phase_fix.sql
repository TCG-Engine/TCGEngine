-- 12_grand_archive_recollection_phase_fix.sql
-- Fixes a dead card_abilities.prereq_code check in GrandArchiveSim: several rows compare
-- GetCurrentPhase() against the literal string "RECOLLECTION", but the engine's actual phase
-- codes (Schemas/GrandArchiveSim/TurnSchema.txt) are short strings -- "BREC" (BeforeRecollection)
-- then "REC" (Recollection) -- so GetCurrentPhase() never equals "RECOLLECTION" at any point.
--
-- The player-facing opportunity window for "during the recollection phase" abilities opens
-- during BeforeRecollectionPhase() (GameLogic.php:8899, GrantOpportunityWindow(..., "REC_START")),
-- i.e. while the phase code is "BREC" -- RecollectionPhase() itself ("REC") resolves synchronously
-- with no player-facing window. So the correct comparison is "BREC", not "RECOLLECTION".
--
-- Affected cards: Nightmare Coil (3fe3c97s71), Peer the Depths (6JMwc6cpRm), Tariff Ring
-- (xnrw8qq1uw), Sink the Mind (yrzexkW5Ej). Of these, Tariff Ring's activateAbilityPrereqs check
-- is live (invoked from GameLogic.php:7037/24213); the other three generate into
-- activateCardPrereqs/cardActivatedPrereqs, which are not currently invoked anywhere in the
-- codebase for any app (a separate, broader wiring gap tracked outside this migration) -- their
-- rows are fixed here too for correctness/consistency, in case/when those arrays are wired up.
--
-- Applies only to the GrandArchiveSim application database (table card_abilities). Idempotent:
-- REPLACE() plus the LIKE guard mean a second run is a no-op.
UPDATE card_abilities
SET prereq_code = REPLACE(prereq_code, '"RECOLLECTION"', '"BREC"')
WHERE root_name = 'GrandArchiveSim'
  AND card_id IN ('3fe3c97s71', '6JMwc6cpRm', 'xnrw8qq1uw', 'yrzexkW5Ej')
  AND prereq_code LIKE '%"RECOLLECTION"%';
