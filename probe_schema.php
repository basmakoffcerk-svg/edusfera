<?php

require __DIR__.'/vendor/autoload.php';

use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;

// Simulate OpenAPI 3.1 schema with type arrays + enum with null + minItems.
$schemaArray = [
    'type' => 'object',
    'required' => ['user_id', 'scopes'],
    'properties' => [
        'user_id' => ['type' => 'integer'],
        'role' => [
            'type' => ['string', 'null'],
            'enum' => ['admin', 'tutor', 'student', 'parent', null],
        ],
        'scopes' => ['type' => 'array', 'items' => ['type' => 'string']],
        'request_id' => ['type' => ['string', 'null']],
    ],
];

$schema = json_decode(json_encode($schemaArray));

// Case 1: role string
$data1 = json_decode(json_encode(['user_id' => 42, 'role' => 'tutor', 'scopes' => ['lessons:read'], 'request_id' => 'abc']));
$v1 = new Validator;
$v1->validate($data1, $schema, Constraint::CHECK_MODE_NORMAL);
echo 'case1 valid='.($v1->isValid() ? 'YES' : 'NO')."\n";
if (! $v1->isValid()) {
    var_export($v1->getErrors());
}

// Case 2: role null + request_id null
$data2 = json_decode(json_encode(['user_id' => 1, 'role' => null, 'scopes' => [], 'request_id' => null]));
$v2 = new Validator;
$v2->validate($data2, $schema, Constraint::CHECK_MODE_NORMAL);
echo 'case2 valid='.($v2->isValid() ? 'YES' : 'NO')."\n";
if (! $v2->isValid()) {
    var_export($v2->getErrors());
}

// Case 3: wrong type for user_id (string) should be INVALID
$data3 = json_decode(json_encode(['user_id' => 'x', 'role' => 'tutor', 'scopes' => []]));
$v3 = new Validator;
$v3->validate($data3, $schema, Constraint::CHECK_MODE_NORMAL);
echo 'case3 valid(should be NO)='.($v3->isValid() ? 'YES' : 'NO')."\n";
