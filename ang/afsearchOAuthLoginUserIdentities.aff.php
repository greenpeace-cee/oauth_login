<?php
use CRM_OauthLogin_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('OAuth Login User Identities'),
  'icon' => 'fa-link',
  'server_route' => 'civicrm/admin/oauth/user-identities',
  'permission' => [
    'cms:administer users',
  ],
];
