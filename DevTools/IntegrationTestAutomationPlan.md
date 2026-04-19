# Integration Test Automation Plan

## Goal

Build a Copilot-first workflow for generating GrandArchiveSim regression tests from editable scenario templates instead of manual UI recording, while preserving the existing fixture runner and fixture format.

## Success Criteria

- A developer can ask Copilot to create a regression test for a card or interaction from inside VS Code.
- Copilot can use MCP tools to inspect cards, choose a scenario template, build an initial gamestate, search for a valid action sequence, and save a normal regression fixture.
- Generated tests still run through the existing CLI path in [DevTools/RunIntegrationTests.php](c:\xampp\htdocs\TCGEngine\DevTools\RunIntegrationTests.php).
- Scenario setup is editable by humans without editing raw gamestate text.
- The first implementation slice delivers value before full action-search automation is complete.

## Non-Goals

- Replacing the existing regression runner.
- Replacing the UI recorder.
- Building a general game-playing AI.
- Automating every card family in the first milestone.

## Current State

### Existing strengths

- [ProcessInput.php](c:\xampp\htdocs\TCGEngine\ProcessInput.php) already routes to [Core/EngineActionRunner.php](c:\xampp\htdocs\TCGEngine\Core\EngineActionRunner.php), which is a stable automation seam.
- [Core/RegressionTestFramework.php](c:\xampp\htdocs\TCGEngine\Core\RegressionTestFramework.php) already defines the shared fixture format and assertion model.
- [DevTools/RunIntegrationTests.php](c:\xampp\htdocs\TCGEngine\DevTools\RunIntegrationTests.php) already replays fixtures in CLI.
- [McpServer](c:\xampp\htdocs\TCGEngine\McpServer) already provides a working MCP codebase and packaging pattern.
- The client already exposes structured pending choices via decision queue handling in [Core/UILibraries20260415.js](c:\xampp\htdocs\TCGEngine\Core\UILibraries20260415.js).

### Current gaps

- Initial state authoring depends on raw `initial_gamestate.txt` snapshots.
- Assertion coverage is too narrow for broad AI-authored test generation.
- There is no server-side legal action enumerator.
- There is no MCP surface for test generation.
- There is no custom Copilot agent specialized for this workflow.

## Product Shape

### Developer experience in VS Code

Target workflow:

1. User asks Copilot: "Create a regression test for card X" or "Create a test for interaction Y."
2. A workspace custom agent handles the request.
3. The agent uses a dedicated testing MCP to:
   - inspect the target card or interaction
   - list scenario templates
   - pick and fill an initial-state template
   - compile that template into a real gamestate
   - enumerate legal actions and optionally search a path
   - evaluate assertions
   - save a normal fixture under `Tests/Integration/GrandArchiveSim/...`
4. The agent reports what it created and where human review is needed.

This keeps the human inside Copilot chat and inside the repo, which is the right ergonomics for the team.

## Architecture

## 1. Editable Scenario Templates

Introduce a human-editable scenario layer that compiles to the current fixture format.

### Proposed location

- `Tests/ScenarioTemplates/GrandArchiveSim/`

### Proposed categories

- `activation/`
- `play-from-hand/`
- `enter-trigger/`
- `attack/`
- `reaction/`
- `graveyard/`
- `materialize/`
- `passive/`

### Proposed file shape

Each template should define:

- metadata: name, category, tags, supported roots
- player context: turn player, phase, priority assumptions
- zone contents for both players
- card-level properties: status, damage, counters, turn effects, subcards, controller
- optional decision queue seed state
- optional variable seed state for `DecisionQueueVariables`
- default assertions for the template family
- placeholders that the MCP can fill for card IDs, targets, counts, and variant flags

### Output

The compiler should generate:

- `initial_gamestate.txt`
- optional seed `actions.json`
- optional seed `assertions.json`

The generated output must remain compatible with the current runner.

## 2. MCP Integration

Use the existing [McpServer](c:\xampp\htdocs\TCGEngine\McpServer) and extend it with test-automation tools for the proof of concept.

### Current direction

Keep all card-authoring and test-authoring tools in the same MCP server for now.

Rationale:

- fastest path to first value
- avoids extra VS Code MCP registration work during the proof of concept
- lets us validate the workflow before deciding whether a split is worth the complexity

If the tool surface becomes unwieldy later, we can still split testing tools into a dedicated server identity.

### Tool groups

#### Template and setup tools

- `list_scenario_templates(root)`

#### Test authoring tools

- `new_test_from_scenario(root, templateId, parameters)`
- `run_test(root, slug)`
- `add_action_to_test(root, slug, action)`
- `save_test(root, slug)`

#### Engine inspection tools

- `enumerate_legal_actions(root, gameName, viewerPlayerId)`
- `apply_engine_action(root, gameName, action)`
- `get_decision_queue(root, gameName, player)`
- `get_game_snapshot(root, gameName, view?)`
- `hash_game_state(root, gameName)`

Search and richer evaluation tools stay out of the proof-of-concept scope and can be added after the editable setup flow is validated.

### First-value scope for MCP

Do not build all tools first. The first slice should ship only:

- template listing
- scenario-based test creation
- fixture action append
- fixture save
- fixture run
- a narrow legal action enumerator for the first scenario family

## 3. Custom Copilot Agent

Add a workspace custom agent at:

- `.github/agents/grandarchive-test-author.agent.md`

### Role

This agent should specialize in building and refining GrandArchiveSim regression tests.

### Tool access

Minimal recommended tool set:

- `read`
- `search`
- `edit`
- `todo`
- `tcgengine-card-editor/*`

Do not give it broad shell execution unless needed for a later phase.

### Responsibilities

- determine the test family from the user request
- gather card and engine context
- choose or propose a scenario template
- fill template parameters
- request legal actions from MCP
- generate or refine assertions
- save the fixture
- explain residual ambiguity instead of guessing silently

### Prompt/body guidance

The agent should explicitly prefer:

- template reuse before raw gamestate editing
- narrow bounded searches over open-ended exploration
- readable assertions over raw final snapshot dependence where practical
- preserving current fixture compatibility

## 4. Assertion Expansion

Extend the assertion model in [Core/RegressionTestFramework.php](c:\xampp\htdocs\TCGEngine\Core\RegressionTestFramework.php).

### Proposed new assertion types

- `card_not_exists`
- `zone_contains_match`
- `zone_not_contains_match`
- `card_counter_equals`
- `card_counter_delta`
- `card_damage_equals`
- `card_damage_delta`
- `card_turn_effect_contains`
- `card_turn_effect_not_contains`
- `decision_queue_variable_equals`
- `flash_message_not_contains`
- `custom_helper_assertion`

### Policy

Generated tests should prefer explicit assertions for the intended effect and only use final snapshot matching as a backstop. This remains a later phase than the current play-from-hand proof of concept.

## 5. Legal Action Enumeration

This is the technical hinge for AI-generated tests.

### Source of truth

Use engine state, not DOM scraping.

#### If a decision queue is pending

Enumerate actions from the next live decision entry:

- `YESNO` -> `YES`, `NO`
- `MZCHOOSE`, `MZMAYCHOOSE` -> legal mzIDs plus optional `PASS`
- `NUMBERCHOOSE` -> bounded numeric values
- `MZMODAL` -> valid option subsets
- `MZSPLITASSIGN` -> bounded assignment strategies for early slices
- `ICONCHOICE` -> allowed options

#### If no decision queue is pending

Enumerate from server-known affordances:

- field activations via `CustomInput Activate:n`
- click actions via `FSM`
- limited move or drag actions only when the active scenario family requires them

### Implementation note

The first version does not need full generality. It only needs to support the initial template families we choose.

## 6. Search Strategy

Avoid a generic "play the game" search.

### Recommended approach

- family-specific recipes first
- bounded BFS or beam search second
- state dedupe by normalized gamestate hash plus decision queue state
- explicit stop conditions driven by assertions or goal predicates

This stays outside the first proof-of-concept slice.

### Example policy

For a simple activation test:

1. instantiate activation template
2. enumerate legal activations for the target card
3. apply the chosen activation
4. resolve decisions with bounded search or recipe rules
5. stop when the target effect assertion is satisfied or the branch space is exhausted

## 7. File and Data Layout

### Existing fixture output stays here

- `Tests/Integration/GrandArchiveSim/<slug>/`

### New editable scenario layer

- `Tests/ScenarioTemplates/GrandArchiveSim/`
- `Tests/ScenarioDrafts/GrandArchiveSim/` for temporary AI-authored drafts if needed

### New Copilot customization

- `.github/agents/grandarchive-test-author.agent.md`

## Delivery Phases

## Phase 0: Design and Scaffolding

Deliverables:

- plan document
- workspace custom agent skeleton
- MCP server extension decision and project layout
- scenario template schema draft

Exit criteria:

- agreed template format
- agreed MCP boundary
- agreed first scenario family

## Phase 1: First Value with Editable Templates

Deliverables:

- scenario template schema and loader
- 1 to 3 editable GrandArchiveSim templates
- compiler from template spec to `initial_gamestate.txt`
- manual draft-to-fixture flow

Suggested template families for first value:

- simple play-from-hand

Initial placeholder support:

- a single editable placeholder for a card in `myHand`

Exit criteria:

- a developer can edit a template and compile a valid initial gamestate without touching raw snapshot text

## Phase 2: Better Assertions and Fixture Drafting

Deliverables:

- expanded assertion set
- fixture draft save tools
- assertion suggestion helpers for the selected scenario families

Exit criteria:

- generated tests can assert intended effects without depending only on full final snapshots

## Phase 3: Narrow Legal Action Enumerator

Deliverables:

- server-side legal action enumeration for selected families
- decision-queue option enumeration
- deterministic action normalization for search

Exit criteria:

- the system can enumerate valid next actions for the first scenario families without a browser

## Phase 4: Copilot Agent and MCP Integration

Deliverables:

- MCP server tool additions for test authoring
- workspace custom agent file
- initial agent instructions for template-first test authoring

Exit criteria:

- a developer can invoke the agent in VS Code and get a draft fixture for supported families

## Phase 5: Guided Search

Deliverables:

- bounded search over legal actions
- goal-predicate driven path finding
- branch explanations on failure

Exit criteria:

- the agent can autonomously find short valid action paths for supported scenario families

## Phase 6: Coverage Expansion

Deliverables:

- more scenario templates
- more enumerated action families
- more assertion helpers
- reporting on covered versus unsupported card categories

## First Implementation Slice

This is the slice I recommend implementing immediately after review.

### Slice objective

Produce an editable play-from-hand scenario template with a single hand-card placeholder and a compiler that can generate a valid initial gamestate plus draft fixture.

### Why this first

- it solves the most painful manual step early
- it does not depend on search being finished
- it creates the substrate the MCP and agent will use later
- it is reviewable and testable with the current runner

### Concrete tasks

1. Define scenario template schema.
2. Add template loader and validator.
3. Add compiler that emits `initial_gamestate.txt`.
4. Add the first play-from-hand template for GrandArchiveSim.
5. Add a CLI or bridge helper to build a fixture draft from a template.
6. Add a small MCP surface for listing templates, creating drafts, and stepping actions.

### Proposed sample template

- `play-from-hand/basic-hand-card`

## Risks and Mitigations

### Risk: search space explosion

Mitigation:

- recipe-first design
- bounded search only
- scenario-family rollouts

### Risk: raw gamestate format is hard to synthesize correctly

Mitigation:

- build the compiler on top of generated runtime helpers where possible
- validate by round-tripping through parser and runner

### Risk: AI silently chooses the wrong interaction path

Mitigation:

- explicit goal predicates
- assertion-first evaluation
- custom agent instructions to surface ambiguity instead of guessing

### Risk: MCP becomes too broad

Mitigation:

- keep the proof of concept tool list intentionally small
- minimal tool exposure per agent
- split the server later only if the combined surface becomes hard to manage

## Review Questions

These are the main decisions to confirm before implementation:

1. Do we want to keep using a single shared MCP server, or split test tools later?
2. Are the proposed template categories the right first partition for GrandArchiveSim?
3. Is the first-value slice correctly focused on editable setup first, before search?
4. Which first scenario family should we support end-to-end: activation, play, or attack?

## Recommended Immediate Next Step

After review, start with Phase 1 and implement the play-from-hand scenario template, compiler, and minimal MCP tools before touching search.