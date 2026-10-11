# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

**Primary: the deck-tester.** A competitive Star Wars: Unlimited player pressure-testing a list before an RCQ or tournament. Arrives with a decklist already open in another browser tab, pastes the link, plays a matchup, tweaks, repeats. Usually at a desk, often at night, frequently on a second monitor.

**Secondary: the casual player** grabbing a game against a bot or a stranger without installing anything or owning the physical cards.

**Tertiary: the spectator**, watching a public game in progress.

Guests are first-class users, not a lesser tier: guests play every format and lose only chat (owner ruling, 2026-09-21). A large share of traffic never logs in.

## Product Purpose

Petranaki Arena is a fan-made browser simulator for the Star Wars: Unlimited trading card game. It runs full, rules-accurate games against human opponents or bots, played from a decklist the user pastes in — no install, no account, no physical collection. Success is a player getting from "I have a list" to "I am playing a game" in seconds, and trusting the result enough to change their deck because of it.

## Positioning

A complete server-side rules engine, not a playmat with draggable images: the sim enforces the comprehensive rules, resolves triggers and timing windows, and validates decks against real format legality. Around that sit three things a neighbouring product could not truthfully copy — a bot opponent ("Arenabot", beta) with five distinct play-style archetypes, deck import from nine different community deck sites, and multi-seat formats including 4-player Twin Suns / Team Suns.

## Operating Context

- Players arrive **mid-workflow**, with a decklist open elsewhere. Supported import sources: SWUStats, SWUDB, melee.gg, SWUBase, Protect the Pod, SWU Card Hub, SWUForge, SWU Meta Stats, SW-Unlimited-DB.
- Game setup is a view over a format registry: game type → opponent/players/mode → card pool. Opponent modes include PvP, Arenabot, Hotseat and Goldfish; pools include Premier, Eternal, Padawan and Open, each with a preview variant during spoiler season.
- Matchmaking runs through a public queue, private invite rooms, and direct bot starts. Public games in progress are spectatable.
- The sim is one of several products sharing a UI layer in the same monolith (SWUDeck, FaBSim, GrandArchiveSim, HellbreakSim and others).

## Capabilities and Constraints

- **Guest access is a product commitment.** Every format is playable without an account; only chat is gated.
- **Cross-browser is a hard requirement**: Chromium, Firefox *and* Safari/WebKit. Layout behaviour diverges between engines and a change is not done until all three are verified.
- **Design system ships zero-build** (owner decision, 2026-09-24): plain `.css` served statically with `?v=<filemtime>` cache busting. No bundler, no npm step, no CI build. Modern native CSS is in scope; a toolchain is not.
- **Public APIs are a contract.** Endpoints documented for external consumers must not change shape; additions must be opt-in and backward-compatible.
- Per-app engine files are generated from a schema and gitignored; the fix always lives in tracked source.
- WebP assets must be decoded via Imagick, never GD — production PHP's GD is compiled without WebP support.
- Deck import is sensitive to punctuation drift: a subtitle mismatch silently resolves to the wrong printing.

## Brand Commitments

- The name **Petranaki Arena**.
- **The fan-made non-affiliation disclaimer is legally required** and must remain present and legible on the page: the product is not affiliated with Fantasy Flight Games or Disney, and all Star Wars: Unlimited characters, cards, logos and art are their property. Terms of Use and Privacy Policy links ship alongside it.
- Community links: Discord, GitHub (the engine is open source), and a Patreon support link.
- The existing logo, background art, palette, typography and layout are **explicitly not binding** (owner, 2026-09-24): all are open to replacement.

## Evidence on Hand

- Real card art and card data for every released set, plus preview sets during spoiler season.
- Real decklists across Premier, Twin Suns and Padawan, including competitive lists (in `.claude/CLAUDE.md`).
- Live local environments per sim (SWUSim on `:3400`), a cross-browser Playwright harness (`DevTools/ui-harness`), and a visual-check corpus (`SWUSim/Tests/Visual/`).
- A measured design critique and technical audit of the current main menu, dated 2026-09-24.
- No real user research, analytics, testimonials or traffic figures were established. Future work must not fabricate them.

## Product Principles

1. **The decklist is the front door.** The shortest path from a pasted URL to a live game is the product; anything that lengthens it is a cost.
2. **Guests are not second-class.** A feature that silently requires an account breaks the standing ruling.
3. **Trust is the feature.** Players change real decks based on what happens here, so the interface must show what it understood — which deck, which format, which opponent — before they commit.
4. **The rules engine is the product; the UI is its instrument.** Expression may never obscure task, state, or a familiar affordance.
5. **Every surface ships in three engines.** A look that only holds in Chromium is not shipped.

## Accessibility & Inclusion

No formal conformance target has been set by the owner. A measured audit on 2026-09-24 found Level A failures on the current main menu (document in quirks mode with no `lang`, zero landmarks, validation errors unannounced to assistive tech) and AA failures for contrast and target size. Clearing WCAG 2.2 AA is the recommended bar for replacement work; it is a recommendation, not yet an owner ruling.
