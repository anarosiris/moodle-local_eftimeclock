<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Simple time report: student, first/last measured access, total time.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_eftimeclock\local\edition;
use local_eftimeclock\local\timeapi;

$courseid = required_param('courseid', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/eftimeclock:viewreport', $context);

$url = new moodle_url('/local/eftimeclock/report.php', ['courseid' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('report_title', 'local_eftimeclock'));
$PAGE->set_heading(format_string($course->fullname));

// Course students: enrolled with a student-archetype role (active or
// suspended enrolment), same criterion as the rest of the family, PLUS
// whoever has measured time in the course (the report never hides time it
// holds because a role changed later).
$namefields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
$students = $DB->get_records_sql(
    "SELECT DISTINCT u.id, $namefields, u.email
       FROM {user} u
       JOIN (SELECT ra.userid
               FROM {role_assignments} ra
               JOIN {role} r ON r.id = ra.roleid AND r.archetype = 'student'
               JOIN {user_enrolments} ue ON ue.userid = ra.userid
               JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
              WHERE ra.contextid = :ctxid
              UNION
             SELECT s.userid
               FROM {local_eftimeclock_session} s
              WHERE s.courseid = :courseid2 AND s.duration > 0) pool ON pool.userid = u.id
      WHERE u.deleted = 0
   ORDER BY u.lastname, u.firstname",
    ['ctxid' => $context->id, 'courseid' => $course->id, 'courseid2' => $course->id]
);

// Separate groups: whoever cannot see all groups only sees the students of
// their own groups (the rule of the whole family of plugins).
$groupmode = groups_get_course_groupmode($course);
if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
    $allowed = [];
    foreach (groups_get_user_groups($course->id, $USER->id)[0] as $groupid) {
        foreach (groups_get_members($groupid, 'u.id') as $member) {
            $allowed[(int) $member->id] = true;
        }
    }
    $students = array_intersect_key($students, $allowed);
}
$summary = timeapi::course_summary($course->id, array_keys($students));

// Report rows (the same ones for screen and for export).
$rows = [];
foreach ($students as $student) {
    $s = $summary[$student->id] ?? null;
    $rows[] = [
        'student' => fullname($student),
        'first'   => $s ? userdate($s->first, '%d/%m/%Y %H:%M') : '—',
        'last'    => $s ? userdate($s->last, '%d/%m/%Y %H:%M') : '—',
        'total'   => $s ? timeapi::format_time($s->total) : '—',
    ];
}
$columns = [
    'student' => get_string('col_student', 'local_eftimeclock'),
    'first'   => get_string('col_firstaccess', 'local_eftimeclock'),
    'last'    => get_string('col_lastaccess', 'local_eftimeclock'),
    'total'   => get_string('col_totaltime', 'local_eftimeclock'),
];

// Excel/CSV export with the core dataformat.
if ($download === 'excel' || $download === 'csv') {
    $format = ($download === 'excel') ? 'excel' : 'csv';
    $filename = 'eftimeclock-' . clean_filename($course->shortname);
    \core\dataformat::download_data(
        $filename,
        $format,
        $columns,
        $rows,
        function (array $row): array {
            return $row;
        }
    );
    die;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report_title', 'local_eftimeclock'));
echo html_writer::tag('p', get_string('report_intro', 'local_eftimeclock'), ['class' => 'text-muted']);
if (\local_eftimeclock\local\config::for_course($course->id)['superseded']) {
    echo $OUTPUT->notification(get_string('superseded_notice', 'local_eftimeclock'), 'info', false);
}

// The website edition also presents the suite (greyed-out items and a closing banner).
$isweb = edition::is_web();
$prourl = edition::suite_url();
$protitle = $isweb ? get_string('pro_gofull', 'local_eftimeclock', edition::suite_host()) : '';

if (!$students) {
    echo html_writer::tag('p', get_string('nostudents', 'local_eftimeclock'));
} else {
    // Top bar: the exports in plain sight (Excel/CSV).
    $toolbar = html_writer::link(
        new moodle_url($url, ['download' => 'excel']),
        get_string('export_excel', 'local_eftimeclock'),
        ['class' => 'btn etc-btn me-2 mb-2']
    );
    $toolbar .= html_writer::link(
        new moodle_url($url, ['download' => 'csv']),
        get_string('export_csv', 'local_eftimeclock'),
        ['class' => 'btn etc-btn me-2 mb-2']
    );
    if ($isweb) {
        // The PDF as a hook: it lights up on hover and links to the suite.
        $toolbar .= html_writer::link(
            $prourl,
            get_string('export_pdf_pro', 'local_eftimeclock')
                . ' ' . html_writer::span(get_string('pro_badge', 'local_eftimeclock'), 'etc-pro-tag ms-1'),
            ['class' => 'btn etc-btn-pro mb-2', 'target' => '_blank', 'rel' => 'noopener', 'title' => $protitle]
        );
    }
    echo html_writer::div($toolbar, 'd-flex flex-wrap align-items-center mt-3');

    if ($isweb) {
        // Filters of the suite (showcase): shown greyed out, they light up on mouse
        // hover and the whole block links to the suite.
        $fakectl = function (string $label): string {
            return html_writer::div(
                html_writer::div($label, 'small fw-bold mb-1')
                . html_writer::div(get_string('choosedots'), 'etc-fakectl'),
                'flex-fill'
            );
        };
        $filters = $fakectl(get_string('pro_filter_from', 'local_eftimeclock'))
            . $fakectl(get_string('pro_filter_to', 'local_eftimeclock'))
            . $fakectl(get_string('group'))
            . $fakectl(get_string('activity'));
        echo html_writer::link(
            $prourl,
            html_writer::div(
                html_writer::div(
                    html_writer::span(get_string('pro_filters_title', 'local_eftimeclock'), 'fw-bold')
                    . ' ' . html_writer::span(get_string('pro_badge', 'local_eftimeclock'), 'etc-pro-tag ms-2'),
                    'mb-2'
                )
                . html_writer::div($filters, 'd-flex flex-wrap gap-3'),
                'etc-pro-card p-3 mb-3'
            ),
            ['class' => 'etc-pro-link', 'target' => '_blank', 'rel' => 'noopener', 'title' => $protitle]
        );
    }

    $table = new html_table();
    $table->head = array_values($columns);
    foreach ($rows as $row) {
        // Cells are HTML: the names go escaped.
        $table->data[] = array_map('s', array_values($row));
    }
    echo html_writer::div(html_writer::table($table), 'etc-card');
}

if ($isweb) {
    // Closing banner that presents the suite.
    $bullets = '';
    foreach (['b1', 'b2', 'b3', 'b4'] as $b) {
        $bullets .= html_writer::tag('li', get_string('pro_banner_' . $b, 'local_eftimeclock'));
    }
    echo html_writer::div(
        html_writer::div(get_string('pro_banner_title', 'local_eftimeclock'), 'etc-banner-title')
        . html_writer::tag('ul', $bullets)
        . html_writer::link(
            $prourl,
            get_string('pro_banner_cta', 'local_eftimeclock') . ' →',
            ['class' => 'etc-banner-cta', 'target' => '_blank', 'rel' => 'noopener']
        )
        . html_writer::span(s(edition::suite_host()), 'ms-3 small'),
        'etc-banner'
    );
} else {
    // Marketplace edition: one plain line that names the full report plugin.
    $fullreport = html_writer::link(
        edition::MARKETPLACE_URL,
        s(get_string('marketplace_more_name', 'local_eftimeclock')),
        ['target' => '_blank', 'rel' => 'noopener']
    );
    echo html_writer::tag(
        'p',
        get_string('marketplace_more', 'local_eftimeclock', $fullreport),
        ['class' => 'small text-muted mt-3', 'id' => 'etc-marketplace-more']
    );
}

echo $OUTPUT->footer();
