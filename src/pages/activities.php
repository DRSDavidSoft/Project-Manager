<?php

	# Hold page data
	$page = [];
	
	$page ['app_title']= 'TODO Manager';
	$page ['page_title']= 'Activities';
	$page ['page_description']= 'List of all recorded activities and their details.';
	
	# Mark start time
	$startTime = microtime(true);
	
	$activities = dbRead( 'activities' );
	
	# Show time taken for script execution
	function timify($input) {return "about ".round($input*100)/100;}
	$page ['query_time']= "Check took ".timify(microtime(true) - $startTime)." seconds.";
	
	# Create the web-page
	$document = new Webpage( dirname(__FILE__) . '/../view/template.html' );
	$document->setCharset("utf-8");
	$document->setTitle( $page['app_title'] . ' &bull; ' . $page['page_title'] );
	$document->setBase( rtrim(WWW_DIR, '/') . '/view/' );
	
	//echo $document->generateHTML( false );

?>
<!DOCTYPE html>
<html>
	<?= $document->getSection( 'head' ); ?>

	<body translate="no" >
	
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
			
			<section class="site-content">
				<p class="description h4">
					
				</p>
				
				<div class="row">
					
					<div class="col-md-8">
						
						<div class="container-fluid">
						
						<br>
						
						<style>
							.panel-footer .progress {
								background: white;
							}
						</style>
						
						<?php 
							// To be changed
							$states = [
								'UNKNOWN'   => 0,
								'OPEN'      => 1,
								'CLOSED'    => 2, // paused
								'DONE'      => 4,
								'SUSPENDED' => 8,
								'FAILED'    => 16,
								'OVERDUE'   => 32
							];
						?>
						
						<?php foreach( $activities as $activity ) {
							$id = intval($activity['task_id']);
							
							$task = @array_pop( dbRead( 'tasks', [ 'id' => $id ] ) );
							
							$class = [];
							
							$status = intval( $activity['status'] );
							if ( !($status & intval($states['OPEN'])) ) $class []= 'panel-default';
							else {
								if( ($status & intval($states['CLOSED'])) ) $class []= 'warning'; else
								if( ($status & intval($states['OPEN'])) ) $class []= 'success'; else
								$class []= 'default';
							}
							
							$dir = ( preg_match('|[ا-ی]|iU', $task['description'] . $activity['description']) ) ? 'rtl' : 'ltr';
						?>
							<div class="panel <?= implode(' ', array_map(function($a){return 'panel-'.$a;},$class)); ?>" dir="<?= $dir; ?>">
								<div class="panel-heading">
									<?php /* if (!empty($project_obj)) {?>
									<a href="?search=project:<?= $activity['project_id'] ; ?>" class="badge pull-left" title="<?= @$projects_linked; ?>"><?= @$project_obj['name']; ?></a>
									<?php } */ ?>
									<a href="edit.php?id=<?= $task['id']; ?>">
									<?= $task['name']; ?>
									</a>
								</div>
								<div class="panel-body">
									<p style="text-align: justify; line-height: 180%;">
										<?php
											echo nl2br(htmlentities(preg_replace('|[\r\n]+|', "\n", $activity['description'])));
										?>
										<hr>
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

						<div class="well well-sm"> Total items: <?= count($activities) ?>, <?= $page['query_time']; ?></div>
					</div>
				
					<div class="col-md-4">

					</div>
					
				</div>

			</section>

		</div>

	</body>
</html>
 
