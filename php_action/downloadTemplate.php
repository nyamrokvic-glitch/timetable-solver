<?php
/** A CSV template for importing past timetables, pre-filled with a few real names from this school. */
require_once 'core.php';
$P = load_problem($connect);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="past_timetable_template.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, array('class', 'subject', 'teacher', 'day', 'period', 'room'));
$n = 0;
foreach($P['allocs'] as $a) {
	if($n++ >= 3) { break; }
	fputcsv($out, array($P['classes'][$a['class']]['name'], $P['subjects'][$a['subject']]['name'], (isset($P['teachers'][$a['teacher']]) ? $P['teachers'][$a['teacher']]['name'] : ''), $P['days'][0], 1, ''));
}
fclose($out);
