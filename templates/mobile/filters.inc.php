<?php 	$setpath = $tplHelper->makeBaseUrl(); ?>
<div data-role="page" id="search"> 
	<div data-role="header" data-backbtn="false">
	<h1>Zoek</h1>

	<div data-role="navbar">
		<ul>
			<li><a href="#spots" data-icon="grid" >Spots</a></li>
			<li><a href="#search" class="ui-btn-active" data-icon="search">Zoek</a></li>
			<li><a href="#filters" data-icon="star">Filters</a></li>
		</ul>
	</div><!-- /navbar -->

</div>
<div data-role="content">
	<div data-role="fieldcontain" >
		<form id="filterform" action="<?php echo $setpath;?>index.php?page=search#spots" method="get" data-ajax="false">
			<fieldset data-role="controlgroup" data-type="horizontal" data-role="fieldcontain">
	         		<input type="radio" name="search[type]" value="Titel" id="radio-choice-1" checked="checked" />
	         		<label for="radio-choice-1">Titel</label>
	
		         	<input type="radio"  name="search[type]" value="Poster" id="radio-choice-2" />
	    	     	<label for="radio-choice-2">Poster</label>
	
	        	 	<input type="radio" name="search[type]" value="Tag" id="radio-choice-3"  />
	         		<label for="radio-choice-3">Tag</label>
			</fieldset>
		    <input type="search" type="text" name="search[text]" value="" />
	</form>
	</div>
 </div>
 </div>
<div data-role="page" id="filters"> 
	<div data-role="header" data-backbtn="false">
	<h1>Spotweb</h1>

	<div data-role="navbar">
		<ul>
			<li><a href="#spots" data-icon="grid" >Spots</a></li>
			<li><a href="#search" data-icon="search">Zoek</a></li>
			<li><a href="#filters" data-icon="star" class="ui-btn-active" >Filters</a></li>

		</ul>
	</div><!-- /navbar -->

</div>
<div data-role="content">

<ul data-role="listview" data-theme="c" data-dividertheme="b">
<?php
    foreach($filters as $filter) {
?>
			<li> 
				<img src="<?php echo $filter[1]; ?>" class="ui-li-icon" />
				<h3><a href="<?php echo $setpath;?>index.php?search[tree]=<?php echo $filter[2];?>#spots" rel="external"><?php echo $filter[0]; ?></a></h3>
			</li>
<?php
        if (!empty($filter[4])) {
            foreach($filter[4] as $subFilter) {
?>
            <li>
               <img src="<?php echo $subFilter[1]; ?>" class="ui-li-icon" />
               <h3><a href="<?php echo $setpath;?>index.php?search[tree]=<?php echo $subFilter[2];?>#spots" rel="external"> - <?php echo $subFilter[0]; ?></a></h3>
             </li>
<?php
            } # foreach 

        } # is_array
    } # foreach
?>
</ul>
</div>
</div>

