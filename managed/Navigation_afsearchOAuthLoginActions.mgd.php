<?php
use CRM_OauthLogin_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_afsearchOAuthLoginActions',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('OAuth Login Actions'),
        'name' => 'afsearchOAuthLoginActions',
        'url' => 'civicrm/admin/oauth/oauthloginactions',
        'icon' => 'crm-i fa-user-gear',
        'permission' => [
          'administer CiviCRM system',
        ],
        'permission_operator' => 'AND',
        'parent_id.name' => 'Users and Permissions',
        'weight' => 15,
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
