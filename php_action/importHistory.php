<?php
/**
 * Import a past timetable from CSV (columns: class, subject, day, period [, teacher] [, room]).
 * The teacher column is optional: when blank or missing, the teacher is taken from the
 * allocation for that class + subject (no teacher at all for class-supervised subjects).
 * Names are matched case-insensitively against class name, subject name/code and teacher name/code.
 * Rows that cannot be matched are reported, never silently dropped.
 */
require_once 'core.php';
require_admin_json();

if(empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) { json_out(array('success' => false, 'messages' => 'Please choose a CSV file')); }
$name = trim(pv('name')); $term = trim(pv('term'));
if($name === '') { $name = 'Imported ' . date('Y-m-d'); }
$learn = !empty($_POST['learn']);

$P = load_problem($connect);
$cMap = array(); foreach($P['classes'] as $id => $c) { $cMap[mb_strtolower(trim($c['name']))] = $id; }
$sMap = array(); foreach($P['subjects'] as $id => $s) { $sMap[mb_strtolower(trim($s['name']))] = $id; if($s['code'] !== '') { $sMap[mb_strtolower($s['code'])] = $id; } }
$tMap = array(); foreach($P['teachers'] as $id => $t) { $tMap[mb_strtolower(trim($t['name']))] = $id; if($t['code'] !== '') { $tMap[mb_strtolower($t['code'])] = $id; } }
$rMap = array(); foreach($P['rooms'] as $id => $r) { $rMap[mb_strtolower(trim($r['name']))] = $id; }
$aMap = array(); foreach($P['allocs'] as $a) { $aMap[$a['class'] . '|' . $a['subject']] = $a['teacher']; }
$dMap = array(); foreach($P['days'] as $i => $d) { $dMap[mb_strtolower($d)] = $i + 1; }

$fh = fopen($_FILES['file']['tmp_name'], 'r');
$header = fgetcsv($fh);
if(!$header) { json_out(array('success' => false, 'messages' => 'The file is empty')); }
$cols = array();
foreach($header as $i => $h) { $cols[strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h)))] = $i; }
foreach(array('class', 'subject', 'day', 'period') as $need) {
	if(!isset($cols[$need])) { json_out(array('success' => false, 'messages' => 'Missing column "' . $need . '". Use the template: class, subject, teacher, day, period, room (teacher and room are optional)')); }
}

$lessons = array(); $problems = array(); $line = 1; $skipped = 0;
while(($row = fgetcsv($fh)) !== false) {
	$line++;
	if(count($row) == 1 && trim($row[0]) === '') { continue; }
	$g = function($k) use ($row, $cols) { return isset($cols[$k]) && isset($row[$cols[$k]]) ? trim($row[$cols[$k]]) : ''; };
	$c = $g('class'); $s = $g('subject'); $t = $g('teacher'); $dRaw = $g('day'); $p = (int)$g('period'); $room = $g('room');
	$err = array();
	if(!isset($cMap[mb_strtolower($c)])) { $err[] = 'unknown class "' . $c . '"'; }
	if(!isset($sMap[mb_strtolower($s)])) { $err[] = 'unknown subject "' . $s . '"'; }
	$d = ctype_digit($dRaw) ? (int)$dRaw : (isset($dMap[mb_strtolower($dRaw)]) ? $dMap[mb_strtolower($dRaw)] : (isset($dMap[mb_strtolower(substr($dRaw, 0, 3))]) ? $dMap[mb_strtolower(substr($dRaw, 0, 3))] : 0));
	if($d < 1 || $d > $P['D']) { $err[] = 'unknown day "' . $dRaw . '"'; }
	if($p < 1 || $p > $P['P']) { $err[] = 'period must be 1-' . $P['P']; }
	$cid = isset($cMap[mb_strtolower($c)]) ? $cMap[mb_strtolower($c)] : 0; $sid = isset($sMap[mb_strtolower($s)]) ? $sMap[mb_strtolower($s)] : 0;
	$tid = null;
	if($t !== '') {
		if(isset($tMap[mb_strtolower($t)])) { $tid = $tMap[mb_strtolower($t)]; } else { $err[] = 'unknown teacher "' . $t . '"'; }
	} elseif($cid && $sid) {
		if(isset($aMap[$cid . '|' . $sid])) { $tid = $aMap[$cid . '|' . $sid]; } else { $err[] = 'no allocation for ' . $c . ' ' . $s . ' to infer the teacher from'; }
	}
	if($err) { $skipped++; if(count($problems) < 25) { $problems[] = "Line $line: " . implode(', ', $err); } continue; }
	$lessons[] = array('class' => $cMap[mb_strtolower($c)], 'subject' => $sMap[mb_strtolower($s)], 'teacher' => $tid,
		'room' => ($room !== '' && isset($rMap[mb_strtolower($room)])) ? $rMap[mb_strtolower($room)] : null, 'day' => $d, 'period' => $p);
}
fclose($fh);

if(!$lessons) { json_out(array('success' => false, 'messages' => 'No usable rows. ' . implode(' | ', array_slice($problems, 0, 3)), 'problems' => $problems)); }
$id = save_timetable($connect, $name, $term, 'imported', $learn ? 'historical' : 'archived', $lessons, 0, 0, 0, 0, 'Imported from CSV (' . count($lessons) . ' lessons).');
json_out(array('success' => true, 'messages' => 'Imported ' . count($lessons) . ' lessons' . ($skipped ? ", skipped $skipped row(s)" : '') . '.' . ($learn ? ' The solver will learn from it.' : ''),
	'timetable_id' => $id, 'problems' => $problems));
