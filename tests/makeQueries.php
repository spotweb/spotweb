<?php

$expr = <<<'EOF'
<?php
        $parameters = array(
            ':tmdbid' => array($trailer->getTmdbId(), PDO::PARAM_INT),
            ':name' => array($trailer->getName(), PDO::PARAM_STR),
            ':size' => array($trailer->getSize(), PDO::PARAM_INT),
            ':source' => array($trailer->getSource(), PDO::PARAM_STR),
            ':type' => array($trailer->getType(), PDO::PARAM_STR),
        );
EOF;

$tokenize = token_get_all($expr);
//var_dump($tokenize);

function seekToken($tokenize, $token, $i) {
    // get to the i
    while (($i < count($tokenize)) && ($tokenize[$i][0] !== $token)) {
        $i++;
    } // while

    if ($i > count($tokenize)) {
        throw new Exception("Not found");
    } // if

    return $i;
} // while

/*
 * Now we only find a list of arrays and their meaning, so lets fix it up
 */
$i = 0;
$i = seekToken($tokenize, T_ARRAY, $i);

while ($i < count($tokenize)) {
    /*
     * Get the child array
     */
    $i = seekToken($tokenize, T_CONSTANT_ENCAPSED_STRING, $i);
    if ($i >= count($tokenize)) break;

    $dbName = $tokenize[$i][1];

    // get the definition of this sql placeholder
    $i = seekToken($tokenize, T_ARRAY, $i);
    if ($i >= count($tokenize)) break;

    // get the identifier
    $i = seekToken($tokenize, T_VARIABLE, $i);
    if ($i >= count($tokenize)) break;

    $identifier = $tokenize[$i][1];
    $i = seekToken($tokenize, T_STRING, $i);
    if ($i >= count($tokenize)) break;
    $identifier .= '->' . $tokenize[$i][1];

    $recordList[] = array('id' => trim($dbName, "':"));
//    echo 'Value = ' . $dbName . ' => ' . $identifier . PHP_EOL;

    $i++;
}


$sql = '$this->_conn->modify' . "('UPDATE xxx SET ";
foreach($recordList as $token) {
    $sql .= PHP_EOL . '                             ' . $token['id'] . ' = :' . $token['id'] . ', ';
}
$sql = substr($sql, 0, -2);
$sql .= "')" . ', $parameters);';
echo $sql. PHP_EOL;
echo PHP_EOL;

$sql = '$this->_conn->modify' . "('INSERT INTO xxx(";
foreach($recordList as $token) {
    $sql .= $token['id'] . ', ';
}
$sql = substr($sql, 0, -2);
$sql .= ') VALUES (';
foreach($recordList as $token) {
    $sql .= ':' . $token['id'] . ', ';
}
$sql = substr($sql, 0, -2);
$sql .= ")'" . ', $parameters);';
echo $sql. PHP_EOL;