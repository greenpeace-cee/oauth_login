<?php
use CRM_OauthLogin_ExtensionUtil as E;

return [
  'name' => 'UserOAuthIdentity',
  'table' => 'civicrm_user_oauth_identity',
  'class' => 'CRM_OauthLogin_DAO_UserOAuthIdentity',
  'getInfo' => fn() => [
    'title' => E::ts('UserOAuthIdentity'),
    'title_plural' => E::ts('UserOAuthIdentities'),
    'description' => E::ts('User mapping to OAuth/OIDC Identities'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique UserOAuthIdentity ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'user_id' => [
      'title' => E::ts('User ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to User'),
      'input_attrs' => [
        'label' => E::ts('User'),
      ],
      'entity_reference' => [
        'entity' => 'User',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'client_id' => [
      'title' => E::ts('Client ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('Client ID'),
      'entity_reference' => [
        'entity' => 'OAuthClient',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'subject' => [
      'title' => E::ts('Subject'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('OIDC Subject Identifier'),
      'input_attrs' => [
        'maxlength' => 255,
      ],
    ],
  ],
  'getIndices' => fn() => [
    'index_client_id_subject' => [
      'fields' => [
        'client_id' => TRUE,
        'subject' => TRUE,
      ],
      'unique' => TRUE,
    ],
    'index_user_id_client_id' => [
      'fields' => [
        'user_id' => TRUE,
        'client_id' => TRUE,
      ],
      'unique' => TRUE,
    ],
  ],
  'getPaths' => fn() => [],
];
