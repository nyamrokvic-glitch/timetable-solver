<?php require_once __DIR__ . '/../core.php'; ?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>School Timetable Solver</title>

	<!-- tailwind css (offline/local file) -->
	<link rel="stylesheet" href="assests/tailwind/tailwind.min.css">
	<!-- font awesome -->
	<link rel="stylesheet" href="assests/font-awesome/css/font-awesome.min.css">

	<!-- DataTables -->
	<link rel="stylesheet" href="assests/plugins/datatables/jquery.dataTables.min.css">

	<!-- jquery -->
	<script src="assests/jquery/jquery.min.js"></script>
	<!-- jquery ui -->
	<link rel="stylesheet" href="assests/jquery-ui/jquery-ui.min.css">
	<script src="assests/jquery-ui/jquery-ui.min.js"></script>
	<!-- DataTables js -->
	<script src="assests/plugins/datatables/jquery.dataTables.min.js"></script>

	<!-- bootstrap replacement js (dropdown/collapse/modal support) -->
	<script src="custom/js/bootstrap-replacement.js"></script>

	<!-- custom css -->
	<link rel="stylesheet" href="custom/css/custom.css">
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<?php $isAdmin = is_admin(); $schoolName = get_setting($connect, 'school_name', 'My School'); ?>

	<nav class="border-b border-slate-200 bg-white">
		<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
			<div class="flex h-16 items-center justify-between gap-4">
				<div class="flex items-center">
					<a href="dashboard.php" class="block shrink-0 leading-tight">
						<span class="block text-lg font-bold text-indigo-600"><i class="fa fa-calendar"></i> Timetable Solver</span>
						<span class="block text-xs text-slate-500"><?php echo h($schoolName); ?></span>
					</a>
				</div>

				<button type="button" class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-slate-700 md:hidden" data-toggle="collapse" data-target="#main-nav" aria-expanded="false">
					<span class="sr-only">Toggle navigation</span>
					<span class="space-y-1">
						<span class="block h-0.5 w-5 bg-slate-700"></span>
						<span class="block h-0.5 w-5 bg-slate-700"></span>
						<span class="block h-0.5 w-5 bg-slate-700"></span>
					</span>
				</button>

				<div class="collapse md:block md:flex-1 md:pb-0" id="main-nav">
					<div class="mb-3 flex items-center justify-between md:hidden">
						<span class="text-sm font-semibold text-slate-700">Menu</span>
						<button type="button" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700" data-toggle="collapse" data-target="#main-nav">Close</button>
					</div>
					<ul class="mt-2 flex flex-col gap-1 md:mt-0 md:flex-row md:items-center md:justify-end md:gap-1">
					<li id="navDashboard"><a href="dashboard.php" class="block rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-indigo-600"><i class="glyphicon glyphicon-list-alt"></i> Dashboard</a></li>
					<?php if($isAdmin) { ?>
					<li class="dropdown relative" id="navSetup">
						<a href="#" class="dropdown-toggle block rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-indigo-600" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="glyphicon glyphicon-wrench"></i> School Setup <span class="caret"></span></a>
						<ul class="dropdown-menu left-0 mt-1 rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
							<li id="subNavTeachers"><a href="teachers.php" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"><i class="glyphicon glyphicon-user"></i> Teachers</a></li>
							<li id="subNavAvailability"><a href="availability.php" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"><i class="glyphicon glyphicon-calendar"></i> Teacher Availability</a></li>
							<li id="subNavSubjects"><a href="subjects.php" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"><i class="glyphicon glyphicon-th-list"></i> Subjects</a></li>
							<li id="subNavClasses"><a href="classes.php" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"><i class="glyphicon glyphicon-th-list"></i> Classes</a></li>
							<li id="subNavRooms"><a href="rooms.php" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"><i class="glyphicon glyphicon-th-list"></i> Rooms &amp; Labs</a></li>
							<li id="subNavAllocations"><a href="allocations.php" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"><i class="glyphicon glyphicon-edit"></i> Allocations</a></li>
							<li id="subNavFixed"><a href="fixed.php" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"><i class="glyphicon glyphicon-pushpin"></i> Fixed Slots</a></li>
							<li id="subNavRules"><a href="constraints.php" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"><i class="glyphicon glyphicon-check"></i> Rules &amp; Weights</a></li>
						</ul>
					</li>
					<li id="navGenerate"><a href="generate.php" class="block rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-indigo-600"><i class="glyphicon glyphicon-circle-arrow-right"></i> Generate</a></li>
					<?php } ?>
					<li id="navTimetable"><a href="timetable.php" class="block rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-indigo-600"><i class="glyphicon glyphicon-calendar"></i> Timetables</a></li>
					<?php if($isAdmin) { ?>
					<li id="navHistory"><a href="history.php" class="block rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-indigo-600"><i class="glyphicon glyphicon-save"></i> History</a></li>
					<li id="navAI"><a href="ai_learning.php" class="block rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-indigo-600"><i class="glyphicon glyphicon-stats"></i> AI Learning</a></li>
					<?php } ?>
					<li id="navReport"><a href="report.php" class="block rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-indigo-600"><i class="glyphicon glyphicon-check"></i> Report</a></li>
					<li class="dropdown relative" id="navSetting">
						<a href="#" class="dropdown-toggle block rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-indigo-600" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="glyphicon glyphicon-user"></i> <span class="caret"></span></a>
						<ul class="dropdown-menu right-0 mt-1 rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
							<?php if($isAdmin) { ?>
							<li id="topNavSetting"><a href="setting.php" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"><i class="glyphicon glyphicon-wrench"></i> Setting</a></li>
							<li id="topNavUser"><a href="user.php" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"><i class="glyphicon glyphicon-wrench"></i> Add User</a></li>
							<?php } ?>
							<li id="topNavLogout"><a href="logout.php" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"><i class="glyphicon glyphicon-log-out"></i> Logout</a></li>
						</ul>
					</li>
					</ul>
				</div>
			</div>
		</div>
		<div id="nav-overlay" class="hidden"></div>
	</nav>

	<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8"> <!-- container -->
