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
 * Spanish strings for the local_eftimeclock plugin.
 *
 * @package    local_eftimeclock
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['banner_ok'] = 'Entendido';
$string['banner_text'] = 'Este curso registra tu tiempo de conexión en las actividades para que puedas ver tu progreso. El reloj flotante muestra tu tiempo acumulado. Al continuar, lo aceptas.';
$string['cfg_enabled'] = 'Medir el tiempo y mostrar el reloj';
$string['cfg_enabled_desc'] = 'Los estudiantes ven un reloj flotante en las actividades de este curso y su tiempo activo queda registrado. El tiempo solo cuenta mientras la pestaña de la actividad está visible y con el foco.';
$string['cfg_saved'] = 'Configuración del reloj guardada.';
$string['cfg_theme'] = 'Tema del reloj';
$string['cfg_usesite'] = 'Usar los valores del sitio';
$string['cfg_usesite_desc'] = 'Este curso sigue los ajustes del sitio (medir o no, tema). Desmarca para decidirlo aquí.';
$string['checkfiles'] = 'Integridad de los ficheros del plugin';
$string['checkfiles_badmanifest'] = 'No se pudo leer el manifiesto de integridad.';
$string['checkfiles_corrupt'] = 'vacío o corrupto';
$string['checkfiles_error'] = '{$a} fichero(s) del plugin ausentes, vacíos o corruptos. La instalación está incompleta.';
$string['checkfiles_fix'] = 'Reinstale el plugin desde su zip. Si se repite, extraiga el zip con el gestor de archivos del hosting en vez del instalador web, y purgue las cachés.';
$string['checkfiles_missing'] = 'ausente';
$string['checkfiles_nomanifest'] = 'No hay manifiesto de integridad (instalación de desarrollo): nada que verificar.';
$string['checkfiles_ok'] = 'Los {$a} ficheros del plugin están presentes e íntegros.';
$string['col_firstaccess'] = 'Primer acceso medido';
$string['col_lastaccess'] = 'Último acceso medido';
$string['col_student'] = 'Estudiante';
$string['col_totaltime'] = 'Tiempo total';
$string['coursedelete_warning'] = 'Este curso tiene tiempo registrado por el Medidor de tiempo real. Al borrar el curso, esos datos se eliminan definitivamente. Si los necesitas, expórtalos ANTES de borrar el curso.';
$string['coursedelete_warning_title'] = 'Atención: este curso tiene tiempo medido';
$string['coursesettings'] = 'Configuración del Medidor de tiempo real';
$string['eftimeclock:manage'] = 'Configurar el Medidor de tiempo real del curso';
$string['eftimeclock:recordtime'] = 'Que se le mida el tiempo en las actividades (estudiantes)';
$string['eftimeclock:viewreport'] = 'Ver el informe del Medidor de tiempo real';
$string['export_csv'] = 'Descargar CSV';
$string['export_excel'] = 'Descargar Excel';
$string['export_pdf_pro'] = 'PDF listo para auditoría';
$string['nodata'] = 'Todavía no hay tiempo medido.';
$string['nostudents'] = 'No hay estudiantes matriculados en este curso.';
$string['pluginname'] = 'Seguimiento de la Formación: Medidor de tiempo (gratuito)';
$string['privacy:metadata:local_eftimeclock_session'] = 'Tiempo activo medido por usuario y actividad.';
$string['privacy:metadata:local_eftimeclock_session:clienttoken'] = 'El identificador de la visita generado por el navegador (agrupa los guardados de una misma visita).';
$string['privacy:metadata:local_eftimeclock_session:cmid'] = 'La actividad donde se dedicó el tiempo.';
$string['privacy:metadata:local_eftimeclock_session:courseid'] = 'El curso de la actividad.';
$string['privacy:metadata:local_eftimeclock_session:duration'] = 'Segundos activos de la visita.';
$string['privacy:metadata:local_eftimeclock_session:timecreated'] = 'Cuándo empezó la visita.';
$string['privacy:metadata:local_eftimeclock_session:timemodified'] = 'Cuándo se guardó la visita por última vez.';
$string['privacy:metadata:local_eftimeclock_session:userid'] = 'El usuario cuyo tiempo se midió.';
$string['privacy:metadata:preference:consent'] = 'Si el usuario ha visto el aviso de medición.';
$string['pro_badge'] = 'PRO';
$string['pro_banner_b1'] = 'Porcentaje sobre las horas comunicadas y detalle por actividad';
$string['pro_banner_b2'] = 'Tiempo en segundo plano: vídeos, recursos externos y videoconferencia BBB';
$string['pro_banner_b3'] = 'Finalización por tiempo, filtros y bloque de progreso del estudiante';
$string['pro_banner_b4'] = 'Informes PDF con diseño y expediente listo para entregar al inspector';
$string['pro_banner_cta'] = 'Descubre el Pack Formación Bonificada Pro';
$string['pro_banner_title'] = 'El reloj te dice cuánto tiempo dedican. El Pack Formación Bonificada Pro te lo deja demostrado.';
$string['pro_filter_from'] = 'Desde';
$string['pro_filter_to'] = 'Hasta';
$string['pro_filters_title'] = 'Filtros, % sobre las horas del curso y detalle por actividad';
$string['pro_gofull'] = 'Incluido en el Pack Formación Bonificada Pro de eLearning Fácil — haz clic para conocerlo (elearningfacil.com)';
$string['pro_hint_more_themes'] = 'Los 7 temas restantes vienen con el Pack Formación Bonificada Pro de eLearning Fácil.';
$string['pro_more_title'] = 'En el Pack Formación Bonificada Pro además configuras…';
$string['pro_set_appearance'] = 'Apariencia completa del reloj';
$string['pro_set_appearance_v'] = 'Tamaño, colores, posición y minimizado';
$string['pro_set_bbb'] = 'Videoconferencia BBB';
$string['pro_set_bbb_v'] = 'Tiempo real de sesión, con detalle por rol';
$string['pro_set_bg'] = 'Tiempo en segundo plano';
$string['pro_set_bg_v'] = 'Recursos externos y BBB siguen contando';
$string['pro_set_caps'] = 'Topes por tipo de recurso';
$string['pro_set_caps_v'] = 'SCORM, herramienta externa, URL, recurso…';
$string['pro_set_completion'] = 'Finalización por tiempo';
$string['pro_set_completion_v'] = 'La actividad se completa al cumplir los minutos';
$string['pro_set_mode'] = 'Modo de seguimiento';
$string['pro_set_mode_v'] = 'Medir sin mostrar el reloj';
$string['pro_set_scope'] = 'Alcance de la medición';
$string['pro_set_scope_v'] = 'Actividades y portada del curso';
$string['pro_set_title'] = 'Título del informe';
$string['pro_set_title_v'] = 'Personalizado con el nombre de tu entidad';
$string['report_intro'] = 'Tiempo activo medido en las actividades de este curso. El tiempo solo cuenta mientras el estudiante mantiene la actividad visible y con el foco.';
$string['report_title'] = 'Informe del Medidor de tiempo real';
$string['reset_note'] = 'Reiniciar el curso dando de baja a los estudiantes borra su tiempo medido (una edición nueva empieza de cero).';
$string['setting_clocktheme'] = 'Tema del reloj por defecto';
$string['setting_clocktheme_desc'] = 'Tema del reloj flotante para los cursos sin ajuste propio.';
$string['setting_default_enabled'] = 'Medir el tiempo por defecto';
$string['setting_default_enabled_desc'] = 'Si los cursos nuevos miden el tiempo y muestran el reloj. Cada curso puede cambiarlo.';
$string['setting_showbanner'] = 'Mostrar el aviso de medición';
$string['setting_showbanner_desc'] = 'Muestra a cada estudiante, una sola vez, un aviso explicando que su tiempo en las actividades se está midiendo (transparencia).';
$string['status_connected'] = 'conectado';
$string['status_paused'] = 'en pausa';
$string['superseded_notice'] = 'En este sitio está instalado Pack Formación Bonificada Pro (local_efreport): él mide el tiempo y muestra su propio reloj, así que el Medidor de tiempo real no muestra ni registra nada. Los ajustes y el tiempo medido antes se conservan.';
$string['theme_atardecer'] = 'Atardecer';
$string['theme_bosque'] = 'Bosque';
$string['theme_circulo'] = 'Círculo';
$string['theme_claro'] = 'Claro';
$string['theme_electrico'] = 'Eléctrico';
$string['theme_minimo'] = 'Mínimo';
$string['theme_pildora'] = 'Píldora';
$string['theme_pizarra'] = 'Pizarra';
$string['theme_zafiro'] = 'Zafiro';
$string['widget_expand'] = 'Restaurar reloj';
$string['widget_hint'] = 'Tu tiempo activo en las actividades del curso.';
$string['widget_minimize'] = 'Minimizar reloj';
$string['widget_title'] = 'Tiempo de conexión';
