<html>
	<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<style type='text/css'>
			html {
				overflow:auto;
				height:100%;
			}

			div.container {
				position:center; 
				font:11px Arial, Helvetica, sans-serif;}

			div.permdenied {
				border: 1px solid red;
				background-color: #fef1fc;
				color: #cd0a0a;

				font-size: 0.9em;
				
				margin-top: 4px;
				margin-left: auto;
				margin-right: auto;
				
				border-top-left-radius: 4px;
				border-top-right-radius: 4px;
				border-bottom-right-radius: 4px;
				border-bottom-left-radius: 4px;
			} 
			
			div.permdenied p {
				text-align: center;
				margin-left: 10px;
				margin-right: 10px;
			} 
			
			div.login {
				text-align: center;
			} 

			ul.formerrors {
				padding: 10px;
				color: #cd0a0a;
				list-style: none;
			}

			<?php echo $settings->get('customcss'); ?>
		</style>
	</head>

	<body>
		<div class='container'>
