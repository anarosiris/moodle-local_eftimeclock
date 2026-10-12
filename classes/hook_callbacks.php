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
 * Hook callbacks: injects the clock widget and the tracking JavaScript.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eftimeclock;

use local_eftimeclock\local\config;
use local_eftimeclock\local\timeapi;

/**
 * Injects the measurement notice, the clock and the counting JavaScript into
 * ACTIVITY pages (the free plugin only measures activities). Counting pauses
 * without focus/visibility, counts by clock difference (not by ticks), caps
 * jumps (sleeping computer) and saves idempotently.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Course deletion confirmation: warns that the plugin data goes too.
     *
     * Shown only when the course holds auditable evidence of this plugin.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook
     */
    public static function before_standard_top_of_body_html_generation(
        \core\hook\output\before_standard_top_of_body_html_generation $hook
    ): void {
        global $PAGE;
        if (!$PAGE->has_set_url() || strpos($PAGE->url->get_path(), '/course/delete.php') === false) {
            return;
        }
        $courseid = (int) ($PAGE->url->param('id') ?? 0);
        if ($courseid <= 0 || !\local_eftimeclock\local\coursedelete::has_data($courseid)) {
            return;
        }
        // Standard notifications area: always below the fixed header, in any
        // theme (injecting at top of body got covered by Boost's navbar).
        \core\notification::add(
            \html_writer::tag('strong', get_string('coursedelete_warning_title', 'local_eftimeclock'))
            . \html_writer::empty_tag('br')
            . get_string('coursedelete_warning', 'local_eftimeclock'),
            \core\output\notification::NOTIFY_ERROR
        );
    }

    /**
     * Runs when the footer of each page is generated.
     *
     * @param \core\hook\output\before_footer_html_generation $hook Footer hook.
     * @return void
     */
    public static function before_footer_html_generation(
        \core\hook\output\before_footer_html_generation $hook
    ): void {
        global $PAGE, $USER;

        // If the PRO suite is installed, the free plugin steps aside: a single
        // clock and a single measurement (coexistence during migration). It only
        // checks whether the class exists; it never calls anything from PRO.
        if (config::superseded()) {
            return;
        }

        // Do not inject inside embedded iframes (e.g. H5P uses pagelayout
        // 'embedded'): it would duplicate the clock and the measurement. The
        // one exception is the SCORM player in its own window: there the
        // launcher page has no focus (it does not count) and the player is
        // where the student really is.
        if ($PAGE->pagelayout === 'embedded' && !self::is_scorm_player()) {
            return;
        }

        // Only activity pages of a real course.
        $courseid = (int) $PAGE->course->id;
        if ($courseid <= 0 || $courseid == SITEID) {
            return;
        }
        if ((int) $PAGE->context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        if (!isloggedin() || isguestuser()) {
            return;
        }

        $coursecontext = \context_course::instance($courseid);

        // Only enrolled students; not teaching/management staff nor admins.
        if (!is_enrolled($coursecontext, $USER->id, '', true)) {
            return;
        }
        if (has_capability('local/eftimeclock:viewreport', $coursecontext)) {
            return;
        }
        if (is_siteadmin($USER->id)) {
            return;
        }

        $cfg = config::for_course($courseid);
        if (!$cfg['enabled']) {
            return;
        }

        $cmid = (int) $PAGE->context->instanceid;
        $html = '';

        // Measurement notice (transparency), only once per user.
        if (
            (bool) get_config('local_eftimeclock', 'showbanner')
                && !get_user_preferences('local_eftimeclock_consent', false, $USER->id)
        ) {
            $html .= self::banner_html();
            $html .= self::consent_js_inline();
        }

        // Visible clock + counting JS. The displayed total starts from what the
        // server says (same API as the report: they never count differently).
        $base = timeapi::get_course_time((int) $USER->id, $courseid);
        $html .= self::widget_html($cfg['theme']);
        $html .= self::timer_js_inline(
            $courseid,
            $cmid,
            $base,
            get_string('status_connected', 'local_eftimeclock'),
            get_string('status_paused', 'local_eftimeclock'),
            (int) $USER->id
        );

        $hook->add_html($html);
    }

    /**
     * Whether the current page is the SCORM player (opened in its own window
     * it uses the embedded layout, and it is the page to measure).
     *
     * @return bool
     */
    protected static function is_scorm_player(): bool {
        global $PAGE;
        return $PAGE->has_set_url() && substr($PAGE->url->get_path(), -strlen('/mod/scorm/player.php')) === '/mod/scorm/player.php';
    }

    /**
     * HTML of the measurement notice (GDPR transparency).
     *
     * @return string
     */
    protected static function banner_html(): string {
        $text = get_string('banner_text', 'local_eftimeclock');
        $ok   = get_string('banner_ok', 'local_eftimeclock');

        return '
        <div id="etc-consent" class="etc-consent" role="alertdialog" aria-live="polite">
            <span class="etc-consent-text">' . s($text) . '</span>
            <button type="button" id="etc-consent-ok" class="etc-consent-ok">'
                . s($ok) .
            '</button>
        </div>';
    }

    /**
     * HTML of the floating clock widget (bottom-right, minimisable).
     *
     * The theme is a closed-list key resolved on the server and is emitted
     * ONLY as a CSS class.
     *
     * @param string $theme Clock theme ('zafiro' | 'pizarra').
     * @return string
     */
    protected static function widget_html(string $theme): string {
        $title    = get_string('widget_title', 'local_eftimeclock');
        $hint     = get_string('widget_hint', 'local_eftimeclock');
        $minimize = get_string('widget_minimize', 'local_eftimeclock');
        $expand   = get_string('widget_expand', 'local_eftimeclock');

        return '
        <div id="etc-widget" class="etc-widget etc-on etc-t-' . s($theme) . '" role="status"
             aria-live="polite" tabindex="0" title="' . s($hint) . '">
            <button id="etc-toggle" class="etc-toggle" type="button" aria-label="' . s($minimize) . '">&#x2715;</button>
            <div id="etc-wbody" class="etc-wbody">
                <div class="etc-title">' . s($title) . '</div>
                <div id="etc-display" class="etc-display">00:00:00</div>
                <div id="etc-status" class="etc-status etc-active"><span class="etc-sdot" aria-hidden="true"></span><span
                    class="etc-stext" id="etc-stext">' . s(get_string('status_connected', 'local_eftimeclock')) . '</span></div>
            </div>
        </div>
        <script>
        (function() {
            var w   = document.getElementById("etc-widget");
            var btn = document.getElementById("etc-toggle");
            if (!w || !btn) { return; }
            var KEY    = "etc_min";
            var lblMin = ' . json_encode($minimize) . ';
            var lblExp = ' . json_encode($expand) . ';
            var hint   = w.getAttribute("title");
            var min = false;
            try { min = localStorage.getItem(KEY) === "1"; } catch(e) {}
            function apply() {
                if (min) {
                    w.classList.add("etc-min");
                    w.setAttribute("title", lblExp);
                    btn.setAttribute("aria-label", lblExp);
                } else {
                    w.classList.remove("etc-min");
                    w.setAttribute("title", hint);
                    btn.setAttribute("aria-label", lblMin);
                }
            }
            apply();
            btn.addEventListener("click", function(e) {
                e.stopPropagation();
                min = true;
                try { localStorage.setItem(KEY, "1"); } catch(e2) {}
                apply();
            });
            w.addEventListener("click", function(e) {
                e.stopPropagation();
                if (min) {
                    min = false;
                    try { localStorage.setItem(KEY, "0"); } catch(e2) {}
                    apply();
                }
            });
        })();
        </script>';
    }

    /**
     * Inline JS for the time counting.
     *
     * @param int $courseid Course.
     * @param int $cmid Activity.
     * @param int $base Seconds already accumulated in the course.
     * @param string $connected Active status text.
     * @param string $paused Paused status text.
     * @param int $userid The student (the browser-side total is theirs alone).
     * @return string
     */
    protected static function timer_js_inline(
        int $courseid,
        int $cmid,
        int $base,
        string $connected,
        string $paused,
        int $userid = 0
    ): string {
        $endpoint = (new \moodle_url('/lib/ajax/service.php', ['sesskey' => sesskey()]))->out(false);

        return '
        <script>
        (function() {
            var courseid = ' . json_encode($courseid) . ', cmid = ' . json_encode($cmid) . ',
                endpoint = ' . json_encode($endpoint) . ', connected = ' . json_encode($connected) . ',
                paused = ' . json_encode($paused) . ', serverBase = ' . json_encode($base) . ';

            // "seconds" = time of THIS visit (what is saved to the DB).
            // "displayTotal" = course total that is SHOWN: what the server
            // holds plus this visit (the server is the truth: a shared
            // computer or a course reset never shows the total of another
            // person or of an old edition). localStorage only remembers the
            // figure of this user to bridge the page change until the next save.
            var seconds = 0, sessionId = 0, syncing = false;
            var storeKey = "etc_total_" + courseid + "_" + ' . json_encode($userid) . ';

            // Visit token: identifies this counting session on the server.
            // The duration is stored cumulatively with max(); all submissions of
            // a visit must land on the same row. sendBeacon cannot read the
            // response (it never learns the sessionid), so without a token each
            // beacon would insert a new row and the report would over-count.
            function newToken() {
                try {
                    if (window.crypto && crypto.getRandomValues) {
                        var a = new Uint8Array(16), s = "";
                        crypto.getRandomValues(a);
                        for (var i = 0; i < a.length; i++) {
                            s += ("0" + a[i].toString(16)).slice(-2);
                        }
                        return s;
                    }
                } catch(e) {}
                return (Date.now().toString(36) + Math.random().toString(36).slice(2)).slice(0, 32);
            }
            var clientToken = newToken();

            // Visual starting point: what the server says. The browser copy
            // only fills the gap of the seconds not yet saved on a page change
            // (bounded: a minute at most, the sync interval).
            var stored = 0;
            try { stored = parseInt(localStorage.getItem(storeKey) || "0", 10) || 0; } catch(e) {}
            var displayTotal = (stored > serverBase && stored - serverBase <= 60) ? stored : serverBase;
            try { localStorage.setItem(storeKey, displayTotal); } catch(e) {}

            var displayEl = document.getElementById("etc-display");
            var statusEl = document.getElementById("etc-status");

            function pad(n){return (n<10?"0":"")+n;}
            function render(){
                if(!displayEl)return;
                var h=Math.floor(displayTotal/3600),m=Math.floor((displayTotal%3600)/60),s=displayTotal%60;
                displayEl.textContent=pad(h)+":"+pad(m)+":"+pad(s);
            }
            function setStatus(a){if(!statusEl)return;
                var st=document.getElementById("etc-stext");
                if(st){st.textContent=(a?connected:paused);}
                var wd=document.getElementById("etc-widget");
                if(wd){wd.classList.toggle("etc-on",a);wd.classList.toggle("etc-off",!a);}
                statusEl.className="etc-status "+(a?"etc-active":"etc-paused");}

            function payload(){
                return JSON.stringify([{index:0,methodname:"local_eftimeclock_record_time",
                    args:{courseid:courseid,cmid:cmid,sessionid:sessionId,duration:seconds,clienttoken:clientToken}}]);
            }
            function syncXHR(){
                if(syncing)return; syncing=true;
                var x=new XMLHttpRequest();
                x.open("POST",endpoint,true);
                x.setRequestHeader("Content-Type","application/json");
                x.onreadystatechange=function(){
                    if(x.readyState===4){syncing=false;
                        try{var r=JSON.parse(x.responseText);
                            if(r&&r[0]&&r[0].data){sessionId=r[0].data.sessionid||sessionId;}}catch(e){}}
                };
                x.send(payload());
            }
            function syncBeacon(){
                try{
                    if(navigator.sendBeacon){
                        navigator.sendBeacon(endpoint, new Blob([payload()], {type:"application/json"}));
                    } else { syncXHR(); }
                }catch(e){}
            }

            // --- Presence detection ----------------------------------------
            // Active = tab VISIBLE and window FOCUSED. document.hasFocus()
            // also returns true with the focus INSIDE an iframe of this page
            // (video, H5P, SCORM); false when moving to another window or app.
            function isActive(){ return !document.hidden && document.hasFocus(); }

            // At the instant of blur some browsers report a transient state:
            // check after a short wait (the tick corrects it).
            var presenceTimer = null;
            function refreshPresence(){
                if(presenceTimer){ clearTimeout(presenceTimer); }
                presenceTimer = setTimeout(function(){
                    presenceTimer = null;
                    var active = isActive();
                    setStatus(active);
                    if(!active){ syncBeacon(); }   // On leaving, save the accumulated time.
                }, 80);
            }
            document.addEventListener("visibilitychange", refreshPresence);
            window.addEventListener("blur",  refreshPresence);
            window.addEventListener("focus", refreshPresence);
            window.addEventListener("pagehide",function(){ syncBeacon(); });
            // MOBILE: browsers FREEZE the tab in the background (Page
            // Lifecycle) and the interval stops running: save beforehand.
            document.addEventListener("freeze", function(){ syncBeacon(); });
            document.addEventListener("visibilitychange", function(){
                if (document.hidden) { syncBeacon(); }
            });

            // --- Counting by timestamps ------------------------------------
            // Real clock difference between ticks, only while active.
            // 120 s cap on jumps: a suspended computer is not attention.
            var lastTs = Date.now(), lastSync = 0, lastActive = isActive();

            setInterval(function(){
                var now = Date.now();
                var raw = Math.round((now - lastTs) / 1000);
                lastTs = now;
                if(raw < 0){ raw = 0; }
                var delta = raw > 120 ? 120 : raw;

                var active = isActive();
                if(active && lastActive && delta > 0){
                    seconds += delta;
                    displayTotal += delta;
                    try{ localStorage.setItem(storeKey, displayTotal); }catch(e){}
                    render();
                    if(seconds - lastSync >= 60){ lastSync = seconds; syncXHR(); }
                }
                if(active !== lastActive){
                    lastActive = active;
                    setStatus(active);
                }
            },1000);

            setStatus(isActive());
            render();
        })();
        </script>';
    }

    /**
     * Inline JavaScript to record that the user has seen the notice.
     *
     * @return string
     */
    protected static function consent_js_inline(): string {
        $endpoint = (new \moodle_url('/lib/ajax/service.php', ['sesskey' => sesskey()]))->out(false);

        return '
        <script>
        (function() {
            var endpoint = ' . json_encode($endpoint) . ';
            var btn = document.getElementById("etc-consent-ok");
            var banner = document.getElementById("etc-consent");
            if (!btn || !banner) return;
            btn.addEventListener("click", function() {
                banner.style.display = "none";
                var x = new XMLHttpRequest();
                x.open("POST", endpoint, true);
                x.setRequestHeader("Content-Type", "application/json");
                x.send(JSON.stringify([{index:0,methodname:"local_eftimeclock_acknowledge_consent",args:{}}]));
            });
        })();
        </script>';
    }
}
