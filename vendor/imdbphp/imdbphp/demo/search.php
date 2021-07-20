<?php
 #############################################################################
 # IMDBPHP                              (c) Giorgos Giagas & Itzchak Rehberg #
 # written by Giorgos Giagas                                                 #
 # extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
 # http://www.izzysoft.de/                                                   #
 # ------------------------------------------------------------------------- #
 # This program is free software; you can redistribute and/or modify it      #
 # under the terms of the GNU General Public License (see doc/LICENSE)       #
 # ------------------------------------------------------------------------- #
 # Search for $name and display results                                      #
 #############################################################################

require __DIR__ . "/../bootstrap.php";
require "inc.php";

# If MID has been explicitly given, we don't need to search:
if (!empty($_GET["mid"]) && preg_match('/^(tt|nm|)([0-9]+)$/',$_GET["mid"],$matches)) {
  $searchtype = !empty($matches[1]) ? $matches[1] : $_GET["searchtype"];
  switch($searchtype) {
    case "nm" : header("Location: person.php?mid=".$matches[2]); break;
    default   : header("Location: movie.php?mid=".$matches[2]); break;
  }
  return;
}

# If we have no MID and no NAME, go back to search page
if (empty($_GET["name"])) {
  header("Location: index.html");
  return;
}

# Still here? Then we need to search for the movie:
if ($_GET['searchtype'] === 'nm') {
  $headname = "Person";
  $search = new \Imdb\PersonSearch();
  $results = $search->search($_GET["name"]);
} else {
  $headname = "Movie";
  $search = new \Imdb\TitleSearch();
  if ($_GET["searchtype"] == "episode") {
    $results = $search->search($_GET["name"], array(\Imdb\TitleSearch::TV_EPISODE));
  } else {
    $results = $search->search($_GET["name"]);
  }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Performing search for "<?php echo esc($_GET["name"]) ?>" - IMDbPHP</title>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <h2 class="text-center">Search results for <span><?php echo esc($_GET["name"]) ?></span>:</h2>
    <table class="table">
      <tr><th><?php echo $headname ?> Details</th><th>IMDb</th></tr>
      <?php foreach ($results as $res):
        if ($_GET['searchtype'] === 'nm'):
          $details = $res->getSearchDetails();
          $hint = '';
          if (!empty($details)) {
            $hint = " (".$details["role"]." in <a href='movie.php?mid=".$details["mid"]."'>".$details["moviename"]."</a> (".$details["year"]."))";
          } ?>
          <tr>
            <td><a href="person.php?mid=<?php echo $res->imdbid() ?>"><?php echo $res->name() ?></a><?php echo $hint ?></td>
            <td><a href="<?php echo $res->main_url() ?>">IMDb</a></td>
          </tr>
        <?php else: ?>
          <tr>
            <td><a href="movie.php?mid=<?php echo $res->imdbid() ?>"><?php echo $res->title() ?> (<?php echo $res->year() ?>) (<?php echo $res->movietype() ?>)</a></td>
            <td><a href="<?php echo $res->main_url() ?>">IMDb</a></td>
          </tr>
        <?php endif ?>
      <?php endforeach ?>
    </table>
	<p class="text-center"><a href="index.html">Go back</a></p>
  </body>
</html>
