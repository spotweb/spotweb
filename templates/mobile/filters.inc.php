<?php
$setpath = $tplHelper->makeBaseUrl('path');
$sortType = $currentSession['user']['prefs']['defaultsortfield'];
?>
<div data-role="page" id="search"> 

	<div data-role="header" data-fullscreen="true">
            <h1><?php echo _('Search'); ?><?php require __DIR__.'/getusername.inc.php'; ?></h1>
            <?php $active = 'search'; require __DIR__.'/includes/navbar.inc.php'; ?>
    </div>

    <div data-role="content">
	    <div data-role="fieldcontain" >
		    <form id="filterform" action="<?php echo $setpath; ?>index.php?page=search#spots" method="get" data-ajax="false">
			    <fieldset data-role="controlgroup" data-type="horizontal" data-role="fieldcontain">
					<input type="radio" id="radio-choice-1" name="sortby" value="" <?php echo $sortType == '' ? 'checked="checked"' : '' ?>>
					<label for="radio-choice-1"><?php echo _('Relevance'); ?></label> 
					<input type="radio" id="radio-choice-2"  name="sortby" value="stamp" <?php echo $sortType == 'stamp' ? 'checked="checked"' : '' ?>>
					<label for="radio-choice-2"><?php echo _('Date'); ?></label> 
                 </fieldset>
				<fieldset data-role="controlgroup" data-type="horizontal" data-role="fieldcontain">
					<input type="radio" name="search[type]" value="Titel" id="radio-choice-1b" checked="checked" />
					<label for="radio-choice-1b">Title</label>
					<input type="radio"  name="search[type]" value="Poster" id="radio-choice-2b" />
					<label for="radio-choice-2b">Poster</label>
					<input type="radio" name="search[type]" value="Tag" id="radio-choice-3b"  />
					<label for="radio-choice-3b">Tag</label>
				</fieldset>
		        <input type="search" type="text" name="search[text]" value="" />
				<input type="submit" value="<?php echo _('Search'); ?>" data-icon="search" data-inline="true">
    	    </form>
	    </div> <!-- fieldcontain -->
    </div>  <!-- content -->

</div> <!-- page -->

<div data-role="page" id="filters"> 
	<div data-role="header">
	    <h1>Filters<?php require __DIR__.'/getusername.inc.php'; ?></h1>
        <?php $active = 'filters'; require __DIR__.'/includes/navbar.inc.php'; ?>
		
		<div data-role="navbar">
		<br>
		    <ul>				
			    <li><a href="#Image"><img src="templates/mobile/icons/film.png">Image</a></li>
			    <li><a href="#Sounds"><img src="templates/mobile/icons/music.png">Sounds</a></li>
			    <li><a href="#Games"><img src="templates/mobile/icons/controller.png">Games</a></li>
                <li><a href="#Apps"><img src="templates/mobile/icons/application.png">Apps</a></li>
		    </ul>
	    </div><!-- /navbar -->

    </div>
	
</div>

<div data-role="page" id="Image"> 
	<div data-role="header">
	    <h1>Image<?php require __DIR__.'/getusername.inc.php'; ?></h1>
		<?php $active = 'filters'; require __DIR__.'/includes/navbar.inc.php'; ?>
		
		<div data-role="navbar">
		<br>
		    <ul>				
			    <li><a href="#Image"><img src="templates/mobile/icons/film.png">Image</a></li>
			    <li><a href="#Sounds"><img src="templates/mobile/icons/music.png">Sounds</a></li>
			    <li><a href="#Games"><img src="templates/mobile/icons/controller.png">Games</a></li>
                <li><a href="#Apps"><img src="templates/mobile/icons/application.png">Apps</a></li>
		    </ul>
	    </div><!-- /navbar -->
		
<div data-role="content">
    <ul data-role="listview" data-theme="d" data-dividertheme="b">
	<br>	
    <?php
        function processImage($tplHelper, $count_newspots, $filterList, $defaultSortField)
        {
            $selfUrl = $tplHelper->makeSelfUrl('path');
            foreach ($filterList as $filter) {
                $imageFilter = $tplHelper->getPageUrl('index').'&amp;search[tree]='.$filter['tree'];
                if (!empty($filter['valuelist'])) {
                    foreach ($filter['valuelist'] as $value) {
                        $imageFilter .= '&amp;search[value][]='.$value;
                    } // foreach
                } // if
                if (!empty($filter['sorton'])) {
                    $imageFilter .= '&amp;sortby='.$filter['sorton'].'&amp;sortdir='.$filter['sortorder'];
                } else {
                    $sortType = $defaultSortField;
                } // if

                // escape the filter values
                $filter['title'] = htmlentities($filter['title'], ENT_NOQUOTES, 'UTF-8');
                $filter['icon'] = htmlentities($filter['icon'], ENT_NOQUOTES, 'UTF-8');

                // Output HTML
                if (strpos($filter['tree'], 'cat0') !== false) {
                    if (strpos($filter['title'], 'Image') !== false) {
                        $filter['title'] = str_replace('Image', 'All Image', $filter['title']);
                    }

                    echo '<li>';
                    echo '<a href="'.$imageFilter.'#spots" rel="external"><img src="templates/mobile/icons/'.$filter['icon'].'.png" class="ui-li-icon"/>'.$filter['title'].'</a>';
                    processImage($tplHelper, $count_newspots, $filter['children'], $defaultSortField);
                    echo '</li>';
                }
            } // foreach
        } // processFilters

        processImage($tplHelper, false, $filters, $currentSession['user']['prefs']['defaultsortfield']);
    ?>
    </ul>
    </div>
	</div>
</div>

<div data-role="page" id="Sounds"> 
	<div data-role="header">
	    <h1>Sounds<?php require __DIR__.'/getusername.inc.php'; ?></h1>
		<?php $active = 'filters'; require __DIR__.'/includes/navbar.inc.php'; ?>
		
		<div data-role="navbar">
		<br>
		    <ul>				
			    <li><a href="#Image"><img src="templates/mobile/icons/film.png">Image</a></li>
			    <li><a href="#Sounds"><img src="templates/mobile/icons/music.png">Sounds</a></li>
			    <li><a href="#Games"><img src="templates/mobile/icons/controller.png">Games</a></li>
                <li><a href="#Apps"><img src="templates/mobile/icons/application.png">Apps</a></li>
		    </ul>
	    </div><!-- /navbar -->
		
<div data-role="content">
    <ul data-role="listview" data-theme="d" data-dividertheme="b">
	<br>	
    <?php
        function processSounds($tplHelper, $count_newspots, $filterList, $defaultSortField)
        {
            $selfUrl = $tplHelper->makeSelfUrl('path');

            foreach ($filterList as $filter) {
                $soundsFilter = $tplHelper->getPageUrl('index').'&amp;search[tree]='.$filter['tree'];
                if (!empty($filter['valuelist'])) {
                    foreach ($filter['valuelist'] as $value) {
                        $soundsFilter .= '&amp;search[value][]='.$value;
                    } // foreach
                } // if
                if (!empty($filter['sorton'])) {
                    $soundsFilter .= '&amp;sortby='.$filter['sorton'].'&amp;sortdir='.$filter['sortorder'];
                } else {
                    $sortType = $defaultSortField;
                } // if

                // escape the filter values
                $filter['title'] = htmlentities($filter['title'], ENT_NOQUOTES, 'UTF-8');
                $filter['icon'] = htmlentities($filter['icon'], ENT_NOQUOTES, 'UTF-8');

                // Output HTML
                if (strpos($filter['tree'], 'cat1') !== false) {
                    if (strpos($filter['title'], 'Sounds') !== false) {
                        $filter['title'] = str_replace('Sounds', 'All Sounds', $filter['title']);
                    }

                    echo '<li>';
                    echo '<a href="'.$soundsFilter.'#spots" rel="external"><img src="templates/mobile/icons/'.$filter['icon'].'.png" class="ui-li-icon"/>'.$filter['title'].'</a>';
                    processSounds($tplHelper, $count_newspots, $filter['children'], $defaultSortField);
                    echo '</li>';
                }
            } // foreach
        } // processFilters

        processSounds($tplHelper, false, $filters, $currentSession['user']['prefs']['defaultsortfield']);
    ?>
    </ul>
    </div>
	</div>
</div>

<div data-role="page" id="Games"> 
	<div data-role="header">
	    <h1>Games<?php require __DIR__.'/getusername.inc.php'; ?></h1>
		<?php $active = 'filters'; require __DIR__.'/includes/navbar.inc.php'; ?>
		
		<div data-role="navbar">
		<br>
		    <ul>				
			    <li><a href="#Image"><img src="templates/mobile/icons/film.png">Image</a></li>
			    <li><a href="#Sounds"><img src="templates/mobile/icons/music.png">Sounds</a></li>
			    <li><a href="#Games"><img src="templates/mobile/icons/controller.png">Games</a></li>
                <li><a href="#Apps"><img src="templates/mobile/icons/application.png">Apps</a></li>
		    </ul>
	    </div><!-- /navbar -->
		
<div data-role="content">
    <ul data-role="listview" data-theme="d" data-dividertheme="b">
	<br>	
    <?php
        function processGames($tplHelper, $count_newspots, $filterList, $defaultSortField)
        {
            $selfUrl = $tplHelper->makeSelfUrl('path');

            foreach ($filterList as $filter) {
                $gamesFilter = $tplHelper->getPageUrl('index').'&amp;search[tree]='.$filter['tree'];
                if (!empty($filter['valuelist'])) {
                    foreach ($filter['valuelist'] as $value) {
                        $gamesFilter .= '&amp;search[value][]='.$value;
                    } // foreach
                } // if
                if (!empty($filter['sorton'])) {
                    $gamesFilter .= '&amp;sortby='.$filter['sorton'].'&amp;sortdir='.$filter['sortorder'];
                } else {
                    $sortType = $defaultSortField;
                } // if

                // escape the filter values
                $filter['title'] = htmlentities($filter['title'], ENT_NOQUOTES, 'UTF-8');
                $filter['icon'] = htmlentities($filter['icon'], ENT_NOQUOTES, 'UTF-8');

                // Output HTML
                if (strpos($filter['tree'], 'cat2') !== false) {
                    if (strpos($filter['title'], 'Games') !== false) {
                        $filter['title'] = str_replace('Games', 'All Games', $filter['title']);
                    }

                    echo '<li>';
                    echo '<a href="'.$gamesFilter.'#spots" rel="external"><img src="templates/mobile/icons/'.$filter['icon'].'.png" class="ui-li-icon"/>'.$filter['title'].'</a>';
                    processGames($tplHelper, $count_newspots, $filter['children'], $defaultSortField);
                    echo '</li>';
                }
            } // foreach
        } // processFilters

        processGames($tplHelper, false, $filters, $currentSession['user']['prefs']['defaultsortfield']);
    ?>
    </ul>
    </div>
	</div>
</div>

<div data-role="page" id="Apps"> 
	<div data-role="header">
	    <h1>Apps<?php require __DIR__.'/getusername.inc.php'; ?></h1>
        <?php $active = 'filters'; require __DIR__.'/includes/navbar.inc.php'; ?>    
        		
		<div data-role="navbar">
		<br>
		    <ul>				
			    <li><a href="#Image"><img src="templates/mobile/icons/film.png">Image</a></li>
			    <li><a href="#Sounds"><img src="templates/mobile/icons/music.png">Sounds</a></li>
			    <li><a href="#Games"><img src="templates/mobile/icons/controller.png">Games</a></li>
                <li><a href="#Apps"><img src="templates/mobile/icons/application.png">Apps</a></li>
		    </ul>
	    </div><!-- /navbar -->
		
<div data-role="content">
    <ul data-role="listview" data-theme="d" data-dividertheme="b">
	<br>	
    <?php
        function processApps($tplHelper, $count_newspots, $filterList, $defaultSortField)
        {
            $selfUrl = $tplHelper->makeSelfUrl('path');

            foreach ($filterList as $filter) {
                $appsFilter = $tplHelper->getPageUrl('index').'&amp;search[tree]='.$filter['tree'];
                if (!empty($filter['valuelist'])) {
                    foreach ($filter['valuelist'] as $value) {
                        $appsFilter .= '&amp;search[value][]='.$value;
                    } // foreach
                } // if
                if (!empty($filter['sorton'])) {
                    $appsFilter .= '&amp;sortby='.$filter['sorton'].'&amp;sortdir='.$filter['sortorder'];
                } else {
                    $sortType = $defaultSortField;
                } // if

                // escape the filter values
                $filter['title'] = htmlentities($filter['title'], ENT_NOQUOTES, 'UTF-8');
                $filter['icon'] = htmlentities($filter['icon'], ENT_NOQUOTES, 'UTF-8');

                // Output HTML
                if (strpos($filter['tree'], 'cat3') !== false) {
                    if (strpos($filter['title'], 'Applications') !== false) {
                        $filter['title'] = str_replace('Applications', 'All Applications', $filter['title']);
                    }

                    echo '<li>';
                    echo '<a href="'.$appsFilter.'#spots" rel="external"><img src="templates/mobile/icons/'.$filter['icon'].'.png" class="ui-li-icon"/>'.$filter['title'].'</a>';
                    processApps($tplHelper, $count_newspots, $filter['children'], $defaultSortField);
                    echo '</li>';
                }
            } // foreach
        } // processFilters

        processApps($tplHelper, false, $filters, $currentSession['user']['prefs']['defaultsortfield']);
    ?>
    </ul>
	</div>
	</div>
</div>
