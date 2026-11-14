<?php
/**
 * Constraint solver for the school timetable.
 *
 * Phase 1  CONSTRUCT  - place every lesson block (single or double period)
 *                       without ever breaking a hard rule. When a block has
 *                       no free slot it "ejects" the blocks in its way, which
 *                       are re-queued (tabu list prevents ping-pong).
 * Phase 2  IMPROVE    - simulated annealing: relocate / swap blocks, only ever
 *                       through hard-feasible states, accepting changes that
 *                       lower the soft cost (and some worse ones early on).
 *
 * HARD rules (never broken - an unplaceable lesson is reported instead):
 *   one lesson per class, teacher and special room per slot; teacher
 *   unavailable slots; teacher max per day / week; special room type and
 *   capacity; doubles never straddle a break; FIXED slots (e.g. Saturday
 *   revision) are pinned first and never moved.
 *
 * Subjects that need no teacher (needs_teacher = 0) get a private virtual
 * teacher per class so they never clash with anything but the class itself.
 *
 * SOFT cost (weights from the `rules` table, learned terms from Learner):
 *   learned subject/period, subject/day, double-start, teacher/period,
 *   teacher avoid slots, teacher gaps (+ gaps beyond what the school
 *   tolerates), teacher balance, repeated subject in a day, class gaps,
 *   back-to-back demanding subjects, demanding subjects late in the day.
 */
class TimetableSolver {
	private $D; private $P; private $S;
	private $brk = array();
	private $nC; private $nT; private $nR;
	private $cIds = array(); private $tIds = array(); private $rIds = array();
	private $tMaxDay = array(); private $tMaxWeek = array();
	private $tBlocked = array(); private $tAvoid = array();
	private $tMean = array(); private $tDayOpen = array(); private $tLoad = array(); private $tVirtual = array();
	private $cStudents = array();
	public $blocks = array();

	private $classOcc = array(); private $teachOcc = array(); private $roomOcc = array();
	private $tDayLoad = array(); private $tWeek = array(); private $roomUse = array();
	private $pos = array(); private $room = array();

	private $W = array();
	private $learn = null;
	private $extra = null;
	private $tolGap = 1;
	private $staticMemo = array();
	private $noise = 0.0;

	public $iterations = 0;
	public $restarts = 0;
	public $constructSeconds = 0.0;

	/**
	 * $problem  from load_problem()
	 * $learner  Learner or null (null = generic solver, no school habits)
	 * $extra    optional callable(subjectId, day0, period0, size) => cost, used
	 *           only to synthesise demo history
	 */
	public function __construct($problem, $learner = null, $extra = null) {
		$this->D = $problem['D']; $this->P = $problem['P']; $this->S = $this->D * $this->P;
		$this->brk = $problem['break_after'];
		$this->learn = $learner;
		$this->extra = $extra;
		$this->W = $problem['weights'];
		foreach(array('teacher_avoid', 'teacher_gaps', 'long_gap', 'teacher_balance', 'subject_once_per_day', 'class_gaps',
			'heavy_back_to_back', 'heavy_late', 'learned_subject_period', 'learned_subject_day', 'learned_double_start', 'learned_teacher_period') as $k) {
			if(!isset($this->W[$k])) { $this->W[$k] = 0; }
		}
		if(!$learner) {
			foreach(array('learned_subject_period', 'learned_subject_day', 'learned_double_start', 'learned_teacher_period') as $k) { $this->W[$k] = 0; }
		} else {
			$this->tolGap = $learner->tolerableGap();
		}

		$cIdx = array(); $tIdx = array(); $rIdx = array();
		foreach($problem['classes'] as $id => $c) { $cIdx[$id] = count($this->cIds); $this->cIds[] = $id; $this->cStudents[] = $c['students']; }
		foreach($problem['teachers'] as $id => $t) {
			$i = count($this->tIds); $tIdx[$id] = $i; $this->tIds[] = $id;
			$this->tMaxDay[$i] = $t['max_day']; $this->tMaxWeek[$i] = $t['max_week'];
			foreach($t['blocked'] as $slot => $_) { $this->tBlocked[$i * $this->S + $slot] = true; }
			foreach($t['avoid'] as $slot => $_) { $this->tAvoid[$i * $this->S + $slot] = true; }
			for($d = 0; $d < $this->D; $d++) {
				$open = false;
				for($p = 0; $p < $this->P; $p++) { if(!isset($this->tBlocked[$i * $this->S + $d * $this->P + $p])) { $open = true; break; } }
				$this->tDayOpen[$i * $this->D + $d] = $open;
			}
			$this->tLoad[$i] = 0;
		}
		foreach($problem['rooms'] as $id => $r) { $rIdx[$id] = count($this->rIds); $this->rIds[] = $id; }
		$this->nC = count($this->cIds); $this->nR = count($this->rIds);

		// ---- build the lesson blocks ----
		$pins = isset($problem['fixed']) ? $problem['fixed'] : array();
		$vIdx = array();
		foreach($problem['allocs'] as $a) {
			$sub = $problem['subjects'][$a['subject']];
			$n = $a['ppw'];
			// fixed slots for this class + subject
			$mine = array();
			foreach($pins as $f) {
				if($f['subject'] == $a['subject'] && ($f['class'] == 0 || $f['class'] == $a['class']) && $f['d'] >= 0 && $f['d'] < $this->D && $f['p'] >= 0 && $f['p'] < $this->P) {
					$mine[$f['d'] * $this->P + $f['p']] = true;
				}
			}
			$mine = array_slice(array_keys($mine), 0, $n);
			$rest = $n - count($mine);
			$dbl = min($a['doubles'], intdiv($rest, 2));
			$sizes = array();
			foreach($mine as $slot) { $sizes[] = array(1, $slot); }
			for($i = 0; $i < $dbl; $i++) { $sizes[] = array(2, null); }
			for($i = 0; $i < $rest - 2 * $dbl; $i++) { $sizes[] = array(1, null); }
			if($a['teacher']) {
				$ti = $tIdx[$a['teacher']];
			} else {
				if(!isset($vIdx[$a['class']])) {
					$i = count($this->tIds); $vIdx[$a['class']] = $i;
					$this->tIds[] = 0; $this->tMaxDay[$i] = 99; $this->tMaxWeek[$i] = 9999; $this->tLoad[$i] = 0; $this->tVirtual[$i] = true;
					for($d = 0; $d < $this->D; $d++) { $this->tDayOpen[$i * $this->D + $d] = true; }
				}
				$ti = $vIdx[$a['class']];
			}
			foreach($sizes as $sz) {
				$size = $sz[0]; $pin = $sz[1];
				$rooms = null; $impossible = false;
				if($sub['room_type'] !== '' && ($sub['room_scope'] === 'all' || $size == 2)) {
					$rooms = array();
					foreach($problem['rooms'] as $rid => $r) {
						if($r['type'] === $sub['room_type'] && $r['capacity'] >= $problem['classes'][$a['class']]['students']) { $rooms[] = $rIdx[$rid]; }
					}
					if(!$rooms) { $impossible = true; }
				}
				$this->tLoad[$ti] += $size;
				$this->blocks[] = array('c' => $cIdx[$a['class']], 't' => $ti, 'sub' => $a['subject'], 'size' => $size, 'rooms' => $rooms, 'pin' => $pin,
					'alloc' => $a['id'], 'heavy' => $sub['difficulty'] >= 3, 'impossible' => $impossible, 'sig' => $a['subject'] . '|' . $ti . '|' . $size);
			}
		}
		$this->nT = count($this->tIds);
		for($t = 0; $t < $this->nT; $t++) {
			$open = 0; for($d = 0; $d < $this->D; $d++) { if($this->tDayOpen[$t * $this->D + $d]) { $open++; } }
			$this->tMean[$t] = $this->tLoad[$t] / max(1, $open);
		}
		$this->resetState();
	}

	// ------------------------------------------------------------------
	// state
	// ------------------------------------------------------------------
	private function resetState() {
		$this->classOcc = array_fill(0, max(1, $this->nC * $this->S), -1);
		$this->teachOcc = array_fill(0, max(1, $this->nT * $this->S), -1);
		$this->roomOcc = array_fill(0, max(1, $this->nR * $this->S), -1);
		$this->tDayLoad = array_fill(0, max(1, $this->nT * $this->D), 0);
		$this->tWeek = array_fill(0, max(1, $this->nT), 0);
		$this->roomUse = array_fill(0, max(1, $this->nR), 0);
		$nb = count($this->blocks);
		$this->pos = $nb ? array_fill(0, $nb, -1) : array();
		$this->room = $nb ? array_fill(0, $nb, -1) : array();
	}

	/** pin the fixed-slot blocks first; they are never moved afterwards */
	private function placePins() {
		foreach($this->blocks as $b => $B) {
			if($B['pin'] === null || $B['impossible']) { continue; }
			$r = $this->feasible($b, $B['pin']);
			if($r === null) { continue; }
			$this->place($b, $B['pin'], $r);
		}
	}

	private function snapshot() { return array($this->pos, $this->room); }

	private function restore($snap) {
		$this->resetState();
		foreach($snap[0] as $b => $s) { if($s >= 0) { $this->place($b, $s, $snap[1][$b]); } }
	}

	private function place($b, $s, $r) {
		$B = $this->blocks[$b]; $S = $this->S; $size = $B['size'];
		$cb = $B['c'] * $S; $tb = $B['t'] * $S;
		for($k = 0; $k < $size; $k++) {
			$x = $s + $k;
			$this->classOcc[$cb + $x] = $b; $this->teachOcc[$tb + $x] = $b;
			if($r >= 0) { $this->roomOcc[$r * $S + $x] = $b; }
		}
		$this->tDayLoad[$B['t'] * $this->D + intdiv($s, $this->P)] += $size;
		$this->tWeek[$B['t']] += $size;
		if($r >= 0) { $this->roomUse[$r] += $size; }
		$this->pos[$b] = $s; $this->room[$b] = $r;
	}

	private function unplace($b) {
		$s = $this->pos[$b]; $r = $this->room[$b];
		$B = $this->blocks[$b]; $S = $this->S; $size = $B['size'];
		$cb = $B['c'] * $S; $tb = $B['t'] * $S;
		for($k = 0; $k < $size; $k++) {
			$x = $s + $k;
			$this->classOcc[$cb + $x] = -1; $this->teachOcc[$tb + $x] = -1;
			if($r >= 0) { $this->roomOcc[$r * $S + $x] = -1; }
		}
		$this->tDayLoad[$B['t'] * $this->D + intdiv($s, $this->P)] -= $size;
		$this->tWeek[$B['t']] -= $size;
		if($r >= 0) { $this->roomUse[$r] -= $size; }
		$this->pos[$b] = -1; $this->room[$b] = -1;
	}

	/**
	 * Can block $b start at slot $s without breaking any hard rule?
	 * Returns null (no), -1 (yes, no special room needed) or a room index.
	 */
	private function feasible($b, $s) {
		$B = $this->blocks[$b];
		if($B['impossible']) { return null; }
		$P = $this->P; $S = $this->S; $size = $B['size'];
		$d = intdiv($s, $P); $p = $s - $d * $P;
		if($p + $size > $P) { return null; }
		if($size == 2 && $this->brk[$p]) { return null; }
		$t = $B['t'];
		if($this->tDayLoad[$t * $this->D + $d] + $size > $this->tMaxDay[$t]) { return null; }
		if($this->tWeek[$t] + $size > $this->tMaxWeek[$t]) { return null; }
		$cb = $B['c'] * $S; $tb = $t * $S;
		for($k = 0; $k < $size; $k++) {
			$x = $s + $k;
			if($this->classOcc[$cb + $x] !== -1 || $this->teachOcc[$tb + $x] !== -1 || isset($this->tBlocked[$tb + $x])) { return null; }
		}
		if($B['rooms'] === null) { return -1; }
		$best = null; $bu = PHP_INT_MAX;
		foreach($B['rooms'] as $r) {
			$rb = $r * $S; $free = true;
			for($k = 0; $k < $size; $k++) { if($this->roomOcc[$rb + $s + $k] !== -1) { $free = false; break; } }
			if($free && $this->roomUse[$r] < $bu) { $bu = $this->roomUse[$r]; $best = $r; }
		}
		return $best;
	}

	// ------------------------------------------------------------------
	// cost
	// ------------------------------------------------------------------
	private function staticCost($b, $s) {
		$B = $this->blocks[$b];
		$key = $B['sig'] . '|' . $s;
		if(isset($this->staticMemo[$key])) { return $this->staticMemo[$key]; }
		$P = $this->P; $d = intdiv($s, $P); $p = $s - $d * $P; $size = $B['size'];
		$t = $B['t']; $tid = $this->tIds[$t]; $sub = $B['sub']; $W = $this->W; $L = $this->learn;
		$cost = 0.0;
		for($k = 0; $k < $size; $k++) {
			$pp = $p + $k; $ss = $s + $k;
			if($W['teacher_avoid'] && isset($this->tAvoid[$t * $this->S + $ss])) { $cost += $W['teacher_avoid']; }
			if($L) {
				$cost += $W['learned_subject_period'] * $L->costSubjectPeriod($sub, $pp);
				if(!isset($this->tVirtual[$t])) { $cost += $W['learned_teacher_period'] * $L->costTeacherPeriod($tid, $pp); }
			}
			if($B['heavy'] && $pp >= $P - 2) { $cost += $W['heavy_late']; }
		}
		if($L) {
			$cost += $W['learned_subject_day'] * $L->costSubjectDay($sub, $d);
			if($size == 2) { $cost += $W['learned_double_start'] * $L->costDoubleStart($sub, $p); }
		}
		if($this->extra) { $cost += call_user_func($this->extra, $sub, $d, $p, $size); }
		$this->staticMemo[$key] = $cost;
		return $cost;
	}

	/** teacher-day: idle gaps, over-long gaps, load balance */
	private function tdCost($t, $d) {
		if(isset($this->tVirtual[$t])) { return 0.0; }
		$P = $this->P; $base = $t * $this->S + $d * $P;
		$n = 0; $last = -1; $gaps = 0; $long = 0;
		for($p = 0; $p < $P; $p++) {
			if($this->teachOcc[$base + $p] !== -1) {
				$n++;
				if($last >= 0) {
					$g = $p - $last - 1;
					if($g > 0) { $gaps += $g; if($g > $this->tolGap) { $long += $g - $this->tolGap; } }
				}
				$last = $p;
			}
		}
		$cost = $this->W['teacher_gaps'] * $gaps + $this->W['long_gap'] * $long;
		if($this->W['teacher_balance'] && $this->tDayOpen[$t * $this->D + $d]) {
			$dev = $n - $this->tMean[$t];
			$cost += $this->W['teacher_balance'] * $dev * $dev;
		}
		return $cost;
	}

	/** class-day: repeated subject, free periods in the middle / at the start, back-to-back heavy */
	private function cdCost($c, $d) {
		$P = $this->P; $base = $c * $this->S + $d * $P;
		$cnt = array(); $n = 0; $first = -1; $last = -1; $prevB = -1; $hbb = 0;
		for($p = 0; $p < $P; $p++) {
			$b = $this->classOcc[$base + $p];
			if($b === -1) { $prevB = -1; continue; }
			$n++; if($first < 0) { $first = $p; } $last = $p;
			if($b !== $prevB) {
				$sub = $this->blocks[$b]['sub'];
				$cnt[$sub] = (isset($cnt[$sub]) ? $cnt[$sub] : 0) + 1;
				if($prevB !== -1 && $this->blocks[$b]['heavy'] && $this->blocks[$prevB]['heavy'] && !$this->brk[$p - 1]) { $hbb++; }
			}
			$prevB = $b;
		}
		$once = 0;
		foreach($cnt as $v) { if($v > 1) { $once += $v - 1; } }
		$gaps = 0;
		if($n > 0) { $gaps = ($last - $first + 1 - $n) + $first; }
		return $this->W['subject_once_per_day'] * $once + $this->W['class_gaps'] * $gaps + $this->W['heavy_back_to_back'] * $hbb;
	}

	public function fullCost() {
		$cost = 0.0;
		foreach($this->blocks as $b => $B) { if($this->pos[$b] >= 0) { $cost += $this->staticCost($b, $this->pos[$b]); } }
		for($t = 0; $t < $this->nT; $t++) { for($d = 0; $d < $this->D; $d++) { $cost += $this->tdCost($t, $d); } }
		for($c = 0; $c < $this->nC; $c++) { for($d = 0; $d < $this->D; $d++) { $cost += $this->cdCost($c, $d); } }
		return $cost;
	}

	/** cost change of adding block b at s (room r), measured on the cells it touches */
	private function incrementalCost($b, $s, $r) {
		$B = $this->blocks[$b]; $d = intdiv($s, $this->P);
		$before = $this->tdCost($B['t'], $d) + $this->cdCost($B['c'], $d);
		$this->place($b, $s, $r);
		$after = $this->tdCost($B['t'], $d) + $this->cdCost($B['c'], $d);
		$this->unplace($b);
		return $this->staticCost($b, $s) + $after - $before;
	}

	private function rnd() { return mt_rand() / mt_getrandmax(); }

	// ------------------------------------------------------------------
	// phase 1: construct
	// ------------------------------------------------------------------
	private function blockOrder($jitter) {
		$score = array();
		foreach($this->blocks as $b => $B) {
			$t = $B['t'];
			$free = $this->S; foreach($this->tBlocked as $k => $_) { if(intdiv($k, $this->S) === $t) { $free--; } }
			$tight = $this->tLoad[$t] / max(1, $free);
			$score[$b] = $B['size'] * 20 + ($B['rooms'] !== null ? 15 : 0) + $tight * 60 + ($B['impossible'] ? -1000 : 0) + $this->rnd() * $jitter;
		}
		arsort($score);
		return array_keys($score);
	}

	/** returns the list of blocks that could not be placed */
	private function construct($order) {
		$queue = $order; $head = 0;
		$iter = 0; $maxIter = 60 * count($order) + 3000;
		$tabuUntil = array();
		$giveUp = array();
		while($head < count($queue) && $iter < $maxIter) {
			$iter++;
			$b = $queue[$head++];
			if($this->pos[$b] >= 0 || isset($giveUp[$b]) || $this->blocks[$b]['pin'] !== null) { continue; }
			$B = $this->blocks[$b];
			if($B['impossible']) { $giveUp[$b] = true; continue; }

			// 1) a hard-feasible slot exists: take the cheapest one
			$best = -1; $bestCost = INF; $bestRoom = -1;
			for($s = 0; $s < $this->S; $s++) {
				$r = $this->feasible($b, $s);
				if($r === null) { continue; }
				$c = $this->incrementalCost($b, $s, $r) + $this->rnd() * $this->noise;
				if($c < $bestCost) { $bestCost = $c; $best = $s; $bestRoom = $r; }
			}
			if($best >= 0) { $this->place($b, $best, $bestRoom); continue; }

			// 2) none: choose the slot that disturbs the fewest placed blocks
			$cand = $this->bestEjection($b, $iter, $tabuUntil);
			if($cand === null) { $giveUp[$b] = true; continue; }
			foreach($cand['eject'] as $ob) {
				$this->unplace($ob);
				$queue[] = $ob;
			}
			$this->place($b, $cand['s'], $cand['room']);
			$tabuUntil[$b] = $iter + 6 + mt_rand(0, 6);
		}
		$left = array();
		foreach($this->blocks as $b => $B) { if($this->pos[$b] < 0) { $left[] = $b; } }
		return $left;
	}

	private function bestEjection($b, $iter, $tabuUntil) {
		$B = $this->blocks[$b]; $P = $this->P; $S = $this->S; $size = $B['size']; $t = $B['t']; $c = $B['c'];
		$cb = $c * $S; $tb = $t * $S;
		$bestScore = INF; $best = null;
		for($s = 0; $s < $S; $s++) {
			$d = intdiv($s, $P); $p = $s - $d * $P;
			if($p + $size > $P) { continue; }
			if($size == 2 && $this->brk[$p]) { continue; }
			$blocked = false;
			for($k = 0; $k < $size; $k++) { if(isset($this->tBlocked[$tb + $s + $k])) { $blocked = true; break; } }
			if($blocked) { continue; }

			$conf = array();
			for($k = 0; $k < $size; $k++) {
				$o = $this->classOcc[$cb + $s + $k]; if($o !== -1) { $conf[$o] = true; }
				$o = $this->teachOcc[$tb + $s + $k]; if($o !== -1) { $conf[$o] = true; }
			}
			// teacher daily load
			$load = $this->tDayLoad[$t * $this->D + $d];
			foreach($conf as $o => $_) { if($this->blocks[$o]['t'] === $t && intdiv($this->pos[$o], $P) === $d) { $load -= $this->blocks[$o]['size']; } }
			$guard = 0;
			while($load + $size > $this->tMaxDay[$t] && $guard++ < 8) {
				$picked = -1;
				for($pp = 0; $pp < $P; $pp++) {
					$o = $this->teachOcc[$tb + $d * $P + $pp];
					if($o !== -1 && !isset($conf[$o]) && $this->blocks[$o]['pin'] === null) { $picked = $o; break; }
				}
				if($picked < 0) { break; }
				$conf[$picked] = true; $load -= $this->blocks[$picked]['size'];
			}
			if($load + $size > $this->tMaxDay[$t]) { continue; }
			// teacher weekly load
			$week = $this->tWeek[$t];
			foreach($conf as $o => $_) { if($this->blocks[$o]['t'] === $t) { $week -= $this->blocks[$o]['size']; } }
			if($week + $size > $this->tMaxWeek[$t]) { continue; }
			// special room
			$room = -1;
			if($B['rooms'] !== null) {
				$bestR = null; $bestRc = INF; $bestRo = array();
				foreach($B['rooms'] as $r) {
					$occ = array();
					for($k = 0; $k < $size; $k++) { $o = $this->roomOcc[$r * $S + $s + $k]; if($o !== -1 && !isset($conf[$o])) { $occ[$o] = true; } }
					if(count($occ) < $bestRc) { $bestRc = count($occ); $bestR = $r; $bestRo = $occ; }
				}
				if($bestR === null) { continue; }
				$room = $bestR;
				foreach($bestRo as $o => $_) { $conf[$o] = true; }
			}
			$pinned = false;
			foreach($conf as $o => $_) { if($this->blocks[$o]['pin'] !== null) { $pinned = true; break; } }
			if($pinned) { continue; }
			$tabu = false;
			foreach($conf as $o => $_) { if(isset($tabuUntil[$o]) && $tabuUntil[$o] > $iter) { $tabu = true; break; } }
			if($tabu) { continue; }
			$score = count($conf) * 100 + $this->staticCost($b, $s) + $this->rnd() * 2;
			if($score < $bestScore) { $bestScore = $score; $best = array('s' => $s, 'room' => $room, 'eject' => array_keys($conf)); }
		}
		return $best;
	}

	// ------------------------------------------------------------------
	// phase 2: simulated annealing
	// ------------------------------------------------------------------
	private function anneal($deadline, $T0, $T1) {
		$byClass = array(); $placed = array();
		foreach($this->blocks as $b => $B) {
			if($this->pos[$b] >= 0 && $B['pin'] === null) { $placed[] = $b; $byClass[$B['c']][] = $b; }
		}
		if(count($placed) < 2) { return; }
		$total = $this->fullCost();
		$bestCost = $total; $bestSnap = $this->snapshot();
		$tStart = microtime(true); $span = max(0.05, $deadline - $tStart);
		$T = $T0; $nP = count($placed); $P = $this->P;
		$iter = 0;
		while(true) {
			if(($iter & 255) === 0) {
				$now = microtime(true);
				if($now >= $deadline) { break; }
				$T = $T0 * pow($T1 / $T0, ($now - $tStart) / $span);
			}
			$iter++;
			$b = $placed[mt_rand(0, $nP - 1)];
			$B = $this->blocks[$b];

			if(mt_rand(0, 99) < 62) {
				// ---- relocate ----
				$old = $this->pos[$b]; $oldRoom = $this->room[$b];
				$s = mt_rand(0, $this->S - 1);
				if($s === $old) { continue; }
				$d0 = intdiv($old, $P); $d1 = intdiv($s, $P);
				$keysT = array($B['t'] . '|' . $d0 => array($B['t'], $d0), $B['t'] . '|' . $d1 => array($B['t'], $d1));
				$keysC = array($B['c'] . '|' . $d0 => array($B['c'], $d0), $B['c'] . '|' . $d1 => array($B['c'], $d1));
				$before = $this->staticCost($b, $old) + $this->groupCost($keysT, $keysC);
				$this->unplace($b);
				$r = $this->feasible($b, $s);
				if($r === null) { $this->place($b, $old, $oldRoom); continue; }
				$this->place($b, $s, $r);
				$delta = $this->staticCost($b, $s) + $this->groupCost($keysT, $keysC) - $before;
				if($delta <= 0 || $this->rnd() < exp(-$delta / $T)) { $total += $delta; }
				else { $this->unplace($b); $this->place($b, $old, $oldRoom); }
			} else {
				// ---- swap with another block of the same class ----
				$list = $byClass[$B['c']];
				if(count($list) < 2) { continue; }
				$b2 = $list[mt_rand(0, count($list) - 1)];
				if($b2 === $b) { continue; }
				$s1 = $this->pos[$b]; $s2 = $this->pos[$b2];
				if($s1 === $s2) { continue; }
				$B2 = $this->blocks[$b2];
				$r1o = $this->room[$b]; $r2o = $this->room[$b2];
				$d1 = intdiv($s1, $P); $d2 = intdiv($s2, $P);
				$keysT = array(); $keysC = array();
				foreach(array($B['t'], $B2['t']) as $tt) { foreach(array($d1, $d2) as $dd) { $keysT[$tt . '|' . $dd] = array($tt, $dd); } }
				foreach(array($d1, $d2) as $dd) { $keysC[$B['c'] . '|' . $dd] = array($B['c'], $dd); }
				$before = $this->staticCost($b, $s1) + $this->staticCost($b2, $s2) + $this->groupCost($keysT, $keysC);
				$this->unplace($b); $this->unplace($b2);
				$ra = $this->feasible($b, $s2);
				if($ra === null) { $this->place($b, $s1, $r1o); $this->place($b2, $s2, $r2o); continue; }
				$this->place($b, $s2, $ra);
				$rb = $this->feasible($b2, $s1);
				if($rb === null) { $this->unplace($b); $this->place($b, $s1, $r1o); $this->place($b2, $s2, $r2o); continue; }
				$this->place($b2, $s1, $rb);
				$delta = $this->staticCost($b, $s2) + $this->staticCost($b2, $s1) + $this->groupCost($keysT, $keysC) - $before;
				if($delta <= 0 || $this->rnd() < exp(-$delta / $T)) { $total += $delta; }
				else {
					$this->unplace($b); $this->unplace($b2);
					$this->place($b, $s1, $r1o); $this->place($b2, $s2, $r2o);
				}
			}
			if($total < $bestCost - 1e-9) { $bestCost = $total; $bestSnap = $this->snapshot(); }
		}
		$this->iterations += $iter;
		$this->restore($bestSnap);
	}

	private function groupCost($keysT, $keysC) {
		$c = 0.0;
		foreach($keysT as $k) { $c += $this->tdCost($k[0], $k[1]); }
		foreach($keysC as $k) { $c += $this->cdCost($k[0], $k[1]); }
		return $c;
	}

	// ------------------------------------------------------------------
	// public API
	// ------------------------------------------------------------------
	/**
	 * Solve within $timeLimit seconds. Returns
	 *   lessons   list of [class,subject,teacher,room|null,day(1-based),period(1-based)]
	 *   unplaced  list of [class,subject,teacher,size,reason]
	 *   cost, seconds, iterations
	 */
	public function solve($timeLimit = 8.0, $seed = null) {
		$t0 = microtime(true);
		if($seed === null) { $seed = mt_rand(1, 2000000000); }
		mt_srand((int)$seed);
		$deadline = $t0 + $timeLimit;
		$this->noise = 0.0;
		$best = null; $bestLeft = PHP_INT_MAX;

		$maxAttempts = 6;
		for($attempt = 0; $attempt < $maxAttempts; $attempt++) {
			$this->resetState();
			$this->placePins();
			$this->noise = $attempt == 0 ? 0.0 : 1.5;
			$left = $this->construct($this->blockOrder($attempt == 0 ? 0 : 25));
			$this->restarts = $attempt;
			// blocks with no possible room are expected to stay unplaced
			$realLeft = 0; foreach($left as $b) { if(!$this->blocks[$b]['impossible'] && $this->blocks[$b]['pin'] === null) { $realLeft++; } }
			if($realLeft < $bestLeft) { $bestLeft = $realLeft; $best = $this->snapshot(); }
			if($realLeft == 0 || microtime(true) > $t0 + $timeLimit * 0.35) { break; }
		}
		$this->constructSeconds = microtime(true) - $t0;
		$this->restore($best);

		// improve
		$this->noise = 0.0;
		if(microtime(true) < $deadline) {
			$this->anneal($deadline, 3.0, 0.08);
		}
		// a last attempt to place anything left over
		$left = array();
		foreach($this->blocks as $b => $B) { if($this->pos[$b] < 0 && !$B['impossible'] && $B['pin'] === null) { $left[] = $b; } }
		if($left) { $this->noise = 0.0; $this->construct($left); }

		return $this->result(microtime(true) - $t0);
	}

	private function result($seconds) {
		$lessons = array(); $unplaced = array();
		foreach($this->blocks as $b => $B) {
			if($this->pos[$b] < 0) {
				$unplaced[] = array('class' => $this->cIds[$B['c']], 'subject' => $B['sub'], 'teacher' => $this->tIds[$B['t']], 'size' => $B['size'],
					'reason' => $B['impossible'] ? 'no suitable room exists' : ($B['pin'] !== null ? 'fixed slot is already taken or the teacher is unavailable' : 'no clash-free slot found'));
				continue;
			}
			$s = $this->pos[$b]; $d = intdiv($s, $this->P); $p = $s - $d * $this->P;
			for($k = 0; $k < $B['size']; $k++) {
				$lessons[] = array('class' => $this->cIds[$B['c']], 'subject' => $B['sub'], 'teacher' => $this->tIds[$B['t']],
					'room' => $this->room[$b] >= 0 ? $this->rIds[$this->room[$b]] : null, 'day' => $d + 1, 'period' => $p + $k + 1);
			}
		}
		return array('lessons' => $lessons, 'unplaced' => $unplaced, 'cost' => $this->fullCost(), 'seconds' => $seconds,
			'iterations' => $this->iterations, 'restarts' => $this->restarts, 'blocks' => count($this->blocks));
	}
}

?>
