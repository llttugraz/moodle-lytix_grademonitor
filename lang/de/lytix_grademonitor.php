<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Grade Monitor plugin for lytix
 *
 * @package    lytix_grademonitor
 * @author     Alexander Kremser
 * @copyright  2022 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Lytix Grade Monitor';
$string['privacy:metadata'] = 'This plugin does not store any data.';

$string['widget_name'] = 'Notenübersicht';

$string['description'] = 'Das <b>Abschlussziel</b> ist die Abschlussnote, die Studierende erreichen <em>wollen</em>; die <b>Selbsteinschätzung</b> ist, was sie sich momentan <em>zutrauen</em>.';

$string['goal'] = 'Dein Ziel';
$string['goal_empty'] = 'Bitte wähle aus dem Drop-Down-Menü, mit welcher Note du den Kurs abschließen möchtest.';
$string['goal_likely'] = 'Du bist auf dem besten Weg, dein Ziel zu erreichen. Nur weiter so!';
$string['goal_unlikely'] = 'Dein aktuelles Ziel scheint gerade außer Reichweite. Überdenke deine Pläne, du schaffst das!';
$string['goal_unachievable'] = 'Leider kannst du dieses Ziel aus aktueller Sicht nicht mehr erreichen. Keine Sorge, denk nach, du findest deinen Weg.';
$string['goal_fail'] = 'Leider kannst du diesen Kurs aus aktueller Sicht nicht mehr bestehen. Keine Panik, konzentriere dich und du findest deinen Weg!';

$string['disclaimer'] = 'Bitte beachte: <i>Learner’s Corner</i> ist noch ein Prototyp. <strong>Fehler in der Notenberechnung sind möglich!</strong> Wende dich im Zweifelsfall an eine:n Lehrende:n.';

$string['scheme_updated'] = 'Das Benotungsschema wurde aktualisiert, am: ';
$string['dismiss'] = 'gesehen';

$string['th_optional_abbr'] = 'frw.';
$string['th_optional'] = 'freiwillig';
$string['th_weight_abbr'] = 'Gew.';
$string['th_weight'] = 'Gewichtung: so groß ist der Anteil an der Gesmatnote';
$string['th_name'] = 'Aufgabe';
$string['th_average'] = '<abbr title="Kursdurchschnitt">Kurs-<br>durchsch.</abbr>';
$string['th_own_result'] = 'Dein<br>Ergebnis';
$string['th_estimation'] = 'Selbsteinschätzung';
$string['th_selection'] = 'Auswahl';
$string['th_result'] = 'Ergebnis';
$string['th_include_abbr'] = 'inkl.';
$string['th_include'] = 'inkludiere in Berechnung der geschätzten Abschlussnote';
$string['th_estimate'] = '<abbr title="Einschätzung">Einsch.</abbr>';
$string['th_percent'] = 'Prozent der Gesamtpunktezahl';
$string['th_goal'] = 'Abschlussziel';
$string['th_grade'] = 'aktuelle Node';
$string['th_students_per_grade'] = 'Studierende pro Note';

$string['points'] = ' Pkte.'; // The space is a thin space.

$string['grade_completion'] = '<b><i>deiner Abschlussnote stehen fest</i></b><br>exkl. freiwilliger Aufgaben';
$string['current_grade'] = 'dein <b>aktueller</b> Notenstand';
$string['class_average'] = 'Kursdurchschnitt';
$string['self_estimation'] = 'selbst geschätzte Abschlussnote';
$string['legend'] = 'Mit einem Plus + gekennzeichnete Aufgaben sind freiweillig: Sie tragen nur zur Gesamtnote bei, wenn diese auch ohne freiwillige Aufgaben positiv wäre.';
$string['show_average'] = 'zeige den Kurs-Durchschnitt pro Aufgabe und im Vergleich zu deiner Gesamtnote';

$string['goal'] = 'Ziel';
$string['estimate_abbr'] = 'Einsc.';
$string['estimate'] = 'Einschätzung';
$string['grade'] = 'Note';
$string['percent'] = 'Prozent';
$string['week_abbr'] = 'KW';
$string['week'] = 'Kalenderwoche';
$string['month'] = 'Monat';
$string['weeks_month'] = 'Wochen pro Monat';
$string['today'] = 'heute';
$string['show'] = 'Zeige';
$string['teacher_description'] = '<p>Die Bedeutung von <b><i>Prozent</i></b> unterscheidet sich zwischen <i>Ziel, Einschätzung und Note;</i> die jeweiligen Werte bantworten die folgenden Fragen.</p><ul><li><b>Ziel</b>: Wie viele Studierende haben ein Ziel festgelegt?</li><li><b>Einschätzung</b>: Wie viele Aufgaben inkludiert der:die typische Studierende in die Selbsteinschätzung?</li><li><b>Note</b>: Wie viele Aufgaben der:des typischen Studierenden wurden bereits benotet?</li></ul>';
// Privacy.
$string['privacy:metadata:lytix_grademonitor'] = "Um das Verhalten von Personen im Kurs zu überwachen,\
 müssen einige Benutzerdaten gespeichert werden";
$string['privacy:metadata:lytix_grademonitor:userid'] = "Die Kursnummer wird gespeichert, um nachvollziehen\
 zu können, von welchem Kurs die Daten erhoben wurden";
$string['privacy:metadata:lytix_grademonitor:courseid'] = "Die Benutzernummer wird gespeichert, um die Person,\
 die den Kurs besucht hat, identifizieren zu können";
$string['privacy:metadata:lytix_grademonitor:goal'] = "Notenziel";
$string['privacy:metadata:lytix_grademonitor:scheme_update'] = "Notenschema verändert";
$string['privacy:metadata:lytix_grademonitor:estimations'] = "Selbsteinschätzung";
$string['privacy:metadata:lytix_grademonitor:show_others'] = "andere Studeirende anzeigen";
$string['privacy:metadata:lytix_grademonitor:dismiss_notification'] = "als gelesen markiert";
$string['privacy:metadata:lytix_grademonitor:timecreated'] = "zeitstempel";
$string['privacy:metadata:lytix_grademonitor:future'] = "Platzhalter für mögliche neue Felder";
