<?php

$config = new PhpCsFixer\Config();

return $config
    ->setRules(array(
        '@PER-CS1.0' => true,
        'visibility_required' => array(
            'elements' => array(
                'method',
                'property'
            )
        ),
    ))
    ->setFinder(PhpCsFixer\Finder::create()
        ->exclude(array(
            'demo',
        ))
        ->in(__DIR__)
    );
