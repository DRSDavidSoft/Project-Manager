<?php
	
	$page ['app_title']= 'TODO Manager';
	
	# Mark start time
	$startTime = microtime(true);
	
	global $db;
	
	if ( @$_REQUEST['action'] == 'edit' ) {
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) { header("HTTP/1.0 405 Method Not Allowed", true, 405); die( "Use POST method instead" ); }
		// TODO: use these instead "id:name:priority:progress_total:type:project_id:projects_linked:description:date_created:date_deadline:date_lastactivity:category:status"
		$postData = $_POST;
		$postData = array_map(function($node){
			return strlen("$node")===0? NULL : $node;
		}, $postData);
		if ( empty( $postData['name'] ) ) { header("HTTP/1.0 400 Bad Request", true, 400); die( "The 'name' field cannot be empty." ); }
		if ( empty( $postData['id'] ) ) {
			unset( $postData['id'] );
			$success = dbAdd( 'tasks', $postData );
			$id = $db->lastInsertId();
		}
		else {
			$id = $postData['id']; unset( $postData['id'] );
			$success = dbWrite( 'tasks', ['id'=>$id], $postData );
		}
		if ( !$success ) { die("Something went wrong!"); }
		else { header("Location: ".rtrim(WWW_DIR, '/') . '/page/'."edit?id=$id&msg=success", true, 302); exit; }
	}
	
	# Just as a template... remove later
	$msgs = [
		'info' => [
			'type'  => 'info',
			'title' => 'Message:',
			'text'  => 'Nothing has happened yet.'
		],
		'success' =>
		[
			'type'  => 'success',
			'title' => 'Success!',
			'text'  => 'The data has been saved into the list.'
		],
		'notfound' =>
		[
			'type'  => 'danger',
			'title' => 'Error!',
			'text'  => 'The task with the requested ID was not found.'
		]
		
	];
	
	if ( empty($_REQUEST['msg']) ) $_REQUEST['msg'] = 'info';
	$alert = $msgs[ $_REQUEST['msg'] ];
	
	$search = explode( ' ', trim(@$_REQUEST['search']) );
	$queries = []; $filters = [ 'is_open'=>'true' ]; 
	foreach ( array_filter($search) as $node) {
		$fields = explode( ':', $node, 2 );
		if ( !empty($fields[1]) ) $filters[$fields[0]] = explode(',', $fields[1]);
		else $queries []= $node;
	}
	
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
				if ( ($status & intval($states['CLOSED'])) ) $match = false;
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
			'id'          => $node['id'],
			'selected'    => is_array(@$filters['project']) && in_array( $node['id'], $filters['project'] )
		);
	}, $projects);
	
	
	$task = @array_pop( dbRead( 'tasks',
		[ 'id' => intval( @$_REQUEST['id'] ) ]
	) );
	
	if ( empty($task) ) {
		if ( !empty( $_REQUEST['id'] ) ) {
			$alert = $msgs['notfound'];
		}
	}
	
	if ( empty($task) ) {
		$page ['page_title']= 'Add a new task';
		$page ['page_description']= 'Enter the name and description of your new task.';
	} else {
		$page ['page_title']= 'Edit task';
		$page ['page_description']= str_replace( '%s', $task['name'], 'Modify the content of "%s" task.');
	}
	
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
		<script src="lib/moment/js/moment.min.js"></script>
		<link rel="stylesheet" href="lib/font-awesome/css/font-awesome.min.css" />
		
		<link rel="stylesheet" href="lib/bootstrap/css/bootstrap.min.css" />
		<script src="lib/bootstrap/js/bootstrap.min.js"></script>
		
		<link rel="stylesheet" href="lib/tagsinput/bootstrap-tagsinput.css">
		<script src="lib/tagsinput/bootstrap-tagsinput.min.js"></script>
		
		<script src="lib/datetimepicker/js/bootstrap-datetimepicker.min.js"></script>
		<link rel="stylesheet" href="lib/datetimepicker/css/bootstrap-datetimepicker.min.css" />
		
		<link rel="stylesheet" href="lib/rangeslider/rangeslider.css">
		<script src="lib/rangeslider/rangeslider.min.js"></script>

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
				
				<?php 
					if ( !empty($alert) ) {
				?>
					<div class="alert alert-<?= $alert['type']; ?> alert-dismissible">
						<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
						<strong><?= $alert['title']; ?></strong> <?= $alert['text']; ?>
					</div>
				<?php
					}
				?>
				
				<div class="row">
					
					<div class="col-md-8">
						
						<h3>Enter Task's Details:</h3>
						
						<form class="form-horizontal" method="POST" action="<?= rtrim(WWW_DIR, '/') . '/page/edit' ?>?action=edit">
							<div class="form-group hidden">
								<label class="control-label col-sm-2" for="id">ID:</label>
								<div class="col-sm-10">
									<input name="id" id="id" value="<?= @$task['id']; ?>" type="text" class="form-control" placeholder="Task's ID">
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-sm-2" for="name">Name:</label>
								<div class="col-sm-10">
									<input name="name" id="name" value="<?= @$task['name']; ?>" type="text" class="form-control" placeholder="Task's name">
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-sm-2" for="priority_level">Priority Level:</label>
								<div class="col-sm-10" id="priority_level">
									<a class="btn" role="button" tabindex="0" data-value="0"> 0% </a>
									<a class="btn" role="button" tabindex="0" data-value="20"> 20% </a>
									<a class="btn" role="button" tabindex="0" data-value="40"> 40% </a>
									<a class="btn" role="button" tabindex="0" data-value="60"> 60% </a>
									<a class="btn" role="button" tabindex="0" data-value="80"> 80% </a>
									<a class="btn" role="button" tabindex="0" data-value="100"> 100% </a>
								</div>
							</div>
							<style>
								#priority_level .btn {
									border-radius: 1px;
									transition: .1s ease;
									width: 55px;
									height: 30px;
									line-height: 18px;
									margin: 0 2px 5px 0;
								}
							</style>
							<script>
								$(function() {
									
									$('#priority_level .btn').click(function(e) {
										$('input[name=priority]').val( parseInt( $(this).attr('data-value') ) / 100 ).change()
										e.preventDefault();
									});
									
									$('input[name=priority]').on('input change', function(e) {
										var amount = $(this).val() * 100;
										var items = [];

										$('#priority_level .btn').each(function() {
											items.push( {
												"diff": Math.round( Math.abs( parseInt( $(this).attr('data-value') ) - amount ) ),
												"name": $(this).attr('data-value').trim()
											} );
										});

										items.sort( function(a, b) {
											if ( a.diff === b.diff ) return 0;
											else return a.diff > b.diff ? +1 : -1
										} );
										
										var btns = $('#priority_level .btn').removeClass('btn-primary btn-success btn-default btn-danger btn-warning');
										var selected = btns.filter( '[data-value="' + items[0].name + '"]' );
										btns.not(selected).addClass('btn-default'); selected.addClass('btn-success');
										
										btns.each( function() {
											$(this).attr( 'aria-pressed', $(this).is('.btn-success') );
										});
										
										e.preventDefault();
										e.stopPropagation();
									});
									
								});
							</script>
							<div class="form-group hidden">
								<label class="control-label col-sm-2" for="priority">Priority:</label>
								<div class="col-sm-8" style="margin-top: 10px;">
									<input name="priority" id="priority" value="<?= floatval(@$task['priority']) ; ?>" type="range" min="0" max="1" step="0.01">
								</div>
								<div class="col-sm-2">
									<input class="output" />
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-sm-2" for="progress_total">Progress:</label>
								<div class="col-sm-8" style="margin-top: 10px;">
									<input name="progress_total" id="progress_total" value="<?= floatval(@$task['progress_total']) ; ?>" type="range" min="0" max="1" step="0.01">
								</div>
								<div class="col-sm-2">
									<input class="output" />
								</div>
							</div>
							<script>
								$(function() {
									$('input.output').on('input change', function(e) {
										if ( this.value.indexOf('%') == -1 && this.value.substr(-1) != '%' )
											this.value = this.value.substr( 0, this.value.length-1 ) + '%';
										var amount = this.value.replace( /[^0-9]+/, '' ).replace( /^0+/, '');
										if ( amount.toString().length == 0 ) amount = 0;
										amount = Math.min( 100, Math.max( 0, parseInt(amount) ) );
										this.value = amount + '%'; e.preventDefault();
										
										var rangeField = $(this).parent().parent().find('input[type=range]');
										if ( Math.round(rangeField.val() * 100) != Math.round(amount) ) rangeField.val( amount/100 ).trigger('change');
									});
								});
							</script>
							<style>
								input.output {
									display: inline;
									background: transparent;
									border: 0;
									outline: 0;
									text-align: center;
									width: 100%;
									font-size: 24px;
									margin-top: -2px;
								}
							</style>
							<script>
								$(function() {
									$('input[type="range"]').rangeslider( {
										polyfill: false,
										onSlide: function(position, value) {
											$( this.$element ).parent().parent().find('.output').val( Math.round(value * 100) + '%' );
											$(this).closest('input[type=range]').val( value ).trigger('input');
											//$(this.$element).parent().find('.rangeslider__fill').css('width', (value * 100) + '%' );
										}
									} ).change();
									$(window).trigger('resize');
								});
							</script>
							<style>
								input[type="range"]:focus + .rangeslider .rangeslider__handle {
									-moz-box-shadow: 0 0 8px #337ab7;
									-webkit-box-shadow: 0 0 8px #337ab7;
									box-shadow: 0 0 8px #337ab7;
								}
							
								.rangeslider--horizontal {
									width: 100%;
									height: 13px;
								}
								
								.rangeslider, .rangeslider__fill {
									-webkit-border-radius: 3px;
									border-radius: 3px;
								}
								
								.rangeslider {
									background-color: white;
									-webkit-box-shadow: inset 0 1px 0 rgba(0,0,0, 0.2);
									box-shadow: inset 0 1px 0 rgba(0,0,0, 0.2);
								}
								
								.rangeslider__fill {
									background-color: #337ab7;
									-webkit-box-shadow: inset 0 1px 0 rgba(0,0,0, 0.1);
									box-shadow: inset 0 1px 0 rgba(0,0,0, 0.1);
								}
								
								.rangeslider__handle {
									width: 24px; height: 24px;
									border-width: 1px;
									-webkit-box-shadow: none;
									box-shadow: none;
								}
								
								.rangeslider__handle:after {
									background-image: none;
								}
								
								.rangeslider--horizontal .rangeslider__handle {
									top: -5px;
								}
							</style>
							<div class="form-group">
								<label class="control-label col-sm-2" for="type">Type:</label>
								<div class="col-sm-10">
									<select name="type" id="type" class="form-control" placeholder="Task's name">
									<?php 
										$options = [
											'todo' => 'Task',
											'meeting' => 'Meeting'
										];
										foreach ( $options as $name=>$value ) {
									?>
										<option value="<?= $name ?>" <?= @$task['type'] == $name ? 'selected' : ''?>><?= $value ?></option>
									<?php
										}
									?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-sm-2" for="project_id">Task's Project:</label>
								<div class="col-sm-5">
									<input name="project_id" id="project_id" value="<?= @$task['project_id']; ?>" type="number" class="form-control" placeholder="Task's Main Project">
								</div>
								<div class="col-sm-5">
									<input name="projects_linked" id="projects_linked" value="<?= @$task['projects_linked']; ?>" type="text" class="form-control" placeholder="Task's Linked Projects">
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-sm-2" for="description">Description:</label>
								<div class="col-sm-10">
									<div class="paper">
										<textarea name="description" id="description" class="form-control" placeholder="" rows=10><?= @$task['description']; ?></textarea>
									</div>
									<style>
										
										.paper > textarea {
											resize: vertical;
											box-shadow: none;
											background: transparent;
											margin: 0; padding: 0;
											border: 0; color: #555;
											/* padding: 28px 55px; */
											padding: 9px 55px;
											line-height: 30px;
											text-align: justify;
											font-weight: 400;
										}
											
										.paper {
											position: relative;
											border: 1px solid #B5B5B5;
											background: white;
											background: -webkit-linear-gradient(top, #DFE8EC 0%, white 8%) 0 57px;
											background: -moz-linear-gradient(top, #DFE8EC 0%, white 8%) 0 57px;
											background: linear-gradient(top, #DFE8EC 0%, white 8%) 0 57px;
											-webkit-background-size: 100% 30px;
											-moz-background-size: 100% 30px;
											-ms-background-size: 100% 30px;
											background-size: 100% 30px;
										}
										
										.paper::before {content:""; z-index:-1; margin:0 1px; width:calc( 100% - 2px ); height:10px; position:absolute; bottom:-3px; left:0; background:white; border:1px solid #B5B5B5;}
										.paper::after {content:''; position:absolute; width:0px; top:0; right:40px; bottom:0; border-left:1px solid #F8D3D3;}
										
										/*
										.paper textarea::-webkit-scrollbar {
											width: 2px;
										}
										*/
									</style>
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-sm-2" for="date_created">Task's Dates:</label>
								<div class="col-sm-3">
									<small class="field-title">Creation Date:</small>
									<input name="date_created" id="date_created" value="<?= @$task['date_created']; ?>" type="number" class="hidden form-control <?= empty($task['date_created']) ? 'current-time' : '' ?>" placeholder="Creation Timestamp">
									<input type="date" class="form-control" placeholder="Creation Date">
								</div>
								<div class="col-sm-3">
									<small class="field-title">Due Date:</small>
									<input name="date_deadline" id="date_deadline" value="<?= @$task['date_deadline']; ?>" type="number" class="hidden form-control" placeholder="Deadline Timestamp">
									<input type="date" class="form-control" placeholder="Due Date">
								</div>
								<div class="col-sm-3">
									<small class="field-title">Last Activity:</small>
									<input name="date_lastactivity" id="date_lastactivity" value="<?= @$task['date_lastactivity']; ?>" type="number" class="hidden form-control" placeholder="Last Activity Timestamp">
									<input type="date" class="form-control" placeholder="Last Activity">
								</div>
							</div>
							<style>
								.field-title {
									display: block;
									width: 100%;
									text-align: left;
									margin: 0 auto;
									padding: 2px 0px;
								}
							</style>
							<script>
								function setMomentOffset(serverTime) {
									var offset = new Date(serverTime).getTime() - Date.now();
									moment.now = function() {
										return offset + Date.now();
									}
								}
								
								// setMomentOffset( <?= time() * 1000; ?> );
								$(function() {
									var startTime = new Date().getTime();
									$.ajax({
										url : location.href,
										type : 'HEAD',
										success : function(res, status, xhr){
											var requestTime = new Date().getTime() - startTime;
											var responseTime = moment.utc( xhr.getResponseHeader("Date"), "ddd, DD MMM YYYY HH:mm:ss" ).valueOf();
											setMomentOffset( requestTime + responseTime );
										},
										error : function(){
											//doSomethingElse();
										}
									});
								});
								
								$(function() {
									$('[type=date]').attr('type', 'text').datetimepicker().each(function() {
										var epochTime = parseInt( $(this).parent().find( '[name^=date_]' ).val() );
										var $dp = $(this).data("DateTimePicker");
										$dp.date(moment.unix(epochTime));
										$(this).on('dp.change', function(e) {
											$(this).parent().find( '[name^=date_]' ).val( e.date.unix() );
										});
										$(this).on('click focus', function() {
											$(this).parent().find( '[name^=date_]' ).removeClass('current-time');
										});
									});
									$( '[name^=date_]' ).on('input change', function(e) {
										$(this).parent().find( '[type=text]' ).data("DateTimePicker")
										.date(moment.unix(parseInt($(this).val())));
									});
									window.updateTSfields = function() {
										$('.current-time').val( moment().unix() ).change();
									}
									window.clearInterval( window.timerID );
									window.timerID = window.setInterval(updateTSfields, 1000);
									updateTSfields();
								});
							</script>
							<div class="form-group">
								<label class="control-label col-sm-2" for="category">Categories:</label>
								<div class="col-sm-10"> 
									<input name="category" id="category" data-role="tagsinput" class="form-control" placeholder="example, category" value="<?= preg_replace( '|\s+\,\s+|', ',', @$task['category'] ); ?>">
								</div>
							</div>
							<style>
								.bootstrap-tagsinput {
									width: 100%;
									text-align: left;
								}
							</style>
							<script>
								// TODO: Read: https://bootstrap-tagsinput.github.io/bootstrap-tagsinput/examples/
								$(function() {
									$('.bootstrap-tagsinput').on( 'keydown', function(e) {
										//console.log(e.which);
										if ( ":186:188:51:13:".indexOf(':' + e.which + ':') > -1 ) {
											$(this).find('input').trigger('blur').trigger('focus').val('');
											e.preventDefault();
										}
									});
								});
							</script>
							<div class="form-group"> 
								<label class="col-sm-2">
									Status:
								</label>
								<div class="col-sm-offset-0 col-sm-10">
								
									<input name="status" type="number" value="<?= $task['status']; ?>" class="hidden form-control" />
										
										<style>
											input.toggle {
												-webkit-appearance: none;
												-moz-appearance: none;
												appearance: none;
												/* Do not use "display: none;" it prevents focus with tab */
												position: absolute;
												z-index: -100;
												opacity: 0;
											}
											
											input.toggle + label {
												outline: 0;
												display: inline-block;
												position: relative;
												text-align: center;
												cursor: pointer;
												overflow: hidden;
												/* TODO: use prefixfree https://leaverou.github.io/prefixfree/ */
												-webkit-user-select: none;
												   -moz-user-select: none;
												    -ms-user-select: none;
												        user-select: none;
											}
											
											input.toggle.skew + label {
												-webkit-transform: skew(-10deg);
														transform: skew(-10deg);
												-webkit-backface-visibility: hidden;
														backface-visibility: hidden;
												-webkit-transition: all .2s ease;
												        transition: all .2s ease;
												font-family: 'Roboto', sans-serif;
												text-transform: uppercase;
												min-width: 75px; height: 24px;
												line-height: 24px;
												letter-spacing: 1px;
												background: #888;
											}
											
											input.toggle.skew + label:after,
											input.toggle.skew + label:before {
												-webkit-transform: skew(10deg);
														transform: skew(10deg);
												display: inline-block;
												-webkit-transition: all .2s ease;
												        transition: all .2s ease;
												width: 100%;
												text-align: center;
												position: absolute;
												font-weight: bold;
												color: #fff;
												text-shadow: 0 1px 0 rgba(0, 0, 0, 0.4);
											}
											input.toggle.skew + label:after {
												left: 100%;
												content: attr(data-on);
											}
											input.toggle.skew + label:before {
												left: 0;
												content: attr(data-off);
											}
											input.toggle.skew + label:active {
												background: #888;
											}
											input.toggle.skew + label:active:before {
												left: -10%;
											}
											input.toggle.skew:checked + label {
												background: #2ecc71;
											}
											input.toggle.skew:focus + label {
												box-shadow: 0 0 3px #888;
											}
											input.toggle.skew:checked:focus + label {
												box-shadow: 0 0 3px #2ecc71;
											}
											input.toggle.skew:checked + label:before {
												left: -100%;
											}
											input.toggle.skew:checked + label:after {
												left: 0;
											}
											input.toggle.skew:checked + label:active:after {
												left: 10%;
											}
										</style>
										
										<div style="float: left">
											<input class="toggle skew" id="status-open" value="1" type="checkbox" />
											<label data-off="Closed" data-on="Open" for="status-open"></label>
										</div>
										
			
										<input class="toggle btn" type="checkbox"  value="2" id="status-closed">     <label for="status-closed">Closed</label>
										<input class="toggle btn" type="checkbox"  value="4" id="status-finished">   <label for="status-finished">Finished</label>
										<input class="toggle btn" type="checkbox"  value="8" id="status-suspended">  <label for="status-suspended">Suspended</label>
										<input class="toggle btn" type="checkbox" value="16" id="status-failed">     <label for="status-failed">Failed</label>
										<input class="toggle btn" type="checkbox" value="32" id="status-overdue">    <label for="status-overdue">Overdue</label>
										
										<style>
											input.toggle.btn + label {
												background: #ecf0f1;
												color: #3498db;
												border: 1px solid #3498db;
												font-weight: 100;
												border-radius: 0px;
												transition: all .2s ease;
												backface-visibility: hidden;
												padding: 0 10px;
												letter-spacing: 0;
												height: 24px;
												line-height: 24px;
												transition: all .2s ease;
											}
											
											input.toggle.btn:checked + label {
												border-color: #3498db;
												background-color: #3498db;
												color: #ecf0f1;
											}
											
											input.toggle.btn + label:active {
												background-color: #ecf0f1;
												border-color: #2980b9;
												color: #2980b9;
											}
											
											input.toggle.btn:checked + label:active {
												background-color: #2980b9;
												border-color: #2980b9;
												color: #ecf0f1;
											}
											
											input.toggle.btn:not(:checked):focus + label {
												background-color: #ecf0f1;
												border-color: #2980b9;
												color: #2980b9;
											}
											input.toggle.btn:checked:focus + label {
												box-shadow: 0 0 3px #2980b9;
											}
										</style>
										
									</div>
									<?php
										$states_list = [
											1  => 'Open',
											2  => 'Closed',
											4  => 'Finished',
											8  => 'Suspended',
											16 => 'Failed',
											32 => 'Overdue'
										];
									?>
									<script>
										$(function() {
											var states = <?= json_encode($states_list); ?>;
											//var enabled = [];
											
											$('[name=status]').on('input change', function() {
												var val = $(this).val();
												//for (i in states) enabled[ states[i] ] = !!( parseInt(i) & parseInt(val) )
												for (i in states) ( $('input[value=%i], label:contains(%s) input'.replace('%s', states[i]).replace('%i', i)).prop('checked', !!( parseInt(i) & parseInt(val) ))  .get(0));
											}).trigger('input');
										});
										
										$( selector = 'input[type=checkbox], input[type=radio]' ).on( 'input change', function() {
											//var enabled = [];
											var state = 0;
											$( selector ).each( function() {
												var id = $(this).val(), checked = $(this).prop('checked');
												//enabled[id] = checked;
												if (checked) state += parseInt(id);
											} );
											var $obj = $('[name=status]');
											if ( $obj.val() != state ) $obj.val( state );
										} );
			
									</script>
									
							</div>
							<div class="form-group"> 
								<div class="col-sm-offset-0 col-sm-12">
									<button type="submit" class="btn btn-main" style="width: 100%"><?= empty($task) ? 'Add New Task' : 'Edit Task' ?></button>
								</div>
							</div>
						</form>
						
						<script>
							$(function() {
								$('input, textarea').on('input change keydown focus', function() {
									var dir = ( $(this).val().match(/[ا-ی]/) ) ? 'rtl' : 'ltr';
									$(this).attr( 'dir', dir );
								}).trigger('input');
							});
						</script>

						<!-- <div class="well well-sm"> Total items: <?= count($tasks) ?>, <?= $page['query_time']; ?></div> -->
					</div>
				
					<div class="col-md-4">
						<h3> Select a Project: </h3>
						<h4> (Ctrl+Click for linked projects) </h4>
						<div class="list-group">
						<?php foreach( $sidebar as $node ) { ?>
							<a href="#" class="list-group-item" data-id="<?= $node['id'] ?>">
								<?php if( !empty($node['amount']) ) { ?> <span class="badge"><?= $node['amount'] ?></span> <?php } ?>
								<h4 class="list-group-item-heading"><?= $node['name'] ?></h4>
								<p class="list-group-item-text"><?= $node['description'] ?></p>
							</a>
						<?php } ?>
						</div>
						<script>
							$(function() {
								$('.list-group a.list-group-item').click(function(e) {
									if ( !e.ctrlKey ) {
										$(this).parent().find('.active').removeClass('active');
										$(this).addClass('active');
										$('[name=project_id]').val( $(this).attr('data-id') )
									} else {
										//if ( !$(this).is('.active') ) {
										$(this).toggleClass('list-group-item-info');
										var items = $(this).parent().find('.list-group-item-info').map(function() {
											return $(this).attr('data-id');
										}).toArray().join(', ');
										$('[name=projects_linked]').val( items )
									}
									e.preventDefault();
								});
								$('[name=project_id]').on('input', function(e) {
									$('.list-group').find('.active').removeClass('active');
									$('.list-group').find('[data-id="%s"]'.replace('%s', $(this).val())).addClass('active');
									e.preventDefault();
								}).trigger('input');
								$('[name=projects_linked]').on('input', function(e) {
									$('.list-group').find('.list-group-item-info').removeClass('list-group-item-info');
									var items = $(this).val().replace(/\s+/gi, '').split(',');
									for (var i=0; i<items.length; i++) $('.list-group').find('[data-id="%s"]'.replace('%s', items[i])).addClass('list-group-item-info');
									e.preventDefault();
								}).trigger('input');
							});
						</script>
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
 
