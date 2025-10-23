<?php
use CRM_OauthLogin_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_afsearchOAuthLoginUserIdentities',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('OAuth Login User Identities'),
        'name' => 'afsearchOAuthLoginUserIdentities',
        'url' => 'civicrm/admin/oauth/user-identities',
        'icon' => 'crm-i fa-link',
        'permission' => [
          'cms:administer users',
        ],
        'permission_operator' => 'AND',
        'parent_id.name' => 'Users and Permissions',
        'weight' => 50,
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
