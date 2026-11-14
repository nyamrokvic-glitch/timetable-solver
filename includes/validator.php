<?php
/**
 * Independent referee for a saved timetable. It does NOT trust the solver:
 * it re-reads the lessons from the database and checks every hard rule, then
 * reports softer problems. Used by the report page, after every manual move,
 * and by the self-test.
 *
 * Returns array(
 *   'hard' => [ [type, message, class_id, teacher_id, room_id, day, period], ... ],
 *   'soft' => [ ... ],
 *   'stats'=> counts
 * )
 */
function validate_timetable($connect, $ttId, $P = null) {
	if($P === null) { $P = load_problem($connect); }
	$lessons = db_all($connect, "SELECT * FROM timetable_lessons WHERE timetable_id = ? ORDER BY day_no, period_no", 'i', array($ttId));
	return validate_lessons($lessons, $P);
}

function validate_lessons($lessons, $P) {
	$days = $P['days']; $periods = $P['P'];
	$hard = array(); $soft = array();
	$slotName = function($d, $p) use ($days) { return (isset($days[$d - 1]) ? $days[$d - 1] : 'Day ' . $d) . ' P' . $p; };
	$cn = function($id) use ($P) { return isset($P['classes'][$id]) ? $P['classes'][$id]['name'] : "class #$id"; };
	$tn = function($id) use ($P) { if(!$id) { return '(no teacher)'; } return isset($P['teachers'][$id]) ? $P['teachers'][$id]['name'] : "teacher #$id"; };
	$sn = function($id) use ($P) { return isset($P['subjects'][$id]) ? $P['subjects'][$id]['name'] : "subject #$id"; };
	$rn = function($id) use ($P) { return isset($P['rooms'][$id]) ? $P['rooms'][$id]['name'] : "room #$id"; };

	$byClass = array(); $byTeacher = array(); $byRoom = array();
	$tDay = array(); $tWeek = array(); $placedPer = array();
	$classDay = array(); $teachDay = array();

	foreach($lessons as $l) {
		$c = (int)$l['class_id']; $t = (int)$l['teacher_id']; $s = (int)$l['subject_id'];
		$r = $l['room_id'] === null ? null : (int)$l['room_id'];
		$d = (int)$l['day_no']; $p = (int)$l['period_no'];
		$byClass[$c][$d][$p][] = $l;
		if($t) { $byTeacher[$t][$d][$p][] = $l; }
		if($r) { $byRoom[$r][$d][$p][] = $l; }
		if($t) { $tDay[$t][$d] = (isset($tDay[$t][$d]) ? $tDay[$t][$d] : 0) + 1;
		$tWeek[$t] = (isset($tWeek[$t]) ? $tWeek[$t] : 0) + 1;
		}
		$k = $c . '|' . $s . '|' . $t;
		$placedPer[$k] = (isset($placedPer[$k]) ? $placedPer[$k] : 0) + 1;
		$classDay[$c][$d][$p] = $s;
		if($t) { $teachDay[$t][$d][] = $p; }

		if($d < 1 || $d > $P['D'] || $p < 1 || $p > $periods) {
			$hard[] = array('out_of_range', "Lesson outside the school day: " . $cn($c) . ' ' . $sn($s) . ' at ' . $slotName($d, $p), $c, $t, $r, $d, $p);
		}
		// teacher unavailable
		if(isset($P['teachers'][$t])) {
			$slot = ($d - 1) * $periods + ($p - 1);
			if(isset($P['teachers'][$t]['blocked'][$slot])) {
				$hard[] = array('teacher_blocked', $tn($t) . ' is marked unavailable at ' . $slotName($d, $p) . ' but teaches ' . $cn($c) . ' ' . $sn($s), $c, $t, $r, $d, $p);
			}
			if(isset($P['teachers'][$t]['avoid'][$slot])) {
				$soft[] = array('teacher_avoid', $tn($t) . ' prefers to avoid ' . $slotName($d, $p) . ' (teaches ' . $cn($c) . ' ' . $sn($s) . ')', $c, $t, $r, $d, $p);
			}
		}
		// room fit
		if(isset($P['subjects'][$s])) {
			$sub = $P['subjects'][$s];
			if($r) {
				if(!isset($P['rooms'][$r])) {
					$hard[] = array('room_fit', 'Unknown room used for ' . $cn($c) . ' ' . $sn($s), $c, $t, $r, $d, $p);
				} elseif($sub['room_type'] !== '' && $P['rooms'][$r]['type'] !== $sub['room_type']) {
					$hard[] = array('room_fit', $sn($s) . ' needs a ' . $sub['room_type'] . ' but ' . $cn($c) . ' is in ' . $rn($r) . ' (' . $P['rooms'][$r]['type'] . ') at ' . $slotName($d, $p), $c, $t, $r, $d, $p);
				} elseif(isset($P['classes'][$c]) && $P['rooms'][$r]['capacity'] < $P['classes'][$c]['students']) {
					$hard[] = array('room_fit', $rn($r) . ' holds ' . $P['rooms'][$r]['capacity'] . ' but ' . $cn($c) . ' has ' . $P['classes'][$c]['students'] . ' students (' . $slotName($d, $p) . ')', $c, $t, $r, $d, $p);
				}
			}
		}
	}

	// fixed slots must be honoured
	$have = array();
	foreach($lessons as $l) { $have[(int)$l['class_id'] . '|' . (int)$l['subject_id'] . '|' . (int)$l['day_no'] . '|' . (int)$l['period_no']] = true; }
	if(!empty($P['fixed'])) {
		$hasAlloc = array();
		foreach($P['allocs'] as $a) { $hasAlloc[$a['class'] . '|' . $a['subject']] = true; }
		foreach($P['fixed'] as $f) {
			foreach(array_keys($P['classes']) as $c) {
				if($f['class'] != 0 && $f['class'] != $c) { continue; }
				if(!isset($hasAlloc[$c . '|' . $f['subject']])) { continue; }
				if(!isset($have[$c . '|' . $f['subject'] . '|' . ($f['d'] + 1) . '|' . ($f['p'] + 1)])) {
					$hard[] = array('fixed_slot', $cn($c) . ' ' . $sn($f['subject']) . ' should be fixed at ' . $slotName($f['d'] + 1, $f['p'] + 1) . ' but is not there', $c, 0, null, $f['d'] + 1, $f['p'] + 1);
				}
			}
		}
	}

	// clashes
	$report = function($map, $type, $who, $what) use (&$hard, $slotName, $sn, $cn) {
		foreach($map as $id => $dd) { foreach($dd as $d => $pp) { foreach($pp as $p => $list) {
			if(count($list) > 1) {
				$names = array();
				foreach($list as $l) { $names[] = $cn($l['class_id']) . ' ' . $sn($l['subject_id']); }
				$hard[] = array($type, $who($id) . ' ' . $what . ' at ' . $slotName($d, $p) . ': ' . implode(' + ', $names),
					(int)$list[0]['class_id'], (int)$list[0]['teacher_id'], $list[0]['room_id'] === null ? null : (int)$list[0]['room_id'], $d, $p);
			}
		} } }
	};
	$report($byClass, 'class_clash', $cn, 'has two lessons');
	$report($byTeacher, 'teacher_clash', $tn, 'is double-booked');
	$report($byRoom, 'room_clash', $rn, 'is double-booked');

	// teacher load
	foreach($tWeek as $t => $n) {
		if(!isset($P['teachers'][$t])) { continue; }
		$T = $P['teachers'][$t];
		if($n > $T['max_week']) { $hard[] = array('teacher_load', $tn($t) . ' teaches ' . $n . ' periods a week (max ' . $T['max_week'] . ')', 0, $t, null, 0, 0); }
		foreach($tDay[$t] as $d => $dn) {
			if($dn > $T['max_day']) { $hard[] = array('teacher_load', $tn($t) . ' teaches ' . $dn . ' periods on ' . $slotName($d, 1) . ' (max ' . $T['max_day'] . ')', 0, $t, null, $d, 0); }
		}
	}

	// coverage against allocations
	$missing = 0;
	foreach($P['allocs'] as $a) {
		$k = $a['class'] . '|' . $a['subject'] . '|' . $a['teacher'];
		$have = isset($placedPer[$k]) ? $placedPer[$k] : 0;
		if($have < $a['ppw']) {
			$missing += $a['ppw'] - $have;
			$hard[] = array('coverage', $cn($a['class']) . ' ' . $sn($a['subject']) . ' (' . $tn($a['teacher']) . '): ' . $have . ' of ' . $a['ppw'] . ' periods scheduled', $a['class'], $a['teacher'], null, 0, 0);
		} elseif($have > $a['ppw']) {
			$hard[] = array('coverage', $cn($a['class']) . ' ' . $sn($a['subject']) . ' (' . $tn($a['teacher']) . '): ' . $have . ' scheduled but only ' . $a['ppw'] . ' allocated', $a['class'], $a['teacher'], null, 0, 0);
		}
	}

	// soft: teacher gaps, class gaps, repeated subjects
	$gapPeriods = 0; $classGapPeriods = 0; $repeats = 0;
	foreach($teachDay as $t => $dd) {
		foreach($dd as $d => $pp) {
			$pp = array_unique($pp); sort($pp);
			if(count($pp) > 1) {
				$g = (end($pp) - $pp[0] + 1) - count($pp);
				if($g > 0) { $gapPeriods += $g; $soft[] = array('teacher_gap', $tn($t) . ' has ' . $g . ' idle period(s) on ' . $slotName($d, 1), 0, $t, null, $d, 0); }
			}
		}
	}
	foreach($classDay as $c => $dd) {
		foreach($dd as $d => $pp) {
			ksort($pp); $ks = array_keys($pp);
			if(count($ks) > 1) {
				$g = (end($ks) - $ks[0] + 1) - count($ks);
				if($g > 0) { $classGapPeriods += $g; $soft[] = array('class_gap', $cn($c) . ' has ' . $g . ' free period(s) mid-day on ' . $slotName($d, 1), $c, 0, null, $d, 0); }
			}
			$runs = array(); $prev = null;
			foreach($pp as $p => $s) { if($prev !== null && $prev['s'] === $s && $prev['p'] === $p - 1) { $prev['p'] = $p; } else { if($prev !== null) { $runs[] = $prev['s']; } $prev = array('s' => $s, 'p' => $p); } }
			if($prev !== null) { $runs[] = $prev['s']; }
			$cnt = array_count_values($runs);
			foreach($cnt as $s => $n) { if($n > 1) { $repeats++; $soft[] = array('repeat_subject', $cn($c) . ' has ' . $sn($s) . ' ' . $n . ' times on ' . $slotName($d, 1), $c, 0, null, $d, 0); } }
		}
	}

	$counts = array();
	foreach($hard as $h) { $counts[$h[0]] = (isset($counts[$h[0]]) ? $counts[$h[0]] : 0) + 1; }
	return array('hard' => $hard, 'soft' => $soft, 'stats' => array(
		'lessons' => count($lessons), 'hard' => count($hard), 'soft' => count($soft), 'missing_periods' => $missing,
		'teacher_gap_periods' => $gapPeriods, 'class_gap_periods' => $classGapPeriods, 'repeat_subject_days' => $repeats,
		'avoid_hits' => count(array_filter($soft, function($x) { return $x[0] === 'teacher_avoid'; })),
		'hard_by_type' => $counts));
}

?>
