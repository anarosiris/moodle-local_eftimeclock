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
 * Per-course clock settings: on/off and theme gallery (free + PRO showcase).
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_eftimeclock\local\config;

$courseid = required_param('courseid', PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/eftimeclock:manage', $context);

$url = new moodle_url('/local/eftimeclock/coursesettings.php', ['courseid' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('coursesettings', 'local_eftimeclock'));
$PAGE->set_heading(format_string($course->fullname));

// Saving (POST with sesskey): the site values, or ON/OFF + theme (only the
// free ones are accepted).
if (data_submitted() && confirm_sesskey()) {
    if (optional_param('usesite', 0, PARAM_INT)) {
        config::clear_course($course->id);
    } else {
        $enabled = (bool) optional_param('enabled', 0, PARAM_INT);
        $theme = optional_param('clocktheme', 'zafiro', PARAM_ALPHA);
        config::save_course($course->id, $enabled, $theme);
    }
    redirect(
        $url,
        get_string('cfg_saved', 'local_eftimeclock'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$cfg = config::for_course($course->id);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coursesettings', 'local_eftimeclock'));
if ($cfg['superseded']) {
    // The PRO suite measures on this site: the settings below are kept, not used.
    echo $OUTPUT->notification(get_string('superseded_notice', 'local_eftimeclock'), 'info', false);
}

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

// Follow the site values (no row of its own) or decide here.
echo html_writer::start_div('form-check my-3');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox', 'class' => 'form-check-input', 'id' => 'etc-usesite',
    'name' => 'usesite', 'value' => 1] + ($cfg['ownsettings'] ? [] : ['checked' => 'checked']));
echo html_writer::tag(
    'label',
    get_string('cfg_usesite', 'local_eftimeclock'),
    ['for' => 'etc-usesite', 'class' => 'form-check-label fw-bold']
);
echo html_writer::tag('div', get_string('cfg_usesite_desc', 'local_eftimeclock'), ['class' => 'text-muted small']);
echo html_writer::end_div();

// ON/OFF (the effective value, so the course starts from what it has today).
echo html_writer::start_div('form-check form-switch my-3');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox', 'class' => 'form-check-input', 'id' => 'etc-enabled',
    'name' => 'enabled', 'value' => 1] + ($cfg['configured'] ? ['checked' => 'checked'] : []));
echo html_writer::tag(
    'label',
    get_string('cfg_enabled', 'local_eftimeclock'),
    ['for' => 'etc-enabled', 'class' => 'form-check-label fw-bold']
);
echo html_writer::tag('div', get_string('cfg_enabled_desc', 'local_eftimeclock'), ['class' => 'text-muted small']);
echo html_writer::end_div();

// Theme gallery: the 2 free ones selectable with their thumbnail; the rest of
// the suite greyed out — it lights up on mouse hover and links to the suite.
$prourl = 'https://elearningfacil.com';
$protitle = get_string('pro_gofull', 'local_eftimeclock');

echo $OUTPUT->heading(get_string('cfg_theme', 'local_eftimeclock'), 4);
echo html_writer::start_div('d-flex flex-wrap gap-3 my-2');
$free = config::free_themes();
foreach (config::all_themes() as $theme) {
    $isfree = in_array($theme, $free, true);
    $label = get_string('theme_' . $theme, 'local_eftimeclock');
    $id = 'etc-theme-' . $theme;

    $inner = html_writer::div('1:23:45', 'etc-thumb etc-prev-' . $theme);
    $inner .= html_writer::empty_tag('input', [
            'type' => 'radio', 'name' => 'clocktheme', 'id' => $id, 'value' => $theme,
            'class' => 'form-check-input me-2',
        ] + ($cfg['theme'] === $theme ? ['checked' => 'checked'] : [])
          + ($isfree ? [] : ['disabled' => 'disabled']));
    $inner .= html_writer::tag('label', $label, ['for' => $id, 'class' => 'form-check-label']);
    if (!$isfree) {
        $inner .= ' ' . html_writer::span(get_string('pro_badge', 'local_eftimeclock'), 'etc-pro-tag ms-1');
    }

    if ($isfree) {
        echo html_writer::div($inner, 'etc-theme-card p-3');
    } else {
        echo html_writer::link(
            $prourl,
            html_writer::div($inner, 'etc-pro-card etc-theme-card p-3'),
            ['class' => 'etc-pro-link', 'target' => '_blank', 'rel' => 'noopener', 'title' => $protitle]
        );
    }
}
echo html_writer::end_div();
echo html_writer::tag('p', get_string('pro_hint_more_themes', 'local_eftimeclock'), ['class' => 'text-muted small']);

echo html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-primary mt-2']);
echo html_writer::end_tag('form');

// PRO settings showcase: what the free plugin does not configure is SHOWN
// (greyed out, as fake controls), lights up on hover and links to elearningfacil.com.
echo $OUTPUT->heading(get_string('pro_more_title', 'local_eftimeclock'), 4, 'mt-4');
echo html_writer::start_div('d-flex flex-wrap gap-3 my-2');
foreach (['mode', 'scope', 'bg', 'caps', 'bbb', 'appearance', 'completion', 'title'] as $item) {
    $card = html_writer::div(
        html_writer::span(get_string('pro_set_' . $item, 'local_eftimeclock'), 'small fw-bold')
        . ' ' . html_writer::span(get_string('pro_badge', 'local_eftimeclock'), 'etc-pro-tag ms-1 float-end'),
        'mb-2'
    );
    $card .= html_writer::div(get_string('pro_set_' . $item . '_v', 'local_eftimeclock'), 'etc-fakectl');
    echo html_writer::link(
        $prourl,
        html_writer::div($card, 'etc-pro-card p-3', ['style' => 'width: 280px;']),
        ['class' => 'etc-pro-link', 'target' => '_blank', 'rel' => 'noopener', 'title' => $protitle]
    );
}
echo html_writer::end_div();

echo $OUTPUT->footer();
