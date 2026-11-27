# Grand Archive development tools

Grand Archive-specific bot, UI, regression, and training tools introduced or changed on the UX/UI branch live here. Run commands from the repository root.

- Bot checks: `php DevTools/grand-archive/GABotDecisionTest.php`
- Knowledge checks: `php DevTools/grand-archive/GABotKnowledgeTest.php`
- Evaluation runner: `python3 DevTools/grand-archive/ga-bot-evaluate.py --help`
- Training and replay workflows: [rl/README.md](rl/README.md)
- Training tests: `python3 -m unittest discover -s DevTools/grand-archive/rl -p 'test_*.py'`

Shared infrastructure stays in `DevTools/TestAutomationBridge.php` and `DevTools/rl`. The pre-existing fixture corpus remains in `DevTools/ga-fixtures`, and local training artifacts remain in `DevTools/rl/artifacts`. Runtime card and bot implementations remain in `GrandArchiveSim`.
