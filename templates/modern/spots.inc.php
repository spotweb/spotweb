<?php

    $isSpotsOnly = !empty($data['spotsonly']);

    if (!$isSpotsOnly) {
        require_once __DIR__.'/includes/header.inc.php';
        require_once __DIR__.'/includes/filters.inc.php';

        $retrieveUrl = $tplHelper->makeRetrieveUrl();
        if (!empty($retrieveUrl)) {
            $retrieveUrlJson = json_encode($retrieveUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            $retrieveLabelJson = json_encode(_('Retrieve'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    var maint = document.querySelector("ul.maintenancebox");
    if (!maint || maint.querySelector(".retrievespots")) { return; }
    var li = document.createElement("li");
    var a = document.createElement("a");
    a.href = '.$retrieveUrlJson.';
    a.className = "greyButton retrievespots";
    a.textContent = '.$retrieveLabelJson.';
    a.onclick = function(ev) { ev.preventDefault(); retrieveSpots(this); };
    li.appendChild(a);
    var insertAfterInfo = maint.querySelector("li.info");
    if (insertAfterInfo && insertAfterInfo.nextSibling) {
        maint.insertBefore(li, insertAfterInfo.nextSibling);
    } else if (insertAfterInfo) {
        maint.appendChild(li);
    } else {
        maint.insertBefore(li, maint.firstChild);
    }
});
</script>';
        }
    }

    SpotTiming::start('tpl:spotsinc-modern-cards');

    $viewMode = isset($_GET['view']) ? $_GET['view'] : (isset($_COOKIE['spotweb_view']) ? $_COOKIE['spotweb_view'] : 'cards');
    if ($viewMode === 'table') {
        $data['spotsonly'] = true;
        require __DIR__.'/spots.table.inc.php';
        if (!$isSpotsOnly) {
            require_once __DIR__.'/includes/footer.inc.php';
        }
        SpotTiming::stop('tpl:spotsinc-modern-cards');

        return;
    }

    $can_use_watchlist = $tplHelper->allowed(SpotSecurity::spotsec_keep_own_watchlist, '');
    $pref_keep_watchlist = !empty($currentSession['user']['prefs']['keep_watchlist']);
    $show_watchlist_button = ($pref_keep_watchlist && $can_use_watchlist);
    $show_comments = ($settings->get('retrieve_comments') && $tplHelper->allowed(SpotSecurity::spotsec_view_comments, ''));
    $show_filesize = $currentSession['user']['prefs']['show_filesize'];
    $show_nzb_button = ($tplHelper->allowed(SpotSecurity::spotsec_retrieve_nzb, '') && $currentSession['user']['prefs']['show_nzbbutton']);
    $show_multinzb_checkbox = ($tplHelper->allowed(SpotSecurity::spotsec_retrieve_nzb, '') && $currentSession['user']['prefs']['show_multinzb']);
    $noResults = (count($spots) == 0);

    if (!$noResults) {
        echo "\t\t\t<div class=\"spots\">".PHP_EOL;

        if (!$pref_keep_watchlist && $can_use_watchlist) {
            echo '<div class="cardsNotice watchlistNotice">';
            echo '<strong>'._('Tip').':</strong> '._('Enable "Keep watchlist" in your user preferences to use favorites.');
            echo '</div>';
        }

        echo '<div class="cardsGrid">';

        foreach ($spots as $spot) {
            // Format spot header without touching core files
            $spot = $tplHelper->formatSpotHeader($spot);
            $spot['formatname'] = SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']);
            if (!isset($spot['image'])) {
                $spot['image'] = '';
            }
            if (!isset($spot['poster'])) {
                $spot['poster'] = '';
            }
            $categoryCss = $tplHelper->cat2CssClass($spot); // spotcatX
            $dateTitleText = $tplHelper->formatDate($spot['stamp'], 'force_spotlist');
            $spotUrl = $tplHelper->makeSpotUrl($spot);
            $nzbUrl = $tplHelper->makeNzbUrl($spot);
            $thumbUrl = $tplHelper->makeImageUrl($spot, 175, 130);
            $newSpotClass = $tplHelper->isSpotNew($spot) ? ' new' : '';

            echo '<div class="spotCard '.$categoryCss.$newSpotClass.'">';
            // Optional thumbnail
            if (!empty($thumbUrl)) {
                echo '  <div class="thumb"><a onclick="openSpot(this,\''.$spotUrl.'\')" class="spotlink" href="'.$spotUrl.'">'
                    .'<img loading="lazy" src="'.$thumbUrl.'" alt="" /></a></div>';
            }
            $badge = isset($spot['formatname']) ? $spot['formatname'] : SpotCategories::Cat2ShortDesc($spot['category'], $spot['subcata']);
            echo '  <div class="topRow">'
                .'    <span class="badge">'.htmlspecialchars((string) $badge).'</span>'
                .'    <div class="title"><a onclick="openSpot(this,\''.$spotUrl.'\')" class="spotlink" href="'.$spotUrl.'">'.htmlspecialchars((string) $spot['title']).'</a></div>'
                .'  </div>';

            echo '  <div class="meta">';
            echo '    <span class="genre"><a href="'.$spot['subcaturl'].'">'.htmlspecialchars($spot['catdesc']).'</a></span>';
            echo '    <span class="poster"><a href="'.$spot['posterurl'].'">'.htmlspecialchars($spot['poster']).'</a></span>';
            echo '    <span class="date" title="'.$dateTitleText.'">'.$tplHelper->formatDate($spot['stamp'], 'spotlist').'</span>';
            if ($show_filesize) {
                echo '    <span class="size">'.$tplHelper->format_size($spot['filesize']).'</span>';
            }
            echo '  </div>';

            echo '  <div class="actions">';
            // NZB button (only for newer spots like original template)
            if ($show_nzb_button && $spot['stamp'] > 1290578400) {
                echo '    <a class="nzb'.($spot['hasbeendownloaded'] ? ' downloaded' : '').'" href="'.$nzbUrl.'">NZB</a>';
            }
            // SABnzbd button
            if (!empty($spot['sabnzbdurl'])) {
                $sabTitle = $spot['hasbeendownloaded'] ? _('Add NZB to SABnzbd queue (you already downloaded this spot) (s)') : _('Add NZB to SABnzbd queue (s)');
                $sabClass = 'sab_'.$spot['id'].' sabnzbd-button'.($spot['hasbeendownloaded'] ? ' succes' : '');
                echo '    <a onclick="downloadSabnzbd(\''.$spot['id'].'\',\''.$spot['sabnzbdurl'].'\',\''.$spot['nzbhandlertype'].'\')" class="'.$sabClass.'" title="'.$sabTitle.'"> </a>';
            } else {
                $prefsUrl = $tplHelper->makeEditUserPrefsUrl($currentSession['user']['userid']);
                $configureTitle = _('Configure NZB handling before sending this spot to a download client');
                $configureTitle = htmlspecialchars($configureTitle, ENT_QUOTES);
                echo '    <a href="'.$prefsUrl.'" onclick="return promptNzbHandlerSetup(event, this.href)" class="sabnzbd-button unconfigured" title="'.$configureTitle.'" aria-label="'.$configureTitle.'"> </a>';
            }
            echo '  </div>';

            echo '  <div class="footerRow">';
            if ($show_comments) {
                $commentLabel = (int) $spot['commentcount'];
                echo '    <a class="comments spotlink" onclick="return openSpot(this,\''.$spotUrl.'#comments\')" href="'.$spotUrl.'#comments">'.$commentLabel.' '._('comments').'</a>';
            }
            if ($show_watchlist_button) {
                $addOnclick = "toggleWatchSpot('".$spot['messageid']."','add',".$spot['id'].')';
                $removeOnclick = "toggleWatchSpot('".$spot['messageid']."','remove',".$spot['id'].')';
                $displayAdd = ($spot['isbeingwatched']) ? ' style=\'display:none;\'' : '';
                $displayRemove = ($spot['isbeingwatched']) ? '' : ' style=\'display:none;\'';
                echo '    <span class="watch">';
                echo '      <a class="add watchadd_'.$spot['id'].'"'.$displayAdd.' onclick="'.$addOnclick.'" title="'._('Add to watchlist (w)').'">&#9734;</a>';
                echo '      <a class="remove watchremove_'.$spot['id'].'"'.$displayRemove.' onclick="'.$removeOnclick.'" title="'._('Delete from watchlist (w)').'">&#9733;</a>';
                echo '    </span>';
            }
            if ($show_multinzb_checkbox && $spot['stamp'] > 1290578400) {
                $multispotid = htmlspecialchars($spot['messageid']);
                echo '    <span class="multi"><input onclick="multinzb()" type="checkbox" name="'.htmlspecialchars('messageid[]').'" value="'.$multispotid.'"></span>';
            }
            echo '  </div>';

            echo '</div>';
        }

        echo '</div>'; // cardsGrid

        // Pagination footer (simple prev/next)
        if ($prevPage >= 0 || $nextPage > 0) {
            echo '<div class="cardsPager">';
            if ($prevPage >= 0) {
                echo '<a class="prev" href="?direction=prev&amp;pagenr='.$prevPage.$tplHelper->convertSortToQueryParams().$tplHelper->convertFilterToQueryParams().'">'._('Previous').'</a>';
            }
            if ($nextPage > 0) {
                echo '<a class="next" href="?direction=next&amp;pagenr='.$nextPage.$tplHelper->convertSortToQueryParams().$tplHelper->convertFilterToQueryParams().'">'._('Next').'</a>';
            }
            echo '</div>';
        }

        // Hidden inputs used by existing JS
        echo '<input type="hidden" id="perPage" value="'.(int) $currentSession['user']['prefs']['perpage'].'">';
        echo '<input type="hidden" id="nextPage" value="'.(int) $nextPage.'">';
        echo '<input type="hidden" id="getURL" value="'.$tplHelper->convertSortToQueryParams().$tplHelper->convertFilterToQueryParams().'">';

        echo "</div>\n<div class=\"clear\"></div>";
    } else {
        $retrieveUrl = '';
        if (
            ($currentSession['user']['userid'] > SPOTWEB_ADMIN_USERID) &&
            $tplHelper->allowed(SpotSecurity::spotsec_retrieve_spots, '') &&
            $tplHelper->allowed(SpotSecurity::spotsec_consume_api, '')
        ) {
            $retrieveUrl = $tplHelper->makeRetrieveUrl();
        } // if

        echo "<div class='spots'>";
        echo "  <div class='cardsEmptyState'>";
        echo '      <h2>'._('No results found').'</h2>';
        echo '      <p>'._('There are no spots visible yet. Usually this means your database is still empty or the current filter returns no results.').'</p>';
        echo "      <div class='emptyActions'>";
        if (!empty($retrieveUrl)) {
            echo "      <a href='".$retrieveUrl."' onclick='return retrieveSpots(this)' class='greyButton retrievespots'>"._('Retrieve').'</a>';
        } // if
        echo "          <a href='".$tplHelper->makeBaseUrl('path')."' class='greyButton'>"._('Back to overview').'</a>';
        if ($tplHelper->allowed(SpotSecurity::spotsec_edit_settings, '') || $tplHelper->allowed(SpotSecurity::spotsec_view_spotweb_updates, '')) {
            echo "      <a href='?page=editsettings' class='greyButton'>"._('Settings').'</a>';
        } // if
        if ($tplHelper->allowed(SpotSecurity::spotsec_edit_own_userprefs, '')) {
            echo "      <a href='".$tplHelper->makeEditUserPrefsUrl($currentSession['user']['userid'])."' class='greyButton'>"._('Change preferences').'</a>';
        } // if
        echo '      </div>';
        echo '  </div>';
        echo '</div>';
    }

    if (!$isSpotsOnly) {
        require_once __DIR__.'/includes/footer.inc.php';
    }

    SpotTiming::stop('tpl:spotsinc-modern-cards');
