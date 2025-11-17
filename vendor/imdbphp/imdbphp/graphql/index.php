<?php

require_once "vendor/autoload.php";

header("Content-Type: application/json");

$body = file_get_contents("php://input");

writelog($body);

// Override request for schema with a custom response
if (strpos($body, "__schema")) {
    $responseBody = [
        "data" => [
            "__schema" => [
                "mutationType" => [
                    "name" => "Mutation"
                ],
                "queryType" => typeQuery("Query"),
                "types" => iterativelyFetchTypes(["Query", "Mutation"])
            ]
        ]
    ];

    echo json_encode($responseBody, JSON_PRETTY_PRINT);
    return;
}

$res = graphqlRequest($body);
foreach ($res->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

echo $res->getBody();

function graphqlRequest($body)
{
    $client = new \GuzzleHttp\Client();

    return $client->request('POST', 'https://api.graphql.imdb.com', [
        'body' => $body,
        'headers' => [
            'content-type' => 'application/json',
        ]
    ]);
}

function iterativelyFetchTypes(array $seedTypes)
{
    $todo = $seedTypes;
    $done = [];
    $result = [];

    $addToQueue = function ($newType) use (&$todo, &$done) {
        if (!in_array($newType, $done) && !in_array($newType, $todo)) {
            $todo[] = $newType;
        }
    };

    while (count($todo)) {
        $typeName = array_shift($todo);
        $done[] = $typeName;
        $type = typeQuery($typeName);

        $recurseTypeNames = function ($src) use ($addToQueue) {
            if ($src->name != null) {
                $addToQueue($src->name);
            }
            if ($src->ofType != null) {
                if ($src->ofType->name != null) {
                    $addToQueue($src->ofType->name);
                }
                if ($src->ofType->ofType != null) {
                    if ($src->ofType->ofType->name != null) {
                        $addToQueue($src->ofType->ofType->name);
                    }
                    if ($src->ofType->ofType->ofType != null) {
                        if ($src->ofType->ofType->ofType->name != null) {
                            $addToQueue($src->ofType->ofType->ofType->name);
                        }
                    }
                }
            }
        };

        if (is_array($type->fields)) {
            foreach ($type->fields as $field) {
                $recurseTypeNames($field->type);

                if (is_array($field->args)) {
                    foreach ($field->args as $arg) {
                        $recurseTypeNames($arg->type);
                    }
                }
            }
        }

        if (is_array($type->interfaces)) {
            foreach ($type->interfaces as $interface) {
                $recurseTypeNames($interface);
            }
        }

        if (is_array($type->inputFields)) {
            foreach ($type->inputFields as $inputFields) {
                $recurseTypeNames($inputFields->type);
            }
        }

        if (is_array($type->possibleTypes)) {
            foreach ($type->possibleTypes as $possibleTypes) {
                $recurseTypeNames($possibleTypes);
            }
        }

        $result[] = $type;
    }
    return $result;
}

/**
 * @param string $typeName
 * @return \stdClass
 */
function typeQuery($typeName)
{
    $query = <<<EOF
query Type(\$type: String!) {
  __type(name: \$type) {
    ...FullType
  }
}

fragment FullType on __Type {
      kind
      name
      description

      fields(includeDeprecated: true) {
        name
        description
        args {
          ...InputValue
        }
        type {
          ...TypeRef
        }
        isDeprecated
        deprecationReason
      }
      inputFields {
        ...InputValue
      }
      interfaces {
        ...TypeRef
      }
      enumValues(includeDeprecated: true) {
        name
        description
        isDeprecated
        deprecationReason
      }
      possibleTypes {
        ...TypeRef
      }
    }

    fragment InputValue on __InputValue {
      name
      description
      defaultValue
      type { ...TypeRef }
      defaultValue


    }

    fragment TypeRef on __Type {
      kind
      name
      ofType {
        kind
        name
        ofType {
          kind
          name
          ofType {
            kind
            name
            ofType {
              kind
              name
              ofType {
                kind
                name
                ofType {
                  kind
                  name
                  ofType {
                    kind
                    name
                  }
                }
              }
            }
          }
        }
      }
    }
EOF;
    $request = [
        "operationName" => "Type",
        "query" => $query,
        "variables" => [
            "type" => $typeName,
        ]
    ];

    $cacheFileName = "cache/$typeName";
    if (file_exists($cacheFileName)) {
        writelog("Reading $typeName from cache");
        $json = json_decode(file_get_contents($cacheFileName));
    } else {
        writelog("Fetching type $typeName from api");
        $res = graphqlRequest(json_encode($request));
        $rawBody = $res->getBody();
        file_put_contents($cacheFileName, $rawBody);
        $json = json_decode($rawBody);
    }


    return $json->data->__type;
}

function writelog($logLine)
{
    file_put_contents("log.txt", $logLine . "\n", FILE_APPEND);
}
