This folder contains copies of the required jQuery libraries for use with 
Dynatree 1.0

Currently using 

- jquery.js:
  jQuery 1.4.4

- jquery-ui.custom.js:
  jQuery UI 1.8.7 with 'UI Core' and 'Interactions' modules.
  ('Widgets' and 'Effects' are not contained in this custom build)
  
- jquery.min.js and jquery-ui.custom.min.js:
  Minified versions of the above

Current versions are always available at 
    http://docs.jquery.com/Downloading_jQuery
and
    http://jqueryui.com/download

Include the required libs like this:
    <script src='../jquery/jquery.js' type='text/javascript'></script>
    <script src='../jquery/jquery-ui.custom.js' type='text/javascript'></script>
    <script src='../jquery/jquery.cookie.js' type='text/javascript'></script>

    
Alternatively the current libs may be we included from CDNs, for example
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.js" type="text/javascript"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.js" type="text/javascript"></script>
    <script src='../jquery/jquery.cookie.js' type='text/javascript'></script>
