# Change Notes

## Scope

This file summarizes the main changes that were made during the local Spotweb work on March 13 and March 14, 2026.

## Core / Architecture

- `modern` and `we1rdo` were separated into standalone themes.
- `modern` no longer depends on `templates/we1rdo/*` as a parent theme layer.
- Generic theme helper behavior was moved into the shared core helper layer where needed.
- Missing template handling was hardened so template failures no longer silently end in blank output.
- `SpotThemes` is now required explicitly by the core consumers that use it, so installs do not depend on a freshly regenerated Composer classmap to boot successfully.

## Theme Architecture

- Added a shared `SpotThemes` registry to centralize theme discovery, fallback handling and validation.
- Theme lists now merge configured themes with discovered standalone themes from `templates/*/SpotTemplateHelper_*.php`.
- Spotweb now falls back to an available theme, preferring `modern`, when a stored theme no longer exists on disk.
- Added shared helper support for theme asset paths and extra header assets so themes no longer need hardcoded self-references.
- `modern` and `we1rdo` JS loading indicators now use the active theme asset base instead of a hardcoded theme folder.
- Added `bin/validate-theme.php` to validate whether a standalone theme contains the required files.
- Added `handleiding.md` with instructions for creating and registering a new standalone theme.

## User Preferences / Settings

- A user-level `TMDB API key` preference was added to `Voorkeuren wijzigen`.
- The language switch flow was corrected so changing from Dutch to English works again.
- The TMDB key is no longer treated as a global admin setting.

## Retrieval / Backend

- Retrieval now gets a higher runtime memory limit in `retrieve.php`.
- The web retrieve button now shows real feedback for failures such as `already running` or fatal errors instead of always pretending the action succeeded.

## Modern Theme UI

- Maintenance buttons were restyled and stacked vertically again.
- Maintenance can be collapsed again.
- Toolbar button styling was repaired after the CSS cleanup.
- Cards view titles are now clamped so long titles no longer make cards uneven in height.
- Dark mode filter blocks were corrected so they no longer stay light.
- Dark filter background was tuned to `#3b3939`.
- Spot detail pages were tightened up by removing spacer rows and skipping empty `Website` and `Tag` rows.
- Dark mode spot detail and comment views were corrected so legacy light panels no longer bleed through.
- User, preference and management dialogs now follow the modern theme styling instead of falling back to the old jQuery UI look.
- Dialog buttons were enlarged for readability, the close button was made visible again, and dialogs are forced above the sticky toolbar.
- Dark table category bars were toned down so the row colors are less aggressive.
- A first cleanup pass removed many unnecessary `!important` rules from the modern override CSS.

## We1rdo Theme UI

- `Snelle toegang` / quick links were restacked vertically above the filters again.
- The `Snelle toegang` block remains collapsible after the quick link layout fix.

## Notes

- The SQLite file `spotweb` in the project root is the local runtime database, not a source file.
- At the moment this note was created, the database file may still be the only dirty tracked file because it changes during normal app usage.
