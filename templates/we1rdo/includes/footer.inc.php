	<script>
	$(function() {
		$( "#slider-filesize" ).slider({
			range: true,
			min: 0,
			max: 375809638400,
			step: 1048576,
			values: [ <?php echo (isset($minFilesize)) ? $minFilesize : "0"; ?>, <?php echo (isset($maxFilesize)) ? $maxFilesize : "375809638400"; ?> ],
			slide: function( event, ui ) {
				$( "#min-filesize" ).val( "filesize:>:" + ui.values[ 0 ] );
				$( "#max-filesize" ).val( "filesize:<:" + ui.values[ 1 ] );
				$( "#human-filesize" ).text( "Tussen " + format_size( ui.values[ 0 ] ) + " en " + format_size( ui.values[ 1 ] ) );
			}
		});
		$( "#min-filesize" ).val( "filesize:>:" + $( "#slider-filesize" ).slider( "values", 0 ) );
		$( "#max-filesize" ).val( "filesize:<:" + $( "#slider-filesize" ).slider( "values", 1 ) );
		$( "#human-filesize" ).text( "Tussen " + format_size( $( "#slider-filesize" ).slider( "values", 0 ) ) + " en " + format_size( $( "#slider-filesize" ).slider( "values", 1 ) ) );
	});
	</script>	

	</div>
	</body>
</html>