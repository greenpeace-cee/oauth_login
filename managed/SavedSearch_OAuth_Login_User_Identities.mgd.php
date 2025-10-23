<?php
use CRM_OauthLogin_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_OAuth_Login_User_Identities',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'OAuth_Login_User_Identities',
        'label' => E::ts('OAuth Login User Identities'),
        'api_entity' => 'UserOAuthIdentity',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'user_id',
            'UserOAuthIdentity_User_user_id_01.username',
            'subject',
            'UserOAuthIdentity_OAuthClient_client_id_01.provider:label',
            'UserOAuthIdentity_User_user_id_01.uf_name',
            'UserOAuthIdentity_OAuthClient_client_id_01.id',
            'UserOAuthIdentity_User_user_id_01.is_active',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [
            [
              'User AS UserOAuthIdentity_User_user_id_01',
              'INNER',
              [
                'user_id',
                '=',
                'UserOAuthIdentity_User_user_id_01.id',
              ],
            ],
            [
              'OAuthClient AS UserOAuthIdentity_OAuthClient_client_id_01',
              'INNER',
              [
                'client_id',
                '=',
                'UserOAuthIdentity_OAuthClient_client_id_01.id',
              ],
            ],
          ],
          'having' => [],
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_OAuth_Login_User_Identities_SearchDisplay_OAuth_Login_User_Identities_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'OAuth_Login_User_Identities_Table',
        'label' => E::ts('OAuth Login User Identities Table'),
        'saved_search_id.name' => 'OAuth_Login_User_Identities',
        'type' => 'table',
        'settings' => [
          'description' => E::ts('List of users along with their linked OAuth identities.'),
          'sort' => [
            [
              'UserOAuthIdentity_User_user_id_01.username',
              'ASC',
            ],
          ],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'UserOAuthIdentity_User_user_id_01.username',
              'label' => E::ts('Username'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'UserOAuthIdentity_User_user_id_01.uf_name',
              'label' => E::ts('User Email'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'UserOAuthIdentity_OAuthClient_client_id_01.provider:label',
              'label' => E::ts('OAuth Provider (Client)'),
              'sortable' => TRUE,
              'rewrite' => '[UserOAuthIdentity_OAuthClient_client_id_01.provider:label] ([UserOAuthIdentity_OAuthClient_client_id_01.id])',
            ],
            [
              'type' => 'field',
              'key' => 'subject',
              'label' => E::ts('Subject Identifier'),
              'sortable' => TRUE,
              'title' => E::ts('Unique identifier of the end-user within the identity provider'),
            ],
          ],
          'actions' => ['delete', 'update'],
          'classes' => ['table', 'table-striped'],
          'actions_display_mode' => 'menu',
          'cssRules' => [
            [
              'disabled',
              'UserOAuthIdentity_User_user_id_01.is_active',
              '=',
              FALSE,
            ],
          ],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
