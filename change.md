# Change Notes

## Scope

This file summarizes the main changes that were made during the local Spotweb work on March 13, 2026.

## Core / Architecture

- `modern` and `we1rdo` were separated into standalone themes.
- `modern` no longer depends on `templates/we1rdo/*` as a parent theme layer.
- Generic theme helper behavior was moved into the shared core helper layer where needed.
- Missing template handling was hardened so template failures no longer silently end in blank output.

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
- A first cleanup pass removed many unnecessary `!important` rules from the modern override CSS.

## Notes

- The SQLite file `spotweb` in the project root is the local runtime database, not a source file.
- At the moment this note was created, the database file may still be the only dirty tracked file because it changes during normal app usage.
