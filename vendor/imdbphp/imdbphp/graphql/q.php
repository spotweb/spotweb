<?php

use GraphQL\SchemaObject\RootQueryObject;
use GraphQL\SchemaObject\RootTitleArgumentsObject;
use GraphQL\SchemaObject\TitleReleaseDatesArgumentsObject;

include "vendor/autoload.php";

$client = new \GraphQL\Client(
    'https://api.graphql.imdb.com/'
);

$rootObject = new RootQueryObject();

$node = $rootObject->selectTitle((new RootTitleArgumentsObject())->setId("tt0120737"))
    ->selectReleaseDates((new TitleReleaseDatesArgumentsObject())->setFirst(9999))
        ->selectEdges()
            ->selectNode();

$node->selectDay()
    ->selectMonth()
    ->selectYear()
    ->selectAttributes()
        ->selectText();
$node->selectCountry()
    ->selectText()
    ->selectId();


$results = $client->runQuery($rootObject->getQuery());

print_r($results->getData());
