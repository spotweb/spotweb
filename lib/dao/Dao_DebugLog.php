<?php

interface Dao_DebugLog {
    function add($lvl, $microtime, $msg);
    function expire();
} # Dao_DebugLog