# Single sign-on on second factor authentication
Middleware can be configured to allow SSO on 2FA. The institution configuration and the service provider configuration has been updated.

This document describes how this feature can be installed and configured.

## Installation
Run the `20221102143350` Doctrine Migration to prepare the Middleware and Gateway projections for the new feature.

The Gateway projection was added to allow Gateway to quickly decide if it should enable/disable the feature without having to consult the Middleware via the API.

### Middleware institution configuration
The `management/institution-configuration` API endpoint can be used to enable the SSO on 2FA feature for the institutions
Use the `sso_on_2fa` boolean to configure this feature.

### Middleware Service Provider configuration
Secondly, you can configure the SP's known to StepUp with the ability to allow the SSO feature. This allows for a more fine grained configuration setup. Where certain SP's are excluded from SSO on 2FA tokens.
The `management/configuration` API endpoint can be used to configure this for the service providers.

The `allow_sso_on_2fa` and `set_sso_cookie_on_2fa` boolean config options can be configure per SP. See this example below 

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
