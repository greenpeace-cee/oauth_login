<?php
use CRM_OAuthLogin_ExtensionUtil as E;

return [
  'name' => 'OAuthLoginAction',
  'table' => 'civicrm_oauth_login_action',
  'class' => 'CRM_OAuthLogin_DAO_OAuthLoginAction',
  'getInfo' => fn() => [
    'title' => E::ts('OAuthLoginAction'),
    'title_plural' => E::ts('OAuthLoginActions'),
    'description' => E::ts('After an OAuth login those actions will be executed'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique OAuthLoginAction ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'client_id' => [
      'title' => E::ts('OAuth Client ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to OAuth Client'),
      'required' => FALSE,
      'entity_reference' => [
        'entity' => 'OAuthClient',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'is_active' => [
      'title' => E::ts('Is Active'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => E::ts('Is this action enabled or disabled/cancelled?'),
      'default' => TRUE,
      'input_attrs' => [
        'label' => E::ts('Enabled'),
      ],
    ],
    'weight' => [
      'title' => E::ts('Order'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Controls action execution order'),
      'default' => 1,
    ],
    'label' => [
      'title' => E::ts('Label'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'default' => '',
      'description' => E::ts('User friendly description of the action'),
      'input_attrs' => [
        'maxlength' => 255,
      ],
    ],
    'type' => [
      'title' => E::ts('Mapping Type'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => E::ts('Mapping Type'),
      'input_attrs' => [
        'maxlength' => 255,
      ],
      'pseudoconstant' => [
         'callback' => [\CRM_OAuthLogin_BAO_OAuthLoginAction::class, 'getTypes'],
       ]
    ],
    'configuration' => [
      'title' => E::ts('Configuration'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Configuration of the mapping type'),
      'required' => FALSE,
      'serialize' => \CRM_Core_DAO::SERIALIZE_JSON,
    ],
  ],
  'getIndices' => fn() => [],
  'getPaths' => fn() => [],
];
