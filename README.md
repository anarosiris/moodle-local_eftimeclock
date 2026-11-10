# Training Evidence: Time Clock (free) (`local_eftimeclock`)

Free plugin for Moodle 5.1+ by eLearning Fácil. It measures each student's **active time** on course activities and
shows it to them in a floating clock:

- **Only real, active time counts.** Time runs while the activity tab is **visible and focused**; a background tab or
  another application pauses it. A focused tab playing a video keeps counting, on purpose.
- **Data you can trust.** Saving is idempotent (visit token + upsert, reliable even with `sendBeacon`), and the server
  never trusts the browser with the amount: a save can only add the seconds that really elapsed since the student's
  previous save, so a hand-made call, a replay or two devices at once cannot inflate the total.
- **Floating clock** on activities (it can be minimised), with two themes: Sapphire and Slate. SCORM packages opened in
  a new window are measured too.
- **Transparency notice** shown once to each student (it can be turned off).
- **Simple report per course**: student, first measured access, last measured access and total time, with
  **Excel and CSV** downloads. With separate groups, each teacher sees only the students of their own groups.
- **Per-course settings** (measure or not, theme, or just follow the site values) and site-wide defaults.
- **Course reset**: resetting a course while unenrolling its students deletes their measured time, so a new edition
  starts from zero; the settings are kept. Download the report first if you need it.
- Privacy API (GDPR), course backup and restore (settings always; time data with "Include enrolled users") and a file
  integrity check of the installation (Site administration → Reports → System status).

Users who can view the report (teachers, managers) and site administrators are **not measured**: they see no clock and
nothing is recorded for them, even when they are also enrolled as students.

Current version: **0.4.2** — see [CHANGES.md](CHANGES.md).

## Documentation

- User guide (PDF, English): [docs/manual/Guide_local_eftimeclock_en.pdf](docs/manual/Guide_local_eftimeclock_en.pdf)
- Guía de uso (PDF, español): [docs/manual/Guia_local_eftimeclock.pdf](docs/manual/Guia_local_eftimeclock.pdf)

## Installation

1. **Back up your site first** (database, moodledata and code) — good practice with any plugin.
2. Site administration → Plugins → Install plugins → upload the ZIP and confirm.
3. By default every course measures time and shows the clock. Site defaults: Site administration → Plugins →
   Local plugins → Time Clock.

Requirements: Moodle 5.1, 5.2 or 5.3 · PHP 8.2+ · MySQL/MariaDB or PostgreSQL.

## Usage

- Course → **More → Time Clock settings**: turn measuring on or off, change the theme or follow the site values.
- Course → **More → Time Clock report**: view and download the measured time.

## Permissions

| Capability | Granted by default to | Notes |
|---|---|---|
| `local/eftimeclock:viewreport` | Teacher, editing teacher, manager | Also grant it to a read-only auditor role if you use one. |
| `local/eftimeclock:manage` | Editing teacher, manager | Configure the clock in the course. |
| `local/eftimeclock:recordtime` | Student | Whose time is measured. |

## Upgrading to the Training Evidence Pack

eLearning Fácil also offers a complete commercial suite (`local_efreport`) with advanced time tracking and reporting.
If it is installed on the same site, Time Clock steps aside automatically (it neither shows the clock nor
records anything, and its pages say so) and the suite imports all the time already measured, so nothing is lost.

## Privacy

Everything stays in your Moodle™ site: the plugin sends no data to eLearning Fácil or to any third party and loads
nothing from external servers. It stores, for each user, the active time measured in each course and activity
(duration and dates of each session) and one user preference recording that the user has seen the notice about the
clock. The plugin implements Moodle's Privacy API, so this data is included in a user's data export and removed by a
data deletion request.

## Support

Report bugs and ask questions in this repository's [issue tracker](https://github.com/anarosiris/moodle-local_eftimeclock/issues).

Developed and maintained by [eLearning Fácil](https://elearningfacil.com), an independent company that builds Moodle™ plugins and themes for training providers and companies. Not affiliated with Moodle Pty Ltd.

## Licence

GNU GPL v3 or later — see [LICENSE](LICENSE). Moodle™ is a registered trademark of Moodle Pty Ltd. SCORM® is a
registered trademark of Advanced Distributed Learning (ADL). eLearning Fácil is not affiliated with either.
