<?php
function esc($str) {
  return htmlspecialchars($str,ENT_QUOTES,'UTF-8');
}
