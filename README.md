# OAuth Login

Provide single sign-on for CiviCRM using OAuth/OpenID Connect.

> [!WARNING]
> This extension is in an early stage and should not be considered secure. DO NOT USE THIS IN PRODUCTION.

This is an [extension for CiviCRM](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/), licensed under [AGPL-3.0](LICENSE.txt).

## Getting Started

The extension relies on the `oauth-client` CiviCRM core extension to provide
connectivity with different OAuth identity providers. The extension ships with
a few existing providers.

It is possible to add your own OAuth providers as described in the [OAuth Reference](https://docs.civicrm.org/dev/en/latest/framework/oauth/#tutorial).
Make sure to add the "Login" tag to your OAuth provider definition to let the
extension know it is intended to be used for single sign-on. The extension
should theoretically work with any providers that conform to the OpenID Connect
specification.

TODO: example json

To start using a provider, set up an OAuth client as described in the
[OAuth system administrator guide](https://docs.civicrm.org/sysadmin/en/latest/setup/oauth/#civicrm-client).
You will need to obtain a client ID and client secret with the appropriate
from your identity provider. The steps required depend on your provider.
`oauth_login` currently supports the following providers:

| Provider  | Description                                                          | How-To |
|-----------|----------------------------------------------------------------------| ------ |
| Google    | Allow users in your Google Workspace environment to login to CiviCRM | https://support.google.com/a/answer/12032922?hl=en&src=supportwidget0&authuser=0#:~:text=Set%20up%20SSO%20with%20OIDC |

Once the OAuth Client has been configured, a new "Sign in with [Name of Provider] (OAuth Client ID)"
should appear on the login screen.

Users are currently connected to remote identities using the following logic:

1. If the subject provided by the OAuth service (which is an unique ID assigned to the user by the identity provider) is already linked to a CiviCRM user, log in that user.
2. Otherwise, if an active user with a matching email exists, log in that user.

`oauth_login` keeps track of these associations via the
`UserOAuthIdentity` entity, which can be inspected and modified via
Administer > Users and Permissions > OAuth Login User Identities.

`oauth_login` currently does not support onboarding new users.

## Known Issues

- Cryptographic verification of ID tokens is missing
- Certain assertions required by the OIDC spec are not yet implemented
- The extension should provide a way to configure how claims by the identity
provider are mapped to the user and contact entities in CiviCRM
- Allow user onboarding (and related config)
- The extension could provide a way to map CiviCRM roles to user groups provided by the provider
- Potentially define hooks that can be used by other extensions to alter
mapping behaviour, apply stricter restrictions, etc.
