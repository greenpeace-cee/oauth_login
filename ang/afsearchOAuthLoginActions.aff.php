<?php
use CRM_OauthLogin_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('OAuth Login Actions'),
  'icon' => 'fa-user-gear',
  'server_route' => 'civicrm/admin/oauth/oauthloginactions',
  'permission' => [
    'administer CiviCRM system',
  ],
];
