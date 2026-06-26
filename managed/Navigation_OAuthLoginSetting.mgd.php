<?php
use CRM_OAuthLogin_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_OAuthLoginSetting',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('OAuth Login Settings'),
        'name' => 'oauthLoginSetting',
        'url' => 'civicrm/admin/setting/oauth_login',
        'icon' => 'crm-i fa-key',
        'permission' => [
          'administer CiviCRM',
        ],
        'permission_operator' => 'AND',
        'parent_id.name' => 'Users and Permissions',
        'weight' => 99,
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
