<?php
/**
 * Runs the solver. Saves the result as a DRAFT timetable.
 * Optionally also runs a generic solver (no learning) so the school can see
 * how much closer the learned timetable is to what it actually uses.
 */
require_once 'core.php';
require_once '../includes/learner.php';
require_once '../includes/solver.php';
require_once '../includes/validator.php';
require_admin_json();

if(!$_POST) { json_out(array('success' => false, 'messages' => 'No data')); }

$name = trim(pv('name'));
$term = trim(pv('term'));
$limit = max(2, min(90, (float)pv('time_limit')));
$useHistory = !empty($_POST['use_history']);
$compare = !empty($_POST['compare']);
$seed = trim(pv('seed')) === '' ? null : (int)pv('seed');
if($name === '') { $name = 'Timetable ' . date('Y-m-d H:i'); }

set_time_limit((int)($limit * 3 + 60));
ini_set('memory_limit', '512M');

$P = load_problem($connect);
$pre = preflight_checks($P);
$blocking = array_filter($pre, function($x) { return $x[0] === 'error'; });

$learner = $useHistory ? Learner::fromDb($connect, $P['D'], $P['P']) : null;
$evalModel = Learner::fromDb($connect, $P['D'], $P['P']);   // always used to MEASURE habit match, even for the generic run
if($learner && !$learner->hasHistory()) { $learner = null; }

$solver = new TimetableSolver($P, $learner);
$res = $solver->solve($limit, $seed);

$pairs = function($lessons) { return array_map(function($l) { return array($l['subject'], $l['period'] - 1); }, $lessons); };
$habit = $evalModel->hasHistory() ? $evalModel->habitMatch($pairs($res['lessons'])) : 0;

$baseline = null;
if($compare && $evalModel->hasHistory()) {
	$gen = new TimetableSolver($P, null);
	$gres = $gen->solve(max(2, $limit / 2), $seed);
	$baseline = array('habit_match' => $evalModel->habitMatch($pairs($gres['lessons'])), 'unplaced' => count($gres['unplaced']));
}

$notes = ($learner ? 'Learned from ' . $learner->nTimetables . ' past timetable(s). ' : 'Generic run (no school history used). ')
	. 'Solver: ' . $res['iterations'] . ' improvement steps.' . ($baseline ? ' Generic baseline habit match: ' . round(100 * $baseline['habit_match'], 1) . '%.' : '');
$id = save_timetable($connect, $name, $term, 'generated', 'draft', $res['lessons'], $res['cost'], $habit, count($res['unplaced']), $res['seconds'], $notes);

$v = validate_timetable($connect, $id, $P);

$unplaced = array();
foreach($res['unplaced'] as $u) {
	$unplaced[] = $P['classes'][$u['class']]['name'] . ' - ' . $P['subjects'][$u['subject']]['name'] . ' (' . (isset($P['teachers'][$u['teacher']]) ? $P['teachers'][$u['teacher']]['name'] : 'no teacher') . ', ' . $u['size'] . ' period' . ($u['size'] > 1 ? 's' : '') . '): ' . $u['reason'];
}
$manual = (float)get_setting($connect, 'manual_hours', '40');

json_out(array(
	'success' => true, 'messages' => 'Timetable generated', 'timetable_id' => $id,
	'lessons' => count($res['lessons']), 'unplaced' => $unplaced, 'seconds' => round($res['seconds'], 1),
	'cost' => round($res['cost'], 1), 'habit_match' => round(100 * $habit, 1),
	'baseline' => $baseline ? array('habit_match' => round(100 * $baseline['habit_match'], 1), 'unplaced' => $baseline['unplaced']) : null,
	'used_history' => (bool)$learner, 'history_timetables' => $evalModel->nTimetables,
	'hard' => $v['stats']['hard'], 'soft' => $v['stats']['soft'], 'teacher_gaps' => $v['stats']['teacher_gap_periods'],
	'avoid_hits' => $v['stats']['avoid_hits'], 'class_gaps' => $v['stats']['class_gap_periods'], 'repeats' => $v['stats']['repeat_subject_days'],
	'hours_saved' => round(max(0, $manual - $res['seconds'] / 3600), 1), 'manual_hours' => $manual,
	'preflight' => array_values($pre), 'blocking' => count($blocking),
));
