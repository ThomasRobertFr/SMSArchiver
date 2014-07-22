<?php
/*  Author: Thomas Robert - thomas-robert.fr - Github @ThomasRobertFr
    
    This file is part of SMSArchiver.

    SMSArchiver is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    SMSArchiver is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along SMSArchiver.  If not, see <http://www.gnu.org/licenses/> */

namespace ThomasR\SMSArchiver\Template;

mb_internal_encoding("UTF-8");

require_once 'lib/Views.php';
use ThomasR\SMSArchiver\View\MessagesView;

extract(MessagesView::main());

?>

<!DOCTYPE html>
<html lang="en">
		<head>
			<meta charset="utf-8">
			<meta name="author" content="Thomas Robert">
			<title>SMS</title>
			<link rel="stylesheet" href="dist/css/bootstrap.min.css">
			<link href='http://fonts.googleapis.com/css?family=Lato:100,300,400,700,400italic,700italic' rel='stylesheet' type='text/css'>
			<style>
				body {padding-top: 65px; font-family: Lato, sans-serif; }
				.sub-header {padding-bottom: 10px; border-bottom: 1px solid #eee; }
				.navbar-inverse .navbar-brand { line-height: normal; font-size: 39px; padding: 0 10px; font-weight: 100; color: #fff; }
				.navbar-fixed-top {border: 0; }
				.navbar-form { color: #fff; }
				.navbar-form input[type=file] { color: #fff; border: none; background: none; }
				.sidebar { position: fixed; top: 51px; bottom: 0; left: 0; z-index: 1000; display: block; padding: 20px 0px; overflow-x: hidden; overflow-y: auto; background-color: #f5f5f5; border-right: 1px solid #eee; }
				.sidebar .nav li .initial { font-size: 23px; font-weight: 300; width: 33px; height: 33px; text-align: center; border-radius: 20px; color: #FFF; background: #CCC; margin-right: 7px; }
				.sidebar .nav li .name { font-size: 17px; font-weight: 300; padding: 5px 0; }
				.sidebar .nav li .badge { margin: 7px 0; }
				.sidebar .nav li        .date { font-size: 85%; color: #AAA; padding: 7px 5px 7px 0; }
				.sidebar .nav li.active .date { color: #FFF; }
				.sidebar .nav li.active .initial { box-shadow: rgba(0, 0, 0, 0.5) 0px 0px 3px; }
				.sidebar .nav li a { border-radius: 0; }

				.main { margin-bottom: 35px; }
				.main h2 { font-weight: 100; font-size: 53px; text-align: center; margin: 0; }
				.main h3 { font-weight: 300; font-size: 20px; text-align: center; margin: 0; }
				.main .message.in  .date, .main .message.in  .initial, .main .message.in  .content { float: left; }
				.main .message.out .date, .main .message.out .initial, .main .message.out .content { float: right; }
				.main .message .initial { font-size: 23px; font-weight: 300; width: 33px; height: 33px; text-align: center; border-radius: 20px; color: #FFF; background: red; }
				.main .message .date { color: #8B8B8B; font-size: 10px; width: 35px; margin: 0 5px; }
				.main .message               .content { position: relative; max-width: 65%; padding: 6px 10px; margin: 0 15px; border-radius: 5px; background: hsl(12, 40%, 40%); margin-bottom: 6px; }
				.main .message.in            .content { text-align: left; }
				.main .message.out           .content { text-align: right; }
				.main .message               .content:after  { content: ''; position: absolute; border-style: solid; border-color: transparent #CCC; display: block; width: 0; z-index: 1; top: 9px; }
				.main .message.in            .content:after  { left:  -8px; border-width: 8px 8px 8px 0px; }
				.main .message.out           .content:after  { right: -8px; border-width: 8px 0px 8px 8px; }
				.main .message.highlight     .content        { border: 2px solid; }
				.main .message.highlight     .content:before { content: ''; position: absolute; border-style: solid; border-color: transparent #7F7F7F; display: block; width: 0; z-index: 0; top: 8px; }
				.main .message.highlight.in  .content:before { left:  -11px; border-width: 9px 9px 9px  0px; }
				.main .message.highlight.out .content:before { right: -11px; border-width: 9px  0px 9px 9px; }

				.hline { width:100%; text-align:center; border-bottom: 1px solid #E4E4E4; line-height:0.1em; margin:10px 0 20px; }
				.hline span { background:#fff; padding:0 10px; color: #8B8B8B; }
				</style>
		</head>
		<body id="top">
			<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
				<div class="container-fluid">
					<div class="navbar-header">
						<a class="navbar-brand" href="#">SMSArchiver</a>
					</div>
					<div class="navbar-collapse collapse">
						<form class="navbar-form navbar-right" method="POST" enctype="multipart/form-data" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
							Import a file:
							<input type="file" name="file" class="form-control">
							<input type="submit" class="form-control btn-primary" value="Send">
						</form>
					</div>
				</div>
			</div>
			<div class="container-fluid">
				<div class="row">
					<div class="col-sm-5 col-md-4 sidebar">
						<ul class="nav nav-pills nav-stacked">
						<?php foreach($contacts as $c) { ?>
							<li <?php if ($c['id'] == $currentContact['id']) echo 'class="active"' ?>>
								<a href="?contact=<?php echo urlencode($c['id']) ?>#bottom">
									<div class="initial pull-left" style="background: hsl(<?php echo $c['number'] ?>, 75%, 50%)"><?php echo $c['initial']; ?></div>
									<div class="pull-right badge"><?php echo $c['nb'] ?></div>
									<div class="pull-right date"><i class="glyphicon glyphicon-time"></i> <?php echo $c['lastsms']; ?></div>
									<div class="name"><?php echo $c['name']; ?></div>
								</a>
							</li>
						<?php } ?>
						</ul>
					</div>
					<div class="col-sm-7 col-sm-offset-3 col-md-8 col-md-offset-4 main">
						<?php if (!empty($popup)) { ?>
							<div class="alert alert-info" role="alert"><?php echo nl2br($popup); ?></div>
						<?php } ?>

						<h2><?php echo $currentContact['name'] ?></h2>
						<h3><?php echo implode('&bullet;', $currentContact['phones']) ?></h3>

						<div class="text-center">
							<ul class="pagination">
								<?php echo $pages ?>
							</ul>
						</div>

						<style>
							.main .message.in            .initial        { background:               hsl(<?php echo $currentContact['number'] ?>, 75%, 50%); }
							.main .message.out           .initial        { background:               hsl(<?php echo             $me['number'] ?>, 35%, 50%); }
							.main .message.in            .content        { background:               hsl(<?php echo $currentContact['number'] ?>, 75%, 91%); }
							.main .message.out           .content        { background:               hsl(<?php echo             $me['number'] ?>, 35%, 91%); }
							.main .message.in            .content:after  { border-color: transparent hsl(<?php echo $currentContact['number'] ?>, 75%, 91%); }
							.main .message.out           .content:after  { border-color: transparent hsl(<?php echo             $me['number'] ?>, 35%, 91%); }
							.main .message.highlight.in  .content        { border-color:             hsl(<?php echo $currentContact['number'] ?>, 36%, 78%); }
							.main .message.highlight.out .content        { border-color:             hsl(<?php echo             $me['number'] ?>, 23%, 78%); }
							.main .message.highlight.in  .content:before { border-color: transparent hsl(<?php echo $currentContact['number'] ?>, 36%, 78%); }
							.main .message.highlight.out .content:before { border-color: transparent hsl(<?php echo             $me['number'] ?>, 23%, 78%); }
							
						</style>

						<?php
						$lastday = '';
						$i = 0;
						foreach($messages as $m) {
							$m = $view->preprocessMessage($m);
							if ($m['day'] != $lastday)
								echo '<div class="hline"><span>'.$m['day'].'</span></div>'; $lastday = $m['day'];
							echo '<div class="message '.$m['direction'].($view->isHighlight($i) ? ' highlight' : '').'">';
								echo '<div class="date" title="'.$m['dateLong'].'">'.$m['dateShort'].'</div>';
								echo '<div class="initial" title="'.$m['name'].'">'.$m['initial'].'</div>';
								echo '<div class="content">'.htmlspecialchars($m['message']).'</div>';
							echo '</div>';
							echo '<div class="clearfix"></div>';
							$i++;
						}
						?>

						<div class="text-center" id="bottom">
							<ul class="pagination">
								<?php echo $pages ?>
							</ul>
						</div>

						<h2><?php echo $currentContact['name'] ?></h2>
						<h3><?php echo implode('&bullet;', $currentContact['phones']) ?></h3>

						<?php if (!empty($popup)) { ?>
							<div class="alert alert-info" role="alert"><?php echo nl2br($popup); ?></div>
						<?php } ?>
					</div>
				</div>
			</div>
			
			<script type="text/javascript">
			window.onload = function () {
				if (location.hash == "")
					location.hash = "#bottom";
			}
			</script>

		</body>
</html>