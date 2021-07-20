<?php

function array_find_item(&$array, $key, $value) {
    foreach ($array as $item) {
        if ($item[$key] == $value) {
            return $item;
        }
    }
}