<?php
/**
 * Learns "what this school actually tolerates" from timetables it already
 * accepted (status = accepted or historical).
 *
 * It is deliberately simple and explainable - frequency tables with Laplace
 * smoothing - so a deputy head can see WHY the solver prefers a slot:
 *
 *   subjPeriod[s][p]  how often subject s was taught in period p
 *   subjDay[s][d]     ... on day d
 *   dblStart[s][p]    where double lessons of s start
 *   tchPeriod[t][p]   a teacher's habitual periods
 *   tolerance         observed idle gaps, repeated subjects, daily load ...
 *
 * A cost for a slot is ln(uniform / probability): negative when the school
 * uses that slot more than chance, positive when it almost never does.
 * With little or no history every probability is ~uniform, so the cost is
 * ~0 and the solver falls back to generic rules.
 */
class Learner {
	public $D = 5;
	public $P = 8;
	public $nTimetables = 0;
	public $nLessons = 0;
	public $timetableNames = array();

	public $subjLessons = array();   // subject => lesson count
	public $subjPeriod = array();    // subject => [p => count]
	public $subjDay = array();
	public $dblStart = array();      // subject => [p => count of double starts]
	public $dblCount = array();      // subject => number of doubles
	public $tchLessons = array();
	public $tchPeriod = array();

	public $tolerance = array(
		'teacher_days' => 0, 'mean_gaps' => 0.0, 'share_days_with_gap' => 0.0, 'max_gap_p90' => 1,
		'class_days' => 0, 'share_repeat_subject' => 0.0, 'max_teacher_day' => 0, 'mean_teacher_day' => 0.0,
		'share_class_gap' => 0.0,
	);

	private $alpha = 1.0;
	private $costCache = array();

	/** Build a model from the saved timetables. */
	public static function fromDb($connect, $D, $P, $onlyIds = null) {
		$m = new Learner();
		$m->D = $D; $m->P = $P;
		$sql = "SELECT timetable_id, name FROM timetables WHERE status IN ('accepted','historical')";
		$tts = db_all($connect, $sql);
		$ids = array();
		foreach($tts as $t) {
			if($onlyIds !== null && !in_array((int)$t['timetable_id'], $onlyIds)) { continue; }
			$ids[] = (int)$t['timetable_id'];
			$m->timetableNames[(int)$t['timetable_id']] = $t['name'];
		}
		$m->nTimetables = count($ids);
		if(!$ids) { return $m; }
		$rows = db_all($connect, "SELECT timetable_id, class_id, subject_id, teacher_id, day_no, period_no FROM timetable_lessons WHERE timetable_id IN (" . implode(',', $ids) . ")");
		$m->ingest($rows);
		return $m;
	}

	/** rows: timetable_id, class_id, subject_id, teacher_id, day_no, period_no (1-based) */
	public function ingest($rows) {
		$classDay = array();   // "tt|class|day" => [period => subject]
		$teachDay = array();   // "tt|teacher|day" => [periods]
		foreach($rows as $r) {
			$d = (int)$r['day_no'] - 1; $p = (int)$r['period_no'] - 1;
			if($d < 0 || $d >= $this->D || $p < 0 || $p >= $this->P) { continue; }
			$s = (int)$r['subject_id']; $t = (int)$r['teacher_id'];
			$this->nLessons++;
			$this->bump($this->subjLessons, $s);
			$this->bump2($this->subjPeriod, $s, $p);
			$this->bump2($this->subjDay, $s, $d);
			if($t !== 0) { $this->bump($this->tchLessons, $t); $this->bump2($this->tchPeriod, $t, $p); }
			$classDay[$r['timetable_id'] . '|' . $r['class_id'] . '|' . $d][$p] = $s;
			if($t !== 0) { $teachDay[$r['timetable_id'] . '|' . $t . '|' . $d][] = $p; }
		}

		// doubles: two consecutive periods of the same subject for a class
		$classDays = 0; $repeat = 0; $classGapDays = 0;
		foreach($classDay as $periods) {
			ksort($periods);
			$classDays++;
			$runs = array(); $cur = null;
			foreach($periods as $p => $s) {
				if($cur !== null && $cur['s'] === $s && $cur['end'] === $p - 1) { $cur['end'] = $p; $cur['len']++; }
				else {
					if($cur !== null) { $runs[] = $cur; }
					$cur = array('s' => $s, 'start' => $p, 'end' => $p, 'len' => 1);
				}
			}
			if($cur !== null) { $runs[] = $cur; }
			$subjectsToday = array();
			foreach($runs as $run) {
				$this->closeRun($run['s'], $run['start'], $run['len']);
				$subjectsToday[$run['s']] = true;
			}
			if(count($subjectsToday) < count($runs)) { $repeat++; }
			$ks = array_keys($periods);
			if(count($ks) > 1 && (end($ks) - $ks[0] + 1) > count($ks)) { $classGapDays++; }
		}

		// teacher idle gaps
		$tDays = 0; $gapSum = 0; $withGap = 0; $maxRuns = array(); $dayLoads = 0; $maxLoad = 0;
		foreach($teachDay as $periods) {
			sort($periods);
			$n = count($periods);
			$tDays++; $dayLoads += $n; $maxLoad = max($maxLoad, $n);
			if($n < 2) { continue; }
			$gaps = (end($periods) - $periods[0] + 1) - $n;
			$gapSum += $gaps;
			if($gaps > 0) {
				$withGap++;
				$longest = 0;
				for($i = 1; $i < $n; $i++) { $longest = max($longest, $periods[$i] - $periods[$i - 1] - 1); }
				$maxRuns[] = $longest;
			}
		}
		sort($maxRuns);
		$p90 = 1;
		if($maxRuns) { $p90 = max(1, $maxRuns[(int)floor(0.9 * (count($maxRuns) - 1))]); }
		$this->tolerance = array(
			'teacher_days' => $tDays,
			'mean_gaps' => $tDays ? $gapSum / $tDays : 0.0,
			'share_days_with_gap' => $tDays ? $withGap / $tDays : 0.0,
			'max_gap_p90' => $p90,
			'class_days' => $classDays,
			'share_repeat_subject' => $classDays ? $repeat / $classDays : 0.0,
			'max_teacher_day' => $maxLoad,
			'mean_teacher_day' => $tDays ? $dayLoads / $tDays : 0.0,
			'share_class_gap' => $classDays ? $classGapDays / $classDays : 0.0,
		);
	}

	private function closeRun($s, $start, $len) {
		if($len >= 2) {
			for($k = 0; $k + 1 < $len; $k += 2) {
				$this->bump2($this->dblStart, $s, $start + $k);
				$this->bump($this->dblCount, $s);
			}
		}
	}

	private function bump(&$arr, $k) { $arr[$k] = (isset($arr[$k]) ? $arr[$k] : 0) + 1; }
	private function bump2(&$arr, $k, $i) {
		if(!isset($arr[$k])) { $arr[$k] = array(); }
		$arr[$k][$i] = (isset($arr[$k][$i]) ? $arr[$k][$i] : 0) + 1;
	}

	public function hasHistory() { return $this->nTimetables > 0 && $this->nLessons > 0; }

	/** smoothed probability of bucket $i out of $n buckets given counts */
	private function prob($counts, $i, $n) {
		$total = $counts ? array_sum($counts) : 0;
		$c = isset($counts[$i]) ? $counts[$i] : 0;
		return ($c + $this->alpha) / ($total + $this->alpha * $n);
	}

	public function probSubjectPeriod($s, $p) { return $this->prob(isset($this->subjPeriod[$s]) ? $this->subjPeriod[$s] : array(), $p, $this->P); }
	public function probSubjectDay($s, $d) { return $this->prob(isset($this->subjDay[$s]) ? $this->subjDay[$s] : array(), $d, $this->D); }
	public function probDoubleStart($s, $p) { return $this->prob(isset($this->dblStart[$s]) ? $this->dblStart[$s] : array(), $p, $this->P); }
	public function probTeacherPeriod($t, $p) {
		if(!isset($this->tchLessons[$t]) || $this->tchLessons[$t] < 20) { return 1.0 / $this->P; }
		return $this->prob($this->tchPeriod[$t], $p, $this->P);
	}

	private function lnRatio($prob, $n) {
		return max(-1.2, min(2.0, log((1.0 / $n) / $prob)));
	}

	public function costSubjectPeriod($s, $p) {
		$k = "sp$s|$p";
		if(!isset($this->costCache[$k])) { $this->costCache[$k] = $this->hasHistory() ? $this->lnRatio($this->probSubjectPeriod($s, $p), $this->P) : 0.0; }
		return $this->costCache[$k];
	}
	public function costSubjectDay($s, $d) {
		$k = "sd$s|$d";
		if(!isset($this->costCache[$k])) { $this->costCache[$k] = $this->hasHistory() ? $this->lnRatio($this->probSubjectDay($s, $d), $this->D) : 0.0; }
		return $this->costCache[$k];
	}
	public function costDoubleStart($s, $p) {
		$k = "ds$s|$p";
		if(!isset($this->costCache[$k])) {
			$n = isset($this->dblCount[$s]) ? $this->dblCount[$s] : 0;
			$this->costCache[$k] = ($this->hasHistory() && $n >= 3) ? $this->lnRatio($this->probDoubleStart($s, $p), $this->P) : 0.0;
		}
		return $this->costCache[$k];
	}
	public function costTeacherPeriod($t, $p) {
		$k = "tp$t|$p";
		if(!isset($this->costCache[$k])) { $this->costCache[$k] = $this->hasHistory() ? $this->lnRatio($this->probTeacherPeriod($t, $p), $this->P) : 0.0; }
		return $this->costCache[$k];
	}

	/** Longest teacher gap (in periods) the school has tolerated. */
	public function tolerableGap() {
		return $this->hasHistory() ? max(1, (int)$this->tolerance['max_gap_p90']) : 1;
	}

	/**
	 * "Habit match": share of lessons sitting in a period this school has used
	 * for that subject at least as often as chance would predict.
	 * lessons: list of [subject, period0]
	 */
	public function habitMatch($lessons) {
		if(!$this->hasHistory() || !$lessons) { return 0.0; }
		$ok = 0;
		foreach($lessons as $l) {
			if($this->probSubjectPeriod($l[0], $l[1]) >= 1.0 / $this->P) { $ok++; }
		}
		return $ok / count($lessons);
	}
}

?>
