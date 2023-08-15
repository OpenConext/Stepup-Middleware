# Single sign-on on second factor authentication
Whether SSO on the second authentication factor is allowed is controlled though the middleware configuration, the actual SSO is handled by the Steup-Gateway. This document describes the SSO configuration in the Stepup-Middleware only. Please refer to the Stepup-Gateway documentation for more information on the SSO feature itself: https://github.com/OpenConext/Stepup-Gateway/blob/develop/docs/SsoOn2Fa.md 

## Requirements
SSO requires Stepup-Middleware 5.1.0 (or above) and Stepup-Gateway 4.2.0 (or above). This middleware includes Doctrine Migration `20221102143350` that creates/updates the Middleware and Gateway projections with the SSO specific configuration options.

## Configuration
All the SSO on 2FA options in the middleware are optional. If no configuration is present, SSO on 2FA is disabled for all institutions and service providers and no SSO cookie is set. For SSO to be active both the institution and the service provider must be configured to allow SSO on 2FA. 

### Middleware institution configuration
The `management/institution-configuration` API endpoint is used to enable the SSO on 2FA feature for the institutions. Set `sso_on_2fa` to true in order to enable SSO for an institution. E.g.:

```json
{
  "example.com": {
    "use_ra_locations": false,
    "show_raa_contact_information": true,
    "verify_email": false,
    "allowed_second_factors": [],
    "number_of_tokens_per_identity": 3,
    "self_vet": true,
    "allow_self_asserted_tokens": true,
    "sso_on_2fa": true
  }
}
```

### Middleware Service Provider configuration
The `management/configuration` API endpoint is used to configure to enable the SSO on 2FA feature for the service providers.

The `allow_sso_on_2fa` and `set_sso_cookie_on_2fa` boolean config options are set per SP. See the example below:

```json
{
    "gateway": {
        "service_providers": [
          {
            "entity_id": "https://ss-dev.stepup.coin.surf.net/app_dev.php/authentication/metadata",
            "public_key": "MIIEJTCCAw2gAwIBAgIJANug+o++<<SNIP FOR BREVITY>>KLV04DqzALXGj+LVmxtDvuxqC042apoIDQV",
            "acs": [
              "https://ss-dev.stepup.coin.surf.net/app_dev.php/authentication/consume-assertion"
            ],
            "loa": {
              "__default__": "https://gw-dev.stepup.coin.surf.net/authentication/loa1"
            },
            "second_factor_only": false,
            "second_factor_only_nameid_patterns": [],
            "assertion_encryption_enabled": false,
            "blacklisted_encryption_algorithms": [],
            
            "allow_sso_on_2fa": true,
            "set_sso_cookie_on_2fa": true
          }
        ]
    }
}
```

`allow_sso_on_2fa` is used to enable or disable SSO on the second factor for that particular SP. When `allow_sso_on_2fa` is set to false (default) for an SP, SSO on 2FA is disabled for all authentications for that SP. If set to true, SSO on 2FA is enabled for all authentications for that SP and SSO will be attempted for all authentications for that SP. SSO is only performed when a valid SSO cookie is present and all the other necessary conditions are met, see the Stepup-Gateway documentation for more information: https://github.com/OpenConext/Stepup-Gateway/blob/develop/docs/SsoOn2Fa.md

`set_sso_cookie_on_2fa` is used to enable or disable the creation of the SSO cookie for authentications to that particular SP. When `set_sso_cookie_on_2fa` is set to false (default) for an SP, no SSO cookie is set. If set to true, an SSO cookie is set after a successful second factor authentication to that SP. Note that the SSO cookie itself is shared between all authentications, it is tied to a specific user and second factor, not to an SP.

## Design
A Gateway projection was added to allow Gateway to quickly lookup if SSO is enabled/disabled for a particular institution without having to consult the Middleware via the API.

