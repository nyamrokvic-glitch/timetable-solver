<?php
require_once 'php_action/db_connect.php';

session_start();

if(isset($_SESSION['userId'])) {
	header('location:'.$store_url.'dashboard.php');
	exit;
}

$errors = array();

if($_POST) {

	$username = isset($_POST['username']) ? trim($_POST['username']) : '';
	$password = isset($_POST['password']) ? $_POST['password'] : '';

	if($username === '' || $password === '') {
		if($username === '') {
			$errors[] = "Username is required";
		}
		if($password === '') {
			$errors[] = "Password is required";
		}
	} else {
		$stmt = $connect->prepare("SELECT user_id, password, role FROM users WHERE username = ?");
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$result = $stmt->get_result();

		if($result->num_rows == 1) {
			$value = $result->fetch_assoc();
			if(password_verify($password, $value['password'])) {
				// set session
				session_regenerate_id(true);
				$_SESSION['userId'] = (int)$value['user_id'];
				$_SESSION['role'] = $value['role'];

				header('location:'.$store_url.'dashboard.php');
				exit;
			} else {
				$errors[] = "Incorrect username/password combination";
			} // /else
		} else {
			$errors[] = "Username does not exist";
		} // /else
	} // /else not empty username // password

} // /if $_POST
?>

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

	<!-- custom css -->
	<link rel="stylesheet" href="custom/css/custom.css">

	<!-- jquery -->
	<script src="assests/jquery/jquery.min.js"></script>
	<script src="custom/js/bootstrap-replacement.js"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
	<div class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4 py-8">
		<div class="w-full max-w-xl rounded-xl border border-slate-200 bg-white shadow-sm">
			<div class="border-b border-slate-200 px-6 py-4">
				<h3 class="text-lg font-semibold text-slate-800"><i class="fa fa-calendar text-indigo-600"></i> School Timetable Solver &mdash; Please Sign in</h3>
			</div>
			<div class="px-6 py-5">
				<div class="messages mb-4 space-y-2">
					<?php if($errors) {
						foreach ($errors as $key => $value) {
							echo '<div class="rounded-lg border border-orange-300 bg-orange-100 px-4 py-3 text-sm text-orange-900" role="alert">
							<i class="glyphicon glyphicon-exclamation-sign"></i>
							'.htmlspecialchars($value).'</div>';
						}
					} ?>
				</div>

				<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" id="loginForm" class="space-y-4">
					<div>
						<label for="username" class="mb-1 block text-sm font-medium text-slate-700">Username</label>
						<input type="text" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" id="username" name="username" placeholder="Username" autocomplete="off" />
					</div>
					<div>
						<label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
						<input type="password" class="block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" id="password" name="password" placeholder="Password" autocomplete="off" />
					</div>
					<div>
						<button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
							<i class="glyphicon glyphicon-log-in"></i> Sign in
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</body>
</html>
