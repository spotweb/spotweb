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
# Show what we have in the Cache                                            #
#############################################################################

require __DIR__ . "/../bootstrap.php";

use \Imdb\Title;
use \Imdb\Person;
use \Imdb\Config;

$config = new Config();
$results = array();
if (is_dir($config->cachedir)) {
  $files = glob($config->cachedir . '{title.tt*,name.nm*}', GLOB_BRACE);
  foreach ($files as $file) {
    if (preg_match('!^title\.tt(\d{7,8})$!i', basename($file), $match)) {
      $results[] = new Title($match[1]);
    }
    if (preg_match('!^name\.nm(\d{7,8})$!i', basename($file), $match)) {
      $results[] = new Person($match[1]);
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>IMDbPHP Cache Contents</title>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php if (empty($results)): ?>
      <h2 class="text-center">Nothing in cache</h2>
    <?php else: ?>
      <h2 class="text-center">Cache Contents</h2>
      <table class="table">
        <tr>
          <th>Name</th>
          <th>Type</th>
          <th>IMDb</th>
        </tr>
        <?php foreach ($results as $res): ?>
            <?php if (get_class($res) === 'Imdb\Title'): ?>
            <tr>
              <td><?php echo $res->title() ?></td>
              <td><?php echo $res->movietype() ?></td>
              <td class="text-center">
                <a href="movie.php?mid=<?php echo $res->imdbid() ?>">Cache</a> |
                <a href="<?php echo $res->main_url() ?>">IMDb</a>
              </td>
            </tr>
            <?php else: ?>
            <tr>
              <td><?php echo $res->name() ?></td>
              <td>Person</td>
              <td class="text-center">
                <a href="person.php?mid=<?php echo $res->imdbid() ?>">Cache</a> |
                <a href="<?php echo $res->main_url() ?>">IMDb</a>
              </td>
            </tr>
            <?php endif; ?>
        <?php endforeach ?>
      </table>
    <?php endif ?>
    <p class="text-center"><a href="index.html">Go back</a></p>
  </body>
</html>
