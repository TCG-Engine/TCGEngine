# Legal / compliance audit: Petranaki Arena + SWU Stats (2026-11-08)

This is a read-only review of the repository. I am not a lawyer, and nothing here is legal advice.

**Not checked (production-only settings):**
- Cloudflare dashboard (Web Analytics or "Automatic Signals" can add scripts at the edge).
- Production `php.ini` session-cookie flags.
- Apache log retention on the servers.
- Whether SendGrid is installed on production.

**Scope:**
- Petranaki Arena: `SharedUI/Sites/SWUSim`, `SWUSim/`.
- SWU Stats: `SharedUI/Sites/SWUDeck`, `SWUDeck/`, `Stats/`, `APIs/`.
- Shared code: `SharedUI/`, `AccountFiles/`, `Database/`, `Core/`.
- The other game sites use the same legal templates but were not audited.

---

## The six questions

### 1. Privacy policy: exists, but it is inaccurate and incomplete
The policy is `SharedUI/Render/templates/PrivacyPolicy.tmpl`, shared by every site.

**What it gets wrong**
- It describes every site as "a fan-made deck building and stat-tracking site" (line 5). That is wrong for Petranaki.
- It says "No third party cookies are used" (line 18), but never mentions that every page sends visitors' IP addresses to Google Fonts and, on some pages, to code.jquery.com, cdn.datatables.net and jsDelivr.

**Data that is collected but not disclosed**
- **Chat, whispers included.** Saved to `SWUSim/Games/<n>/Chat.jsonl` and `SWUSim/Chats/*.jsonl` (`Core/ChatStore.php`). Nothing ever deletes it.
- **Chat text in server logs.** Chat is sent by GET (`SharedUI/Render/ChatPanel.php:336-340`), so message text sits in the Apache access logs.
- **IP addresses:**
  - in bug reports (`APIs/SubmitBugReport.php:81`) and player reports (`SWUSim/SubmitPlayerReport.php:56`);
  - in Apache "combined" logs and fail2ban.
- **Player reports** attach a frozen copy of the chat, whispers included (`PlayerReportLib.php:140-150`).
- **Bot games** are recorded action by action for bot training (`SWUSim/Custom/BotDataRecorder.php`).
- **Match history** stores the opponent's name; Glicko ratings and abandon penalties are also kept.
- **Linked-account tokens** for Discord, Patreon, SWU Stats and SWU Forge.
- **Backups** run nightly and are kept for 14 days, with an optional off-site copy (`newhost/backup-db.sh`).

**Data shared with other parties but not disclosed**
- Discord login (receives your email).
- Patreon (patron status).
- SWU Forge (full game record plus deck names; usernames are filtered out).
- Petranaki → SWU Stats game submission.
- SendGrid (email).
- Third-party OAuth apps on SWU Stats can receive a user's **email** (`APIs/OAuth/OAuthServer.php:434-471`).
- Patron card search sends the query text over **plain HTTP** to `142.11.210.6` (`AIEndpoints/ElasticSearchHelper.php:33`).

**Sections missing for GDPR (EU users) and California's privacy-policy law (CalOPPA)**
- Who runs the site and a contact address (Discord alone is weak).
- An effective / last-updated date.
- Legal basis for each use of data.
- Retention periods per kind of data. "Indefinitely" is not acceptable under GDPR.
- A list of user rights: access, correction, deletion, export, complaint to a regulator.
- How Do-Not-Track signals are handled (required by CalOPPA).
- A statement that the site is not directed at children.

### 2. Terms of use: exists, but needs fixes
The terms are `SharedUI/Render/templates/TermsOfUse.tmpl`.
- **Wrong site description for Petranaki** (line 8).
- **Contradicts your open source.** Line 21 forbids extracting the source code, but the code is MIT-licensed and linked from the menu bar. Replace that line with something like "the code is MIT; the card content belongs to its owners".
- **No rules for chat and conduct.** Petranaki has live chat, whispers, player reports and bans (abandon penalties) but no conduct rules. Add a chat/behaviour section, how moderation and reporting work, and that moderators can read saved chat.
- **Nothing on patron-only features.** SWU Stats locks PDFs, images and card search to patrons.
- **"13+" is stated but never checked** (see Local laws below).
- **Contact is Discord only.**

### 3. Cookie policy: a short section inside the privacy policy, and incomplete
- Covered today: the Remember Me cookie (`rememberMeToken`) and the "authorization key" cookie.
- **Missing cookies:**
  - `PHPSESSID` (the login session);
  - `tcgengine_<Root>_lastAuthKey`;
  - `tcg_hg_<lobby>` (room chat key, 1 day);
  - `swu_mobile_card_browser_hidden` (preference, 1 year).
- **Missing storage:** about 25 localStorage, sessionStorage and IndexedDB keys. All are preferences, drafts, saved replays or game-seat keys, and none is a tracker. Describe them by category, not one by one.
- **"Keep me signed in" is ticked by default** on both login pages (`SharedUI/Render/Auth.php:159` and the older SWU Stats layout). The policy says "to opt out, do not use Remember Me", so leave the box unticked by default.

### 4. Cookie consent banner: not needed, once two things are fixed
Every cookie and storage key is first-party, and each one is either strictly necessary (login, game seat, security) or a preference the user chose. I found no analytics, ads, pixels or fingerprinting. The EU ePrivacy rules exempt all of these from consent.

Two things to do so it stays that way:
1. **Self-host Google Fonts** (Barlow, Teko, Archivo, Roboto, Gemunu Libre).
   - Loaded from `SharedUI/Render/Head.php:20-27`, `SharedUI/MenuBar.php:36-39` and `NextTurn.php:402-405`.
   - A German court (LG München, 2022) fined a site for hotlinking Google Fonts without consent.
   - All of these fonts are OFL-licensed, so self-hosting is allowed.
2. **Self-host jQuery, DataTables and Chart.js** for the same reason.
   - jQuery and DataTables: `Stats/CardMetaStats.php:23-25`, `Stats/DeckMetaStats.php:20-22`, `SWUDeck/DeckStats.php:558-568`.
   - Chart.js: `NextTurn.php:16`.

Also confirm that Cloudflare Web Analytics is **off** for both domains in the Cloudflare dashboard. If it is on, either turn it off or disclose it. Its cookieless mode is generally treated as not needing consent, but it still has to be disclosed.

### 5. Refund policy: not needed
- Neither site sells anything directly: no Stripe, PayPal, Ko-fi or checkout.
- "Support" is a Patreon link. Patreon handles billing and has its own refund rules.
- Add one line to the Terms: "Support is through Patreon; billing and refunds are governed by Patreon's terms."
- The real money risk is intellectual property, not refunds (see Image copyright below).

### 6. Form consent
**Signup, both sites.** The "By creating an account you agree to the Terms… see the Privacy Policy" text sits next to the button (`Auth.php:110, 253-256, 276-281`).
- US courts usually enforce this style if it is clear and close to the button, so it is acceptable.
- A required checkbox is stronger. Discord onboarding already has one (`AccountFiles/DiscordOnboarding.php:104-107`), but the email/password signup does not.
- Neither signup asks the user to confirm their age.

**Forms with no notice at the point of entry**
- **Chat:** nothing says it is saved or can be read by moderators.
- **Player report:** nothing says the whole chat is attached and the reporter's IP is stored.
- **Bug report:** nothing says the IP and gamestate are stored, or that the optional Discord name is kept.
- **Linking Discord or Patreon:** no disclosure. SWU Forge linking does disclose that games are sent.

**Broken link:** the "Remember Me" consent note on the SWU Stats login page links to `/TCGEngine/SharedUI/MenuFiles/PrivacyPolicy.php`, which does not exist (404). See `SharedUI/Render/Auth.php:221`. The correct path is `/TCGEngine/SharedUI/PrivacyPolicy.php`.

---

## Template checklist

| Item | Status | Notes |
|---|---|---|
| Only collect necessary data | Mostly lean | See "Over-collection" below. |
| Audit analytics/tracking | ✅ None in code | Confirm Cloudflare dashboard. |
| Audit third-party embeds | ✅ No iframes or widgets | Google Fonts and CDNs leak IPs (see 4). |
| Accessibility | Petranaki good; SWU Stats legacy pages weak | See "Accessibility" below. |
| Alt text | ❌ Missing on SWU Stats card images | `Stats/Decks.php:226-227`, `Stats/DeckMetaStats.php:147,161,163,362-363` (title holds a raw card ID), `SharedUI/Sites/SWUDeck/MainMenu.php:307-314`, GitHub/Discord icons `SharedUI/MenuBar.php:72-73`. |
| Colour contrast | ⚠ Several failures | See "Accessibility" below. |
| Keyboard-friendly forms | ⚠ Gaps | No error shown after a failed login; SWU Stats login labels not tied to fields; deck rows clickable by mouse only. |
| Clear button labels | Mostly OK | Icon-only buttons rely on `title`; GitHub/Discord links read as "External link". |
| Remove fake reviews | ✅ None found | No testimonials, ratings or "trusted by". |
| Remove unsupported claims | ✅ None of concern | "Official card data" refers to the data source and is fine; the Terms contradict the MIT licence (see 2). |
| Add business details | ❌ None | No operator name, email or address anywhere. Add at least a contact email to both legal pages. |
| Check image copyright | ⚠ Biggest real risk | See "Image copyright" below. |
| Check local laws | See below | |

### Over-collection and data hygiene
- **Whispers** are saved forever. Set a retention period (for example 30–90 days, longer only when attached to a report), and purge on a schedule.
- **Chat over GET** puts message text and `authKey` in access logs. Switch it to POST.
- **Debug logging** in `APIs/UserAPIs/GetUserDecks.php:36-41` writes all request headers, Bearer tokens included. Remove the header dump.
- **Unused columns:**
  - `users.lastLoggedIP` is never written; drop it so the schema doesn't suggest IP tracking.
  - `users.patreonEnum` is read but nothing writes it.
- **Session contents:** the session stores the password hash and email (`AccountFiles/AccountDatabaseAPI.php:85-91`). It doesn't need either.
- **No self-serve account deletion.** Deletion by request is legal, but the process must cover:
  - the `users` row;
  - deck data: `ownership`, the deck files on disk, `favoritedeck*`, `lastdeck`;
  - settings and social: `usercosmetic`, `savedsettings`, `blocklist`;
  - match and rating records: `matchhistory`, `glicko_*`, `completedgame`;
  - linked accounts and apps: `swustats_links`, `swuforge_*`, the OAuth tables;
  - teams and resets: `team*`, `pwdreset`;
  - reports: the report tables and the `P-*`/`R-*` folders;
  - chat files and backups.

### Security issues found along the way
These are not compliance items, but the Terms claim "best efforts".
- **Patreon callback ties tokens to whatever user the URL names.**
  - In `APIs/PatreonLogin.php:30-37`, the user id comes from the browser-controlled `state` parameter, with no check that the logged-in user matches.
  - It also echoes `print_r($state)` before a `header()` redirect.
  - The redirect address is hard-coded to swustats.net, yet the button is also offered on Petranaki.
- **Password change** doesn't ask for the current password (`SharedUI/Render/Profile.php:8-62`).
- **Password reset is broken.** It includes files that don't exist and sends a talishar.net URL (`AccountFiles/PasswordResetRequestAPI.php`).
- **Plain HTTP** is used for user queries to `142.11.210.6` (`AIEndpoints/ElasticSearchHelper.php:33`, `APIs/DiscordBot.php`).
- **No security headers** in the repo: no HSTS, CSP or Referrer-Policy.
- **Unauthenticated storage:** `APIs/HolonetSubmitGame.php` saves any POST body it receives.

### Accessibility (WCAG 2.1 AA): top fixes
1. Move Decks, Deck Stats and Card Stats off the legacy `SharedUI/MenuBar.php` and onto `RenderHead`/`RenderMenuBar`. Today they have no doctype, no `lang` and no viewport tag; Melee Tournaments and APIs print the doctype too late.
2. **Deck rows are mouse-only.** They are `<tr onclick>` with no link (`Stats/Decks.php:225`, `SharedUI/Sites/SWUDeck/MainMenu.php:298-302`). Rename is a `<span onclick>` (`:323`).
3. **The Stats ▼ menu works on hover only on desktop** (`SharedUI/css/menuStyles.css:723`). Keyboard users can't reach Deck Stats, Card Stats or Melee.
4. **A failed login shows no error.** `AccountFiles/AccountDatabaseAPI.php:190,206` redirects back with nothing to display.
5. **SWU Stats main menu blocks pinch-zoom.** `user-scalable=no, maximum-scale=1.0` in `SharedUI/Render/Misc.php:5` (WCAG 1.4.4).
6. **Contrast failures:**
   - SWU Stats `--text-muted` #65839f: 4.0:1.
   - Card search placeholder: 4.2:1.
   - Chat placeholder: 3.7–3.9:1.
   - Chat input border: 1.5:1.
   - `.week-select` border: 1.3:1.
   - Sort arrows and select chevron: 2.4–2.7:1.
   - Petranaki `--danger` used as text: 3.3–4.0:1.
   - Petranaki `--on-accent` on the steel gradient: 4.1:1.
7. **Overlays:**
   - The closed mobile nav is still focusable.
   - SWU Stats Create/Import pop-ups and the Deck Stats matchup pop-up have no dialog role, no Escape key and no focus handling.
   - The game settings dialog has no accessible name.
8. **Fields with no label:**
   - chat input (`ChatPanel.php:32`);
   - WaitingRoom deck textarea;
   - Create/Import inputs and selects;
   - Melee row checkboxes.

   Also, the team-invite `<label for="username">` points at the wrong field (`Profile.php:89`).
9. **No skip link** on any page, and no `<main>` on SWU Stats pages.
10. **Reduced motion:**
    - The Petranaki matchmaking pop-ups' inline infinite animations ignore the reduced-motion setting (`SharedUI/Sites/SWUSim/MainMenu.php:1554,1725,1735`).
    - SWU Stats has no reduced-motion rules at all.

### Image copyright
**Card images and art (biggest risk)**
- Card images and art come from the official card database and are self-hosted. This is standard for fan simulators and is tolerated by the rights holders, not licensed.
- The disclaimer names "Fantasy Flight Games, Disney". **Add Lucasfilm Ltd.**, which owns Star Wars, and consider adding Asmodee, FFG's parent.

**Biggest risk: patron-only features that show card art (SWU Stats)**
- Deck PDFs, images and some views say "You must be a patron…" (`SWUDeck/CreatePDF.php:37`, `CreateImage.php:51`, `TournamentSim.php:36`).
- The card-search API and conversational search are patron-only (`Stats/APIs.php:903`, `AIEndpoints/FullElasticSearch.php:27-28`).
- Asmodee's published IP policy allows fan creations that are "for your own personal use or freely shared with the community". Star Wars itself is licensed from Lucasfilm.
- I found no official SWU rule that allows paywalled fan content. Donations with everything free is the safer pattern.
- **This is a decision for you, not a code fix.**

**Creator deck lists**
- Locked behind the creators' own Patreons (KTOD, Rebel Resource, Stubb Hub, Force Fam).
- This is the creators' content, but it is gated on your site. Make sure you have their permission to show their logos (`Assets/Images/logos/`).

**Art with no recorded source**
- `Assets/Boards/SWUSim`, `Assets/CardBacks/SWUSim` (about 70 community/team backs), `Assets/Playmats/SWUSim` (labelled "Key Art", i.e. official), and `SharedUI/Sites/SWUSim/assets/` (mode and news art).
- Keep a credits file listing where each came from and the permission.

**Fonts**
- `Assets/Soloist.otf` has no licence. It is used on Rebel Resource deck images (`SWUDeck/CreateImage.php:168`). Iconian fonts are usually free for personal use only.
- `StarJedi.ttf` and `ShadowOfXizor.ttf` are unused; delete them.

**AI-generated icons**
- The keyword icons in `Assets/Icons/*.webp` were made with Kling.
- No US law requires labelling them.
- Check that the Kling plan used allows this kind of use.

**Credits**
- Credits currently sit in the privacy policy ("Icon by lutfix", unpkg).
- Move them to a Credits section and add Bootstrap Icons (MIT) and Montserrat.

### Local laws (assuming a US-based operator; confirm)
- **CalOPPA (California):** any site with California users must have a privacy policy, linked clearly from the home page, with an effective date and a Do-Not-Track statement.
  - The home page link is there, through the footer disclaimer.
  - The effective date and Do-Not-Track statement are missing.
  - Many Stats pages and every in-game page have no legal link at all.
- **CCPA/CPRA:** almost certainly doesn't apply. It needs more than $25M revenue, data on 100k+ consumers, or selling data.
- **COPPA:** a Star Wars game draws kids.
  - The 13+ rule is only text in the Terms.
  - Add a "I'm 13 or older" checkbox at signup, and a process to delete accounts you learn belong to under-13s.
- **GDPR / UK GDPR:** applies if you serve EU or UK users, which an international SWU community does. Requires:
  - the policy contents listed under question 1;
  - deletion and export on request within one month;
  - the IP-leak fixes in question 4.
- **ADA / accessibility lawsuits:** these mainly target commercial sites. The risk to a free fan site is low, but the patron-only features make it a little more commercial.
- **AI-assisted code:** no disclosure is required.
