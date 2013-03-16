<?php
	$path = $_SERVER["PATH_INFO"];
	
	if($path=='/messages') {
		$time = $_GET['time'];
		$db = new SQLite3('messages.db');
		$res = $db->query(sprintf('select * from messages where time>%d order by time asc', $time));
		$result = array();
		while ($row = $res->fetchArray()) { 		
			$row = array(
				'message' => utf8_encode($row['message']),
				'time' => $row['time'],
				'grhash' => $row['grhash']
			);
			$result[] = $row;
		}
		$db->close();
		echo json_encode($result);
		exit;
	}
	
	// http://prog.hu/tudastar/28455/UTF8+kodolas-PHP+dekodolas-MySQL+tarolas.html
	function myutf8_decode($s) {
		$s = str_replace("\xC5\x91","\xC3\xB5",$s); // õ
		$s = str_replace("\xC5\xB1","\xC3\xBB",$s); // û
         
		$s = str_replace("\xC5\x90","\xC3\x95",$s); // Õ
		$s = str_replace("\xC5\xB0","\xC3\x9B",$s); // Û
         
		return utf8_decode($s);
	}

	if($path=='/add_message') {
		$data = json_decode(file_get_contents('php://input'));
		$db = new SQLite3('messages.db');
		$db->exec(sprintf("insert into messages (`message`, `time`, `grhash`) values ('%s', '%d', '%s')", 
			SQLite3::escapeString(myutf8_decode($data->message)), 
			time(),
			md5( strtolower( trim( $data->email ) ) )
		));
		$db->close();
		exit;
	}
	
?>
<!DOCTYPE html>
<html lang="en"ng-app="ui">
  <head>
    <meta charset="utf-8">
    <title>PHP AngularJS messageboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Simple (only one file) PHP newsfeed example built with AngularJS, AngularUI, and Twitter Bootstrap">
    <meta name="author" content="The Bojda (https://github.com/TheBojda)">

    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/bootstrap-responsive.css" rel="stylesheet">

  <style type="text/css">
	body {
		padding-top: 60px;
	}
      
	.item {
		transition: all 0.5s ease;
		-o-transition: all 0.5s ease;
		-moz-transition: all 0.5s ease;
		-webkit-transition: all 0.5s ease;
		max-height: 500px; 
		overflow: hidden
	}

	.ui-animate {
		opacity: 0;
		max-height: 0;
		padding: 0 5px;
	}      
  </style>

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="../assets/js/html5shiv.js"></script>
    <![endif]-->
  </head>
  <body>
  
	<div class="navbar navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container">
				<a class="brand" href="#">PHP + AngularJS messageboard example</a>
			</div>
		</div>
	</div>
  
	<div class="container" ng-controller="MessageBoardCtrl">
		<div class="row span6">
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span10 well well-small">
					<form>
						<input type="text" placeholder="e-mail (only for gravatar)" ng-model="email"/>
						<textarea placeholder="Share something with others ..." rows="5" style="width:90%" ng-model="message"></textarea>
						<button class="btn btn-primary" ng-click="sendMessage()">Share</button>
					</form>
				</div>
			</div>
			<div class="row-fluid item" ng-repeat="item in items" ui-animate>
				<div class="span2"><img src="http://www.gravatar.com/avatar/{{item.grhash}}?s=75" class="img-rounded" width="75px" height="75px"/></div>
				<div class="span10 well well-small">
					<p><strong>Laszlo Fazekas</strong></p>
					<p>{{item.message}}</p>
				</div>
			</div>
		</div>
	</div>
	
	<a href="https://github.com/TheBojda/php-messageboard" target="_blank"><img style="position: absolute; top: 0; right: 0; border: 0; z-index: 2000;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_gray_6d6d6d.png" alt="Fork me on GitHub"></a>
	
	<script src="js/jquery.js"></script>
	<script src="js/angular.js"></script>
	<script src="js/angular-ui.js"></script>
	<script>
		function MessageBoardCtrl($scope, $http, $timeout) {
			$scope.items = [];
			$scope.message = '';
			$scope.email = '';
			$scope.lastTime = 0;
			
			$scope.refreshMessages = function() {
				$http.get('index.php/messages?time=' + $scope.lastTime).success(function(data) {
					for(id in data) {
						item = data[id];
						$scope.items.unshift(item);
						if($scope.lastTime<item.time)
							$scope.lastTime = item.time;
					}
				});
			}
						
			$scope.sendMessage = function() {
				if(!$scope.message)
					return;
				$http.post('index.php/add_message', {message: $scope.message, email: $scope.email}).success(function() {
					$scope.message = '';
					$scope.refreshMessages();
				});
			}
			
			$scope.periodicRefresh = function() {
				$scope.refreshMessages();
				$timeout($scope.periodicRefresh, 5000, true);
			}
			
			$scope.periodicRefresh();
		}
	</script>
  </body>
</html>