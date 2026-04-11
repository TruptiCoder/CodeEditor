<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/codejudge:managequestions' => [
        'riskbitmask'  => RISK_SPAM | RISK_XSS,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
        ],
    ],
    'local/codejudge:submit' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'student' => CAP_ALLOW,
            'user'    => CAP_ALLOW,
        ],
    ],
    'local/codejudge:viewreports' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
        ],
    ],
    'local/codejudge:viewown' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'student' => CAP_ALLOW,
            'user'    => CAP_ALLOW,
        ],
    ],
];
