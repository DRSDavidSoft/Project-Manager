<?php

	$page ['app_title']= 'TODO Manager';
	$page ['page_title']= 'Projects';
	$page ['page_description']= 'Manage a list of your projects here.';
	
	# Mark start time
	$startTime = microtime(true);
	
	global $db;
	
	$search = explode( ' ', trim(@$_REQUEST['search']) );
	$queries = []; $filters = [ 'is_open'=>'true' ]; 
	foreach ( array_filter($search) as $node) {
		$fields = explode( ':', $node, 2 );
		if ( !empty($fields[1]) ) $filters[$fields[0]] = explode(',', $fields[1]);
		else $queries []= $node;
	}
	
	global $all_tasks, $states;
	
	$all_tasks = dbRead( 'tasks' );
	
	$states = [
		'UNKNOWN'   => 0,
		'OPEN'      => 1,
		'CLOSED'    => 2,
		'DONE'      => 4,
		'SUSPENDED' => 8,
		'FAILED'    => 16,
		'OVERDUE'   => 32
	];
	
	function getTasks( $filters, $queries ) {
		global $all_tasks, $states;
		
		$tasks = array_filter( $all_tasks, function( $task ) use($filters, $queries, $states) {
			$match = true;
			if ( !empty( $filters['is_open'] ) ) {
				$status = intval( $task['status'] );
				//if ( !($status & intval($states['OPEN'])) ) $match = !filter_var($filters['is_open'], FILTER_VALIDATE_BOOLEAN);
				if ( !($status & intval($states['OPEN'])) ) $match = false;
			}
			if ( !empty( $filters['category'] ) ) {
				$items = array_unique( array_map( 'trim', explode( ',', $task['category'] ) ) );
				foreach ($filters['category'] as $node) if ( !in_array($node, $items) ) $match = false;
			}
			if ( !empty( $filters['project'] ) ) {
				$ids = array_unique( array_map( 'trim', explode( ',', $task['project_id'] . ', ' . $task['projects_linked'] ) ) );
				foreach ($filters['project'] as $node) if ( !in_array($node, $ids) ) $match = false;
			}
			if ( !empty( $queries ) ) {
				$any = false;
				foreach ($queries as $node) {
					if ( strstr($task['name'] . "\n". $task['description'], $node) ) { $any = true; break; }
				}
				if ( !$any ) $match = false;
			}
			return $match;
		} );
		
		return $tasks;
		
	}
	
	$tasks = getTasks( $filters, $queries );
	
	$projects = dbRead( 'projects' );
	$sidebar = array_map( function($node) use($filters, $queries) {
		return array(
			'name'        => $node['name'],
			'description' => $node['description'],
			'amount'      => count( getTasks( [ 'is_open'=>'true', 'project' => array($node['id']) ] , [] ) ),
			'link'        => rtrim(WWW_DIR, '/').'/page/' . 'list?search=project:' . $node['id'],
			'selected'    => is_array(@$filters['project']) && in_array( $node['id'], $filters['project'] )
		);
	}, $projects);
	
	# Show time taken for script execution
	function timify($input) {return "about ".round($input*100)/100;}
	$page ['query_time']= "Check took ".timify(microtime(true) - $startTime)." seconds.";

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta id="viewport" name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, minimum-scale=1, maximum-scale=1" />

		<title> <?= $page['app_title']; ?> • <?= $page['page_title']; ?> </title>
		
		<base href="<?= rtrim(WWW_DIR, '/') . '/view/'; ?>" />

		<meta name="robots" content="noindex"/ >
		<meta name="description" content="Web-based application and UI.">

		<link rel="icon" href="favicon.ico" type="image/x-icon" />
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
		<meta name="mobile-web-app-capable" content="yes" />
		<meta name="theme-color" content="#000000" />
		
		<script src="lib/jquery/jquery.min.js"></script>
		<link rel="stylesheet" href="lib/font-awesome/css/font-awesome.min.css" />
		
		<link rel="stylesheet" href="lib/bootstrap/css/bootstrap.min.css" />
		<script src="lib/bootstrap/js/bootstrap.min.js"></script>

		<link rel="stylesheet" href="fonts/webfonts.css" />
		
		<link rel="stylesheet" href="css/main.css" />
		<link rel="stylesheet" href="css/design.css" />
		
	</head>

	<body translate="no" >
	
	<!--
		<link rel="stylesheet" href="./assets/bubbles/bubbles.css" />
		<script src="./assets/bubbles/bubbles.js"></script>
		<ul class="bg-bubbles"></uL>
	-->
	
		<header class="main">
			
			<ul class="navbar">
				<div class="container">
					
				</div>
			</ul>
			
			<div class="intro">
				<div class="container">
					<h1 class="site-title"> <?= $page['page_title']; ?> </h1>
					<p class="description">
						<?= $page['page_description']; ?>
					</p>
				</div>
			</div>

		</header>
		
		<div class="container">
				
			<!--
			<section class="site-content">
				<p class="description h4">
					Please login to your account first.
				</p>
				
				<button class="btn btn-primary btn-square float-bottom">
					Login
				</button>
			</section>
			-->
			
			<section class="site-content">
				<p class="description h4">
					
				</p>
				
				<div class="row">
					
					<div class="col-md-8">
					
						<!--
						<form method="POST" action="">
							<div class="form-group">
								<label for="email">Email address:</label>
								<input type="email" class="form-control" id="email">
							</div>
							<div class="form-group">
								<label for="pwd">Password:</label>
								<input type="password" class="form-control" id="pwd">
							</div>
							<button type="submit" class="btn btn-default">Submit</button>
						</form>
						-->
						
						<form class="search" method="GET" action="">
							<div class="input-group">
								<input type="text" class="form-control" placeholder="Search" name="search" value="<?= trim(@$_REQUEST['search']); ?>">
								<div class="input-group-btn">
									<button class="btn btn-default" type="submit">
										Search
									</button>
								</div>
							</div>
						</form>
						
						<div class="container-fluid">
						
						<br>
						
						<style>
							.panel-footer .progress {
								background: white;
							}
						</style>
						
						<?php foreach( $tasks as $task ) {
							$id = intval($task['project_id']);
							
							$project_objs = array_values( array_filter($projects, function( $a ) use ($id) {
								return $a['id'] == $id;
							}) );
							$project_obj = array_pop( $project_objs );
							
							$projects_linked = [];
							foreach( array_map('trim', explode(',', $task['projects_linked'])) as $id ) {
								$project_objs = array_values( array_filter($projects, function( $a ) use ($id) {
									return $a['id'] == $id;
								}) );
								$projects_linked = array_merge( $projects_linked, $project_objs );
							}
							
							$projects_linked = implode(', ', array_map(function( $a ) {
								return $a['name'];
							}, $projects_linked));
							
							$class = [];
							
							$status = intval( $task['status'] );
							if ( !($status & intval($states['OPEN'])) ) $class []= 'panel-default';
							else {
								if( ($task['type'] == 'meeting') ) $class []= 'success';
								if( ($task['type'] == 'todo') ) $class []= 'info';
								if( ($status & intval($states['OVERDUE'])) ) $class []= 'warning';
								if( ($status & intval($states['SUSPENDED'])) ) $class []= 'danger';
							}
							
							$dir = ( preg_match('|[ا-ی]|iU', $task['name'] . "\n" . $task['description']) ) ? 'rtl' : 'ltr';
						?>
							<div class="panel <?= implode(' ', array_map(function($a){return 'panel-'.$a;},$class)); ?>" dir="<?= $dir; ?>">
								<div class="panel-heading">
									<?php if (!empty($project_obj)) {?>
									<a href="?search=project:<?= $task['project_id'] ; ?>" class="badge pull-left" title="<?= @$projects_linked; ?>"><?= @$project_obj['name']; ?></a>
									<?php } ?>
									<a href="<?= rtrim(WWW_DIR, '/') . '/page/'; ?>edit?id=<?= $task['id']; ?>">
									<?= $task['name']; ?>
									</a>
								</div>
								<div class="panel-body">
									<p style="text-align: justify; line-height: 180%;">
										<?php
											echo nl2br(htmlentities(preg_replace('|[\r\n]+|', "\n", $task['description'])));
										?>
									</p>
								</div>
								<div class="panel-footer">
									<div class="row">
										<div class="col-sm-8">
											<div class="progress">
												<div class="progress-bar <?= implode(' ', array_map(function($a){return 'progress-bar-'.$a;},$class)); ?> progress-bar-striped" role="progressbar" aria-valuenow="<?= ($task['progress_total'] * 100); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= ($task['progress_total'] * 100) . '%'; ?>;">
													<?= ($task['progress_total'] * 100) . '%'; ?>
												</div>
											</div>
										</div>
										<div class="col-sm-4">
											<?php foreach ( array_map('trim', explode(',', $task['category']) ) as $node ) { ?>
											<a class="label label-success" href="?search=category:<?= $node; ?>"><?= trim($node) ; ?></a>
											<?php } ?>
										</div>
									</div>
								</div>
							</div>
						<?php } ?>
						
						</div>

						<div class="well well-sm"> Total items: <?= count($tasks) ?>, <?= $page['query_time']; ?></div>
					</div>
				
					<div class="col-md-4">
						<div class="list-group">
							<a href="?search=" class="list-group-item <?= empty($filters['project']) ? 'active' : '' ?>">
								<span class="badge"><?= count( getTasks( ['is_open'=>'true'], [] ) ) ?></span>
								<h4 class="list-group-item-heading"> All Tasks </h4>
								<p class="list-group-item-text"> </p>
							</a>
						<?php foreach( $sidebar as $node ) { ?>
							<a href="<?= $node['link'] ?>" class="list-group-item <?= $node['selected'] ? 'active' : '' ?>">
								<?php if( !empty($node['amount']) ) { ?> <span class="badge"><?= $node['amount'] ?></span> <?php } ?>
								<h4 class="list-group-item-heading"><?= $node['name'] ?></h4>
								<p class="list-group-item-text"><?= $node['description'] ?></p>
							</a>
						<?php } ?>
						</div>
					</div>
					
				</div>

			</section>

		</div>
		
		<script>
		/*
			if (location.href.indexOf('static') == -1)
			$(function() {
				setTimeout(function() {
					location.reload();
				}, 3000);
			});
		*/
		</script>

	</body>
</html>
 
