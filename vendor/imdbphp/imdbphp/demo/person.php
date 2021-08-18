<?php
 #############################################################################
 # IMDBPHP                              (c) Giorgos Giagas & Itzchak Rehberg #
 # written by Giorgos Giagas                                                 #
 # extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
 # http://www.izzysoft.de/                                                   #
 # ------------------------------------------------------------------------- #
 # This program is free software; you can redistribute and/or modify it      #
 # under the terms of the GNU General Public License (see doc/LICENSE)       #
 #############################################################################

require __DIR__ . "/../bootstrap.php";

if (isset ($_GET["mid"]) && preg_match('/^[0-9]+$/',$_GET["mid"])) {
  $config = new \Imdb\Config();
  $config->language = 'en-US,en';
  $person = new \Imdb\Person($_GET["mid"],$config);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title><?php echo $person->name() ?> - IMDbPHP</title>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php # Name ?>
    <h2 class="text-center"><?php echo $person->name() ?></h2>
      <?php # Photo ?>
      <div class="photo mb-10 text-center">
        <?php
          if (($photo_url = $person->photo_localurl() ) != FALSE) {
            echo '<img src="'.$photo_url.'" alt="Cover">';
          } else {
            echo "No photo available";
          }
        ?>
      </div>
      
      <?php
      # Birthday
      $birthday = $person->born();
      if (!empty($birthday)) {
      ?>
      <div class="text-center mb-10">
        <?php echo $person->name() ?><br><b>&#9788;</b><?php echo $birthday["day"] . ' ' . $birthday["month"] . ' ' . $birthday["year"]; ?>
        <?php if (!empty($birthday["place"])) { ?>
          <br>in <?php echo $birthday["place"] ?>
        <?php } ?>
      </div>
      <?php } ?>
      
      <?php
      # Death
      $death = $person->died();
      if (!empty($death)) {
      ?>
      <div class="text-center mb-10">
        <b>&#8224;</b><?php echo $death["day"] . ' ' . $death["month"] . ' ' . $death["year"]; ?>
        <?php if (!empty($death["place"])) { ?>
          <br>in <?php echo $death["place"] ?>
        <?php } ?>
        <?php if (!empty($death["cause"])) { ?>
          <br><?php echo $death["cause"] ?>
        <?php } ?>
      </div>
      <?php } ?>
      
      <table class="table">
        <tr>
          <th colspan="2" class="move-container">
            Person Details
            <span class="move-right pr-10">Source: [<a href="<?php echo $person->main_url() ?>">IMDb</a>]</span>
          </th>
        </tr>
        
        <?php
        # Birthname
        $bn = $person->birthname();
        if (!empty($bn)) {
        ?>
        <tr>
          <td class="mw-120"><b>Birth Name:</b></td>
          <td><?php echo $bn ?></td>
        </tr>
        <?php } ?>
        
        <?php
        # Nickname
        $nicks = $person->nickname();
        if (!empty($nicks)) {
        ?>
        <tr>
          <td class="mw-120"><b>Nicknames:</b></td>
          <td><?php echo implode(', ',$nicks) ?></td>
        </tr>
        <?php } ?>
        
        <?php
        # Body Height
        $bh = $person->height();
        if (!empty($bh)) {
        ?>
        <tr>
          <td class="mw-120"><b>Body Height:</b></td>
          <td><?php echo $bh["metric"] ?></td>
        </tr>
        <?php } ?>
        
        <?php
        # Spouse(s)
        $sp = $person->spouse();
        if (!empty($sp)) {
        ?>
        <tr>
          <td><b>Spouse(s):</b></td>
          <td>
            <table>
              <tr>
                <th>Spouse</th>
                <th>Period</th>
                <th>Comment</th>
              </tr>
              <?php foreach ( $sp as $spouse) { ?>
                <tr>
                  <td><a href="?mid=<?php echo $spouse["imdb"] ?>"><?php echo $spouse["name"] ?></a></td>
                  <?php if (empty($spouse["from"])) { ?>
                  <td>&nbsp;</td>
                  <?php } else { ?>
                  <td>
                    <?php echo $spouse["from"]["day"] . '-' . $spouse["from"]["month"] . '-' . $spouse["from"]["year"]; ?>
                    <?php echo !empty($spouse["to"]) ? '-' . $spouse["to"]["day"] . '-' . $spouse["to"]["month"] . '-' . $spouse["to"]["year"] : ''; ?>
                  </td>
                  <?php } ?>
                  <?php if (empty($spouse["comment"]) && empty($spouse["children"])) { ?>
                  <td>&nbsp;</td>
                  <?php } else { ?>
                  <td>
                  <?php if (empty($spouse["comment"]) && !empty($spouse["children"])) {
                    echo "Kids: ".$spouse["children"];
                  } elseif (empty($spouse["children"]) && !empty($spouse["comment"])) {
                    echo $spouse["comment"];
                  } else {
                    echo $spouse["comment"]."; Kids: ".$spouse["children"];
                  }
                  ?>
                  </td>
                  <?php } ?>
                </tr>
              <?php } ?>
            </table>
          </td>
        </tr>
        <?php } ?>
        
        <?php
        # MiniBio
        $bio = $person->bio();
        if (!empty($bio)) {
          if (count($bio)<2) $idx = 0; else $idx = 1;
          $minibio = $bio[$idx]["desc"];
          $minibio = preg_replace('/https\:\/\/'.str_replace(".","\.",$person->imdbsite).'\/name\/nm(\d{7,8})(\?ref_=nmbio_mbio)?/','?mid=\\1',$minibio);
          $minibio = preg_replace('/https\:\/\/'.str_replace(".","\.",$person->imdbsite).'\/title\/tt(\d{7,8})(\?ref_=nmbio_mbio)?/','movie.php?mid=\\1',$minibio);
        ?>
        <tr>
          <td><b>Mini Bio:</b></td>
          <td>
            <?php echo $minibio ?>
            <br>(Written by: <?php echo $bio[$idx]['author']['name'] ?>)
          </td>
        </tr>
        <?php } ?>
        
        <?php
        # Some Trivia (Personal Quotes work the same)
        $trivia = $person->trivia();
        $tc     = count($trivia);
        if ($tc > 0) {
        ?>
        <tr>
          <td><b>Trivia:</b></td>
          <td>
            There are <?php echo $tc ?> trivia records. Some examples:
            <ul>
              <?php 
                for($i=0;$i<5;++$i) {
                if (empty($trivia[$i])) break;
              ?>
              <li>
                <?php
                $t = $trivia[$i];
                $t = preg_replace('/https\:\/\/'.str_replace(".","\.",$person->imdbsite).'\/name\/nm(\d{7,8})(\?ref_=nmbio_trv_\d)?/','?mid=\\1',$t);
                $t = preg_replace('/https\:\/\/'.str_replace(".","\.",$person->imdbsite).'\/title\/tt(\d{7,8})(\?ref_=nmbio_trv_\d)?/','movie.php?mid=\\1',$t);
                echo $t;
                ?>
              </li>
              <?php } ?>
            </ul>
          </td>
        </tr>
        <?php } ?>
        
        <?php
        # Trademarks
        $tm = $person->trademark();
        if (!empty($tm)) {
        ?>
        <tr>
          <td><b>Trademarks:</b></td>
          <td>
            <ul>
              <?php foreach($tm as $trade) { ?>
              <li><?php echo $trade ?></li>
              <?php } ?>
            </ul>
          </td>
        </tr>
        <?php } ?>
        
        <?php
        # Salary
        $sal = $person->salary();
        if (!empty($sal)) {
        ?>
        <tr>
          <td><b>Salary:</b></td>
          <td>
            <table>
              <tr>
                <th>Movie</th>
                <th>Salary</th>
              </tr>
              <?php foreach ( $sal as $salary) { ?>
                <tr>
                  <td>
                    <?php if (!empty($salary["movie"]["imdb"])) {?>
                      <a href="movie.php?mid=<?php echo $salary["movie"]["imdb"] ?>"><?php $salary["movie"]["name"] ?></a>
                    <?php } else {
                      echo preg_replace('/\/title\/tt(\d{7,8})(\?ref_=nmbio_sal_\d)?/','movie.php?mid=\\1',$salary["movie"]["name"]);
                    } ?>
                    <?php if (!empty($salary["movie"]["year"])) {
                      echo ' (' . $salary["movie"]["year"] . ')';
                    } ?>
                  </td>
                  <td>
                    <?php echo $salary["salary"] ?>
                  </td>
                </tr>
              <?php } ?>
            </table>
          </td>
        </tr>
        <?php } ?>
        
        <?php
        # This also works for all the other filmographies:
        $ff = array("producer","director","actor","self");
        foreach ($ff as $var) {
          $fdt = "movies_$var";
          $filmo = $person->$fdt();
          $flname = ucfirst($var)."s Filmography";
          if (!empty($filmo)) { ?>
          <tr>
            <td><b><?php echo $flname ?></b></td>
            <td>
              <table>
                <tr>
                  <th>Movie</th>
                  <th>Character</th>
                </tr>
                <?php foreach ($filmo as $film) { ?>
                <tr>
                  <td><a href="movie.php?mid=<?php echo $film["mid"] ?>"><?php echo $film["name"] ?></a>
                  <?php if (!empty($film["year"])) {
                    echo ' (' . $film["year"] . ')';
                  }
                  ?>
                  </td>
                  <td>
                  <?php if (empty($film["chname"])) {
                    echo '&nbsp;';
                  } else {
                    if (empty($film["chid"])) {
                      echo $film["chname"];
                    } else { ?>
                      <a href="https://<?php echo $person->imdbsite ?>/character/ch<?php echo $film["chid"] ?>/"><?php echo $film["chname"] ?></a>
                    <?php
                    }
                  }
                  ?>
                  </td>
                </tr>
                <?php } ?>
              </table>
            </td>
          </tr>
        <?php
          }
        }
        ?>
        
        <?php
        # Publications about this person
        $books = $person->pubprints();
        if (!empty($books)) {
        ?>
        <tr>
          <td><b>Publications:</b></td>
          <td>
            <table>
              <tr>
                <th>Author</th>
                <th>Title</th>
                <th>Year</th>
                <th>ISBN</th>
              </tr>
              <?php foreach ( $books as $book) { ?>
                <tr>
                  <td><?php echo $book["author"] ?></td>
                  <td><?php echo $book["title"] ?></td>
                  <td><?php echo $book["year"] ?></td>
                  <td>
                  <?php if (!empty($books[$i]["url"])) { ?>
                    <a href="<?php echo $book["url"] ?>"><?php echo $book["isbn"] ?></a>
                  <?php } elseif (!empty($books[$i]["isbn"])) {
                    echo $book["isbn"];
                  } else {
                    echo '&nbsp;';
                  } ?>
                  </td>
                </tr>
              <?php } ?>
            </table>
          </td>
        </tr>
        <?php } ?>
        
       <?php
        # Biographical movies
        $pm = $person->pubmovies();
        if (!empty($pm)) {
        ?>
        <tr>
          <td><b>Biographical movies:</b></td>
          <td>
            <table>
              <tr>
                <th>Movie</th>
                <th>Year</th>
              </tr>
              <?php foreach ( $pm as $movie) { ?>
                <tr>
                  <td><a href="movie.php?mid=<?php echo $movie["imdb"] ?>"><?php echo $movie["name"] ?></a></td>
                  <td><?php echo !empty($movie["year"]) ? $movie["year"] : '&nbsp;' ?></td>
                </tr>
              <?php } ?>
            </table>
          </td>
        </tr>
        <?php } ?>
        
       <?php
        # Interviews (articles, pictorials, and magcovers work the same)
        $interviews = $person->interviews();
        if (!empty($interviews)) {
        ?>
        <tr>
          <td><b>Interviews:</b></td>
          <td>
            <table>
              <tr>
                <th>Interview</th>
                <th>Details</th>
                <th>Year</th>
                <th>Author</th>
              </tr>
              <?php foreach ( $interviews as $interview) { ?>
                <tr>
                  <td>
                    <?php if (empty($interview['inturl'])) {
                      echo $interview["name"];
                    } else { ?>
                      <a href="https://<?php echo $person->imdbsite . $interview["inturl"] ?>"><?php echo $interview["name"] ?></a>
                    <?php } ?>
                  </td>
                  <td><?php echo $interview["details"]; ?></td>
                  <td><?php echo $interview["date"]["full"]; ?></td>
                  <td>
                    <?php if (empty($interview["author"])) {
                      echo '&nbsp;';
                    } else {
                      if (empty($interview['auturl'])) {
                        echo $interview["author"];
                      } else { ?>
                        <a href="https://<?php echo $person->imdbsite.$interview["auturl"] ?>"><?php echo $interview["author"] ?></a>
                      <?php 
                      }
                    } ?>
                  </td>
                </tr>
              <?php } ?>
            </table>
          </td>
        </tr>
        <?php } ?>
      </table>
    <p class="text-center"><a href="index.html">Go back</a></p>
  </body>
</html>
<?php
}
