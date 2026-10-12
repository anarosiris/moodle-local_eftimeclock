# Changelog — Active Time Clock (local_eftimeclock)

All notable changes to this plugin are listed here.

## [0.3.1] - 2026-10-13

### Changed
- New name: **Active Time Clock** (Spanish: «Medidor de tiempo real»). The course menu entries are now
  "Active Time Clock settings" and "Active Time Clock report". No changes to capabilities or behaviour.
- README and changelog rewritten in English; user guide in English and Spanish.

## [0.3.0] - 2026-10-08

### Changed
- **The server no longer trusts the duration sent by the browser.** What a save adds is bounded by the server clock:
  never more than the seconds elapsed since the student's previous save (in any visit), and a new visit cannot start
  with more than 5 minutes. A hand-made call, a replay or two devices at once cannot inflate the total.
- Nothing is recorded when the clock is turned off in the course, when the complete suite is installed, or for
  teachers, managers and site administrators, even if they are also enrolled as students.
- The clock starts from the time stored on the server; the browser copy is kept per user (on a shared computer you no
  longer see someone else's total).
- Per-course "Use the site values" option; the cache is refreshed on save.
- Supported Moodle versions: 5.1 to 5.3.

### Added
- **SCORM packages opened in a new window are measured**: the SCORM player shows the clock; the launcher page, without
  focus, does not count.
- **Course reset**: resetting a course while unenrolling its students deletes their measured time; the settings are
  kept. A reset that does not unenrol students changes nothing.
- Report with **separate groups**: without "Access all groups", each teacher sees only the students of their groups.
- Privacy: full metadata, the notice preference is exported, and the data export is readable (activity, seconds,
  dates).

### Fixed
- Time sent with a visit identifier from another course or activity is no longer recorded there.
- A visit token longer than allowed is rejected instead of causing a database error.
- Restoring a backup accepts only known themes and bounds durations like a real save.
- The report lists everyone with measured time in the course even if their role changed; names are escaped; a visit
  with no seconds is not counted as a first access.

## [0.2.2] - 2026-10-03

### Changed
- Documentation only: updated user guide.

## [0.2.1] - 2026-08-11

### Fixed
- The warning on the course deletion confirmation page was partly hidden by fixed theme headers; it now uses Moodle's
  standard notification area.

## [0.2.0] - 2026-08-11

### Added
- When a course is deleted, its measured time is deleted too; the upgrade cleans data left by courses deleted before.
- Warning on the course deletion confirmation page when the course has measured time, reminding you to download it
  first.

## [0.1.7] - 2026-07-26

### Changed
- Maturity raised to stable.

## [0.1.6] - 2026-07-26

### Changed
- Code comments and developer strings in English; README in English.
- Requires Moodle 5.1 or later; tested on MySQL/MariaDB and PostgreSQL.

## [0.1.4] - 2026-07-12

### Fixed
- Accessibility (WCAG AA): contrast of the download buttons and badges.

## [0.1.3] - 2026-07-11

### Changed
- Redesigned report page and clock theme gallery.

## [0.1.1] - 2026-07-10

### Added
- First user guide (PDF).

## [0.1.0] - 2026-07-09

### Added
- First release: active time measured on activities (paused without focus or visibility, protection against clock
  jumps, idempotent saving), floating clock with two themes, transparency notice for students.
- Simple course report (student, first and last measured access, total time) with Excel and CSV downloads.
- Per-course settings (on/off, theme) and site defaults.
- Works alongside the complete suite: if `local_efreport` is installed, this plugin does nothing (one clock, one
  measurement).
- Privacy provider, course backup and restore, file integrity check, English and Spanish strings, CI.
