# Middleware Configuration

The middleware stores the configuration that is set using the [middleware management API](MiddlewareManagementAPI.md) in the middleware and gateway databases. The gateway is designed to not require access to the middleware and to only need read-only access to the gateway database. The the other components (Self-Service and RA) do have access to the middleware API and do not have database access and use the middleware to get access to the configuration when required.

Configuration is done by making a POST request to an URL of the [middleware management API](MiddlewareManagementAPI.md) with the new configuration as JSON in the request body. There are three configuration APIs that each update a different part of the configuration:
- `http://{middleware.tld/management/configuration` -- the [Middleware configuration API](#using-the-middleware-configuration-api)
- `http://{middleware.tld/management/institution-configuration` -- [Institution configuration API](#using-the-institution-configuration-api)
- `http://{middleware.tld/management/whitelist/replace` -- the [Institution whitelist API](#using-the-whitelist-api)

This section is intended to document the requirements and structure of the above three configuration endpoints.

## Usage examples

[Stepup-Deploy](https://github.com/OpenConext/Stepup-Deploy) contains and installs scripts to for managing the middleware configuration:
- Configuration templates: https://github.com/OpenConext/Stepup-Deploy/tree/develop/environments/template/templates/middleware
- `middleware-push-*` scripts in https://github.com/OpenConext/Stepup-Deploy/tree/develop/roles/stepup-middleware/templates

Addionally Stepup-Deploy contains the `push-mw-*.yml` Ansible playbooks and the [push_config.sh](https://github.com/OpenConext/Stepup-Deploy/blob/develop/scripts/push-config.sh) script for updating the configuration using Ansible.


# Using the Middleware Configuration API

This section describes using the first of the three configuration APIs: The Middleware Configuration. This is the most complex of the three configuratino APIs/

The configuration API is protected by HTTP Basic-Authentication. Example using cURL to set a new middleware configuraton:
```
curl -XPOST -v \
    -u username:password \
    -H "Accept: application/json" \
    -H "Content-type: application/json" \
    -d @new_configuration.json \
    http://middleware.tld/management/configuration
```

## Configuration Structure

The configuration must be a json object with the following keys:
* sraa
* email_templates
* gateway
Each of these keys will be described in detail in a section below. The minimum structure the configuration must have is:
```json
{
    "sraa": [],
    "email_templates": {},
    "gateway": {
        "identity_providers": [],
        "service_providers": []
    }
}
```

## SRAA
### Specification
The Super Registration Authority Administrator (SRAA) is configured by sending a list of NameIDs that should be granted SRAA rights when logged in to the RA application with a sufficient LOA.
```json
"sraa": [
    "Subject NameID of the user as received by the Gateway from the remote IdP",
    "Subject NameID of a different user as received by the Gateway from the remote IdP"
]
```

### Processing
The list of current SRAA's will be deleted and the supplied list of SRAAs will be stored.

### Example
```json
"sraa": [
   "39ba648867aa14a873339bb2a3031791ef319894"
]
```

## Email Templates

### Specification
The `email_templates` key must contain an object.
Each property of this object denotes a specific type of email, the types available are:
* `confirm_email`: **(required)** the email sent when the Registrant is asked prove the possession of his email address.
* `registration_code_with_ras`: **(required)** the email sent when the Registrant has successfully registered a new Second Factor and is invited to visit an RA for institutions not using RA locations.
* `registration_code_with_ra_locations`: **(required)** the email sent when the Registrant has successfully registered a new Second Factor and is invited to visit an RA for institutions using RA locations.
* `second_factor_verification_reminder_with_ras`: **(required)** the reminder email sent when the registrant registered it's token 7 days ago and is invited to visit an RA for institutions not using RA locations.
* `second_factor_verification_reminder_with_ra_locations`: **(required)** the email sent when the registrant registered it's token 7 days ago and is invited to visit an RA for institutions using RA locations.
* `vetted`: **(required)** the email sent when the Registrant has successfully vetted a Second Factor.
* `second_factor_revoked` **(required)**: the email sent when a Second Factor has been revoked.
* `recovery_token_created` **(required)**: the email sent when a recovery token was created.
* `recovery_token_revoked` **(required)**: the email sent when a recovery token has been revoked.

Each email contains an object, where each property corresponds with an IETF language tag (2 letter lower cased language code + underscore + 2 letter upper cased country code, i.e. nl_NL, nl_BE) that may be supported in the application.

```json
"email_templates": {
    "confirm_email": {
        "nl_NL": "Volledige template met een {{ variableName }} variabele in Twig syntax. Kan <b>HTML</b> en
new lines bevatten.",
        "en_GB": "Full template with a {{ variableName }} variable in Twig syntax. May contain <b>HTML</b> and
new lines."
    }
}
```

### Processing
There will only be validation if the required email-template properties are present,
each with at least the default language ("en_GB") template available.
All previous templates will be removed from the database and the new templates will be inserted.

### Template Variables

#### E-mail verification (confirm_email)

Send as part of the user self service registration process when the user must validate their email address by clicking a verification link in the email.

| variable name   | type   | example                                                 |
|-----------------|--------|---------------------------------------------------------|
| commonName      | string | Jan Modaal                                              |
| email           | string | jan@modaal.nl                                           |
| verificationUrl | string | http://self-service.com/verify-email?n=0123456789abcdef |

#### Registration (registration_code_with_ras & second_factor_verification_reminder_with_ras)

Sent after completion of the user self service registration process to inform the user that he/she must visit a registration authority (RA) to get their token vetted. The RA location information is taken from the location information provided by each RA(A)'s in the RA interface. One location for each RA. Used when `use_ra_locations` is `false` in the Institution Configuration of the user's institution. The `show_raa_contact_information` option in the Institution Configuration determines whether RAA contacts will be listed in addition to the RA contacts.

| variable name         | type   | example                   |
|-----------------------|--------|---------------------------|
| commonName            | string | Jan Modaal                |
| email                 | string | jan@modaal.nl             |
| expirationDate        | string | 2017-01-01                |
| ras                   | array  |                           |
| ╰ commonName          | string | Henk Modaal               |
| ╰ location            | string | Moreelsepark, Utrecht     |
| ╰ contactInformation  | string | mail naar info@surfnet.nl |

#### Registration (registration_code_with_ra_locations & second_factor_verification_reminder_with_ra_locations)

Sent after completion of the user self service registration process to inform the user that he/she must visit a registration authority (RA) to get their token vetted. The RA location information is taken from the location information provided by the RAA's of the user's institution in the RA interface. Used when `use_ra_locations` is `true` in the Institution Configuration of the user's institution.

| variable name         | type   | example                   |
|-----------------------|--------|---------------------------|
| commonName            | string | Jan Modaal                |
| email                 | string | jan@modaal.nl             |
| expirationDate        | string | 2017-01-01                |
| raLocations           | array  |                           |
| ╰ name                | string | Servicebalie              |
| ╰ location            | string | Moreelsepark, Utrecht     |
| ╰ contactInformation  | string | mail naar info@surfnet.nl |

#### After vetting (vetted)

Sent when the token of the user has been vetted by an RA.

| name       | type   | example       |
|------------|--------|---------------|
| commonName | string | Jan Modaal    |
| email      | string | jan@modaal.nl |

#### Second factor revocation (second_factor_revoked)

Sent when the token of the user has been revoked by the user or by an RA.

| name           | type    | example                    |
|----------------|---------|----------------------------|
| commonName     | string  | Jan Modaal                 |
| email          | string  | jan@modaal.nl              |
| tokenType      | string  | yubikey                    |
| tokenId        | string  | 123923                     |
| isRevokedByRa  | boolean | true                       |
| selfServiceUrl | string  | http://selfservice.example |

* `` **(required)**: 

#### After vetting (recovery_token_created)

Sent when an identity created a new recovery token.

| name       | type   | example       |
|------------|--------|---------------|
| commonName | string | Jan Modaal    |
| email      | string | jan@modaal.nl |

#### Recovery token revocation (recovery_token_revoked)

Sent when the token of the user has been revoked by the user or by an RA.

| name           | type    | example                    |
|----------------|---------|----------------------------|
| commonName     | string  | Jan Modaal                 |
| email          | string  | jan@modaal.nl              |
| tokenType      | string  | sms                        |
| tokenIdentifier| string  | +31612345678               |
| isRevokedByRa  | boolean | true                       |
| selfServiceUrl | string  | http://selfservice.example |


## Gateway

### Specification
The gateway section contains the configured saml entities for the gateway.
This allows the registration of various IdPs and SPs with their respective configurations.
It must contain an object with the ```identity_providers``` and ```service_providers``` properties.
Both must contain an array as value.

#### Service Providers

Each element in the ```service_providers``` array must be an object and contain the following properties:
* `entity_id` has a string as value that identifies the IdP that is listed as Authenticating Authority in the SAML assertion.
* `public_key` contains the Base64 encoded X.509 certificate with the the public signing key of the SP (i.e. a PEM certificate, but without the PEM "-----BEGIN CERTIFICATE-----" and "-----END CERTIFICATE-----" headers and without whitespace). This is value of the X509Certificate element in the KeyDescriptor of the SAML 2.0 metadata of the SP.
* The `acs` property contains a list of AssertionConsumerService (ACS) Location URLs to which the SAMLResponse may be sent. The Stepup-Gateway always uses the SAML HTTP-POST Binding to send the SAMLResponse the ACS location of the SP. The first ACS location in the `acs` list is the default location. When multiple ACS locations are present the SP can specify the ACS location to use in the AuthnRequest using the `AssertionConsumerServiceURL` attribute. The requested ACS location must match exacly with one of the enties in the `acs` property, otherwise the default location is used. For an SFO SP multiple ACS locations are not supported and the default location is always used. When the SFO SP is an ADFS MFA Plugin the verification is more relaxed, and it is only verified that the requested ACS location starts with the default location. Multiple ACS locations are supported since Stepup-Gateway 2.9.2 (Release 15)
* The `loa` property must contain a hash (object) with at least the key `__default__` with the default required minimum loa for the SP as value. The LoA values, even for SFO, that are used in the configuration are the values that are defined using the `gateway_loa_loa*` parameters in the [gateway configuration](https://github.com/OpenConext/Stepup-Gateway/blob/develop/config/legacy/parameters.yml.dist).
  For specific institutions an alternative minimum LoA can be specified by using the institution identifier (as used in the institution whitelist configuration) as the key, and the required minimum LoA as the value.
* `second_factor_only` boolean determines whether this SP is allowed to use the Second Factor Only (SFO) mode. SFO uses different endpoints and metadata (/second-factor-only/metadata). Using SFO is mutually exclusive with using the normal endpoint (/second-factor-only/metadata).
* `second_factor_only_nameid_patterns` contains a list of patterns (strings that may contain a '*' wildcard character) that are allowed to use the Second Factor Only mode. E.g. the wilcard pattern `urn:collab:person:example.org:*` matches all NameIDs that start with "urn:collab:person:example.org:". Does nothing if `second_factor_only` is not set to true.
* ```assertion_encryption_enabled``` must be a boolean value that allows configuring whether or not the assertion that is sent to the SP should be encrypted.
* ```blacklisted_encryption_algorithms``` contains an array that lists (each as single string-element) algorithms that may not be used for encryption. When left empty, no algorithms are blacklisted. As the gateway currently only allows the " http://www.w3.org/2001/04/xmldsig-more#rsa-sha256" algorithm this option is of little practical use.
* `use_pdp` optional boolean value, defaults to false, the PDP policy decision is enforced when enabled
* `allow_sso_on_2fa` optional boolean value, defaults to false, allow the sp to evaluate the SSO on 2FA cookie (if present) 
* `set_sso_cookie_on_2fa` optional boolean value, defaults to false. Is the SP allowed to set a SSO on 2FA cookie in Gateway?

#### Identity Providers

This array can be empty, in which case no IdP specific configuration is used.

Each element in the `identity_providers` array must be an object and contain the `entity_id` and `loa` properties.
* `entity_id` has a string as value that identifies the IdP that is listed as Authenticating Authority in the SAML assertion.
* The `loa` property must contain a hash (object) with at least the key __default__ with the default required loa as value. Each additional key is used as EntityID of an SP, with the value as the minimum required LoA for that SP that should be required when you log in.
* `use_pdp` optional boolean value, defaults to false, the PDP policy decision is enforced when enabled

Note: This option has not seen any use in practice.

### Processing
Everything will be validated against the requirements listed above. Once the validation passes, the whole configuration that is in the database is removed and the new configuration is inserted. In other words: the existing middleware configuration is overwritten. The institution configuration and whitelist are not affected.Z

### LOA Resolution
It is possible to specify a LoA in 3 places:
  1. The AuthnContextClassRef in the Authentication Request (SAML2 AuthnRequest).
  2. The ```loa``` on the ```service_providers``` configuration.
  3. The ```loa``` on the ```identity_providers``` configuration.

The Gateway will require that the user authenticates with the highest LoA of all of these. I.e. it is possible the raise the LoA, not to lower it.

Second Factor Only (SFO) mode requires that AuthnRequests use LoA aliases in the AuthnRequest. However internally these are immediately translated to their equivalent LoAs. This means that the configuration must **not** use Second Factor Only LoA aliases, only the LoAs defined in the `gateway_loa_loa*` parameters in the [gateway configuration](https://github.com/OpenConext/Stepup-Gateway/blob/develop/config/legacy/parameters.yml.dist).

### Example
```json

"gateway": {
    "identity_providers": [
        {
            "entity_id": "https://example.idp.tld/metadata",
            "loa": {
                "__default__": "https://gateway.tld/assurance/loa2",
                "https://example.sp.tld/metadata": "https://gateway.tld/assurance/loa2"
            }
        }
    ],
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
            "blacklisted_encryption_algorithms": []
        },
        {
            "entity_id": "https://ra-dev.stepup.coin.surf.net/app_dev.php/vetting-procedure/gssf/tiqr/metadata",
            "public_key": "MIIEJTCCAw2gAwIBAgIJANug+o++<<SNIP FOR BREVITY>>KLV04DqzALXGj+LVmxtDvuxqC042apoIDQV",
            "acs": [
                "https://ra-dev.stepup.coin.surf.net/app_dev.php/vetting-procedure/gssf/tiqr/verify"
            ],
            "loa": {
                "__default__": "https://gw-dev.stepup.coin.surf.net/authentication/loa3"
            },
            "second_factor_only": false,
            "second_factor_only_nameid_patterns": [],
            "assertion_encryption_enabled": false,
            "blacklisted_encryption_algorithms": []
        }
    ]
}
```

## A Complete example

```json
{
    "sraa": [
        "39ba648867aa14a873339bb2a3031791ef319894"
    ],
    "email_templates": {
        "confirm_email": {
            "nl_NL": "<p>Beste {{ commonName }},</p>\n\n<p>Bedankt voor het registreren van je token. Klik op onderstaande link om je e-mailadres te bevestigen:</p>\n<p><a href=\"{{ verificationUrl }}\">{{ verificationUrl }}</a></p>\n<p>Is klikken op de link niet mogelijk? Kopieer dan de link en plak deze in de adresbalk van je browser.</p>\n<p>SURFnet</p>",
            "en_GB":"<p>Dear {{ commonName }},</p>\n\n<p>Thank you for registering your token. Please visit this link to verify your email address:</p>\n<p><a href=\"{{ verificationUrl }}\">{{ verificationUrl }}</a></p>\n<p>If you can not click on the URL, please copy the link and paste it in the address bar of your browser.</p>\n<p>SURFnet</p>"
        },
       "registration_code_with_ras": {
           "nl_NL": "<p>Beste {{ commonName }},</p>\n\n<p>Bedankt voor het registreren van je token. Je token is bijna klaar voor gebruik. Ga naar de Service Desk om je token te laten activeren. </p>\n<p>Neem aub het volgende mee:</p>\n<ul>\n    <li>Je token</li>\n    <li>Een geldig legitimatiebewijs (paspoort, rijbewijs of nationale ID-kaart)</li>\n    <li>De registratiecode uit deze e-mail</li>\n</ul>\n\n<p style=\"font-size: 150%; text-align: center\">\n    <code>{{ registrationCode }}</code>\n</p>\n\n<p>Service Desk medewerkers die je token kunnen activeren:</p>\n\n{% if ras is empty %}\n    <p>Er zijn geen Service Desk medewerkers beschikbaar.</p>\n{% else %}\n    <ul>\n        {% for ra in ras %}\n            <li>\n                <address>\n                    <strong>{{ ra.commonName }}</strong><br>\n                    {{ ra.location }}<br>\n                    {{ ra.contactInformation }}\n                </address>\n            </li>\n        {% endfor %}\n    </ul>\n{% endif %}",
           "en_GB": "<p>Dear {{ commonName }},</p>\n\n<p>Thank you for registering your token, you are almost ready now. Please visit the Service Desk to activate your token.</p>\n<p>Please bring the following:</p>\n<ul>\n    <li>Your token</li>\n    <li>A valid identity document (passport, drivers license or national ID-card)</li>\n    <li>The registration code from this e-mail</li>\n</ul>\n\n<p style=\"font-size: 150%; text-align: center\">\n    <code>{{ registrationCode }}</code>\n</p>\n\n<p>Service Desk employees authorized to activate your token:</p>\n\n{% if ras is empty %}\n    <p>No Service Desk employees are available.</p>\n{% else %}\n    <ul>\n        {% for ra in ras %}\n            <li>\n                <address>\n                    <strong>{{ ra.commonName }}</strong><br>\n                    {{ ra.location }}<br>\n                    {{ ra.contactInformation }}\n                </address>\n            </li>\n        {% endfor %}\n    </ul>\n{% endif %}"
       },
       "registration_code_with_ra_locations": {
           "nl_NL": "<p>Beste {{ commonName }},</p>\n\n<p>Bedankt voor het registreren van je token. Je token is bijna klaar voor gebruik. Ga naar de Service Desk om je token te laten activeren. </p>\n<p>Neem aub het volgende mee:</p>\n<ul>\n    <li>Je token</li>\n    <li>Een geldig legitimatiebewijs (paspoort, rijbewijs of nationale ID-kaart)</li>\n    <li>De registratiecode uit deze e-mail</li>\n</ul>\n\n<p style=\"font-size: 150%; text-align: center\">\n    <code>{{ registrationCode }}</code>\n</p>\n\n<p>Locaties waar je je token kunt activeren:</p>\n\n{% if raLocations is empty %}\n    <p>Er zijn geen locaties beschikbaar.</p>\n{% else %}\n    <ul>\n        {% for raLocation in raLocations %}\n            <li>\n                <address>\n                    <strong>{{ raLocation.name }}</strong><br>\n                    {{ raLocation.location }}<br>\n                    {{ raLocation.contactInformation }}\n                </address>\n            </li>\n        {% endfor %}\n    </ul>\n{% endif %}",
           "en_GB": "<p>Dear {{ commonName }},</p>\n\n<p>Thank you for registering your token, you are almost ready now. Please visit the Service Desk to activate your token.</p>\n<p>Please bring the following:</p>\n<ul>\n    <li>Your token</li>\n    <li>A valid identity document (passport, drivers license or national ID-card)</li>\n    <li>The registration code from this e-mail</li>\n</ul>\n\n<p style=\"font-size: 150%; text-align: center\">\n    <code>{{ registrationCode }}</code>\n</p>\n\n<p>Locations where  your token can be activated:</p>\n\n{% if raLocations is empty %}\n    <p>No locations are available.</p>\n{% else %}\n    <ul>\n        {% for raLocation in raLocations %}\n            <li>\n                <address>\n                    <strong>{{ raLocation.name }}</strong><br>\n                    {{ raLocation.location }}<br>\n                    {{ raLocation.contactInformation }}\n                </address>\n            </li>\n        {% endfor %}\n    </ul>\n{% endif %}"
       },
       "vetted": {
           "nl_NL": "<p>Beste {{ commonName }},</p>\n\n<p>Bedankt voor het activeren van je token. Je token is nu klaar voor gebruik.</p>",
           "en_GB": "<p>Dear {{ commonName }},</p>\n\n<p>Thank you for activating your token. Your token is now ready for use.</p>"
       },
       "second_factor_revoked": {
            "nl_NL": "<p>Beste {{ commonName }},</p><p>{% if isRevokedByRa %}De registratie van je {{ tokenType }} token met ID {{ tokenIdentifier }} is verwijderd door een beheerder.{% else %}Je hebt de registratie voor je {{ tokenType }} token met ID {{ tokenIdentifier }} verwijderd. Neem direct contact op met de helpdesk van je instelling als je dit zelf niet gedaan hebt, omdat dit kan betekenen dat je account gecompromitteerd is.{% endif %}</p> Je kunt dit token niet meer gebruiken om in te loggen bij op OpenConext aangesloten services die een tweede inlogstap vereisen.</p><p> Wil je een nieuw token aanvragen? Ga dan naar <a href=\"{{ selfServiceUrl }}\">{{ selfServiceUrl }}</a> en doorloop het registratieproces opnieuw.</p><p> Voor meer informatie kun je terecht op onze wiki: <a href=\"https://support.surfconext.nl/faq-sterke-authenticatie\">https://support.surfconext.nl/faq-sterke-authenticatie</a></p><p>Met vriendelijke groet,</p><p>SURFnet</p>",
            "en_GB": "<p>Dear {{ commonName }},</p><p>{% if isRevokedByRa %}The registration of your {{ tokenType }} with ID {{ tokenIdentifier }} was deleted by an administrator.{% else %}You have deleted the registration of your {{ tokenType }} token with ID {{ tokenIdentifier }}. If you did not delete your token you must immediately contact the support desk of your institution, as this may indicate that your account has been compromised.{% endif %}</p> You can no longer use this token to access OpenConext services that require two-step authentication.</p><p>Do you want to replace your token? Please visit <a href=\"{{ selfServiceUrl }}\">{{ selfServiceUrl }}</a> and register a new token.</p><p>For more info please visit our wiki: <a href=\"https://support.surfconext.nl/faq-strong-authentication\">https://support.surfconext.nl/faq-strong-authentication</a></p><p>Best regards,</p><p>SURFnet</p>"
       }
    },
    "gateway": {
        "identity_providers": [],
        "service_providers": [
            {
                "entity_id": "https://ss-dev.stepup.coin.surf.net/app_dev.php/authentication/metadata",
                "public_key": "MIIEJTCCAw2gAwIBAgIJANug+o++1X5IMA0GCSqGSIb3DQEBCwUAMIGoMQswCQYDVQQGEwJOTDEQMA4GA1UECAwHVXRyZWNodDEQMA4GA1UEBwwHVXRyZWNodDEVMBMGA1UECgwMU1VSRm5ldCBCLlYuMRMwEQYDVQQLDApTVVJGY29uZXh0MRwwGgYDVQQDDBNTVVJGbmV0IERldmVsb3BtZW50MSswKQYJKoZIhvcNAQkBFhxzdXJmY29uZXh0LWJlaGVlckBzdXJmbmV0Lm5sMB4XDTE0MTAyMDEyMzkxMVoXDTE0MTExOTEyMzkxMVowgagxCzAJBgNVBAYTAk5MMRAwDgYDVQQIDAdVdHJlY2h0MRAwDgYDVQQHDAdVdHJlY2h0MRUwEwYDVQQKDAxTVVJGbmV0IEIuVi4xEzARBgNVBAsMClNVUkZjb25leHQxHDAaBgNVBAMME1NVUkZuZXQgRGV2ZWxvcG1lbnQxKzApBgkqhkiG9w0BCQEWHHN1cmZjb25leHQtYmVoZWVyQHN1cmZuZXQubmwwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDXuSSBeNJY3d4p060oNRSuAER5nLWT6AIVbv3XrXhcgSwc9m2b8u3ksp14pi8FbaNHAYW3MjlKgnLlopYIylzKD/6Ut/clEx67aO9Hpqsc0HmIP0It6q2bf5yUZ71E4CN2HtQceO5DsEYpe5M7D5i64kS2A7e2NYWVdA5Z01DqUpQGRBc+uMzOwyif6StBiMiLrZH3n2r5q5aVaXU4Vy5EE4VShv3Mp91sgXJj/v155fv0wShgl681v8yf2u2ZMb7NKnQRA4zM2Ng2EUAyy6PQ+Jbn+rALSm1YgiJdVuSlTLhvgwbiHGO2XgBi7bTHhlqSrJFK3Gs4zwIsop/XqQRBAgMBAAGjUDBOMB0GA1UdDgQWBBQCJmcoa/F7aM3jIFN7Bd4uzWRgzjAfBgNVHSMEGDAWgBQCJmcoa/F7aM3jIFN7Bd4uzWRgzjAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQBd80GpWKjp1J+Dgp0blVAox1s/WPWQlex9xrx1GEYbc5elp3svS+S82s7dFm2llHrrNOBt1HZVC+TdW4f+MR1xq8O5lOYjDRsosxZc/u9jVsYWYc3M9bQAx8VyJ8VGpcAK+fLqRNabYlqTnj/t9bzX8fS90sp8JsALV4g84Aj0G8RpYJokw+pJUmOpuxsZN5U84MmLPnVfmrnuCVh/HkiLNV2c8Pk8LSomg6q1M1dQUTsz/HVxcOhHLj/owwh3IzXf/KXV/E8vSYW8o4WWCAnruYOWdJMI4Z8NG1Mfv7zvb7U3FL1C/KLV04DqzALXGj+LVmxtDvuxqC042apoIDQV",
                "acs": [
                    "https://ss-dev.stepup.coin.surf.net/app_dev.php/authentication/consume-assertion"
                ],
                "loa": {
                    "__default__": "https://gw-dev.stepup.coin.surf.net/authentication/loa1"
                },
                "second_factor_only": false,
                "second_factor_only_nameid_patterns": [],
                "assertion_encryption_enabled": false,
                "blacklisted_encryption_algorithms": []
            },
            {
                "entity_id": "https://ss-dev.stepup.coin.surf.net/app_dev.php/registration/gssf/tiqr/metadata",
                "public_key": "MIIEJTCCAw2gAwIBAgIJANug+o++1X5IMA0GCSqGSIb3DQEBCwUAMIGoMQswCQYDVQQGEwJOTDEQMA4GA1UECAwHVXRyZWNodDEQMA4GA1UEBwwHVXRyZWNodDEVMBMGA1UECgwMU1VSRm5ldCBCLlYuMRMwEQYDVQQLDApTVVJGY29uZXh0MRwwGgYDVQQDDBNTVVJGbmV0IERldmVsb3BtZW50MSswKQYJKoZIhvcNAQkBFhxzdXJmY29uZXh0LWJlaGVlckBzdXJmbmV0Lm5sMB4XDTE0MTAyMDEyMzkxMVoXDTE0MTExOTEyMzkxMVowgagxCzAJBgNVBAYTAk5MMRAwDgYDVQQIDAdVdHJlY2h0MRAwDgYDVQQHDAdVdHJlY2h0MRUwEwYDVQQKDAxTVVJGbmV0IEIuVi4xEzARBgNVBAsMClNVUkZjb25leHQxHDAaBgNVBAMME1NVUkZuZXQgRGV2ZWxvcG1lbnQxKzApBgkqhkiG9w0BCQEWHHN1cmZjb25leHQtYmVoZWVyQHN1cmZuZXQubmwwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDXuSSBeNJY3d4p060oNRSuAER5nLWT6AIVbv3XrXhcgSwc9m2b8u3ksp14pi8FbaNHAYW3MjlKgnLlopYIylzKD/6Ut/clEx67aO9Hpqsc0HmIP0It6q2bf5yUZ71E4CN2HtQceO5DsEYpe5M7D5i64kS2A7e2NYWVdA5Z01DqUpQGRBc+uMzOwyif6StBiMiLrZH3n2r5q5aVaXU4Vy5EE4VShv3Mp91sgXJj/v155fv0wShgl681v8yf2u2ZMb7NKnQRA4zM2Ng2EUAyy6PQ+Jbn+rALSm1YgiJdVuSlTLhvgwbiHGO2XgBi7bTHhlqSrJFK3Gs4zwIsop/XqQRBAgMBAAGjUDBOMB0GA1UdDgQWBBQCJmcoa/F7aM3jIFN7Bd4uzWRgzjAfBgNVHSMEGDAWgBQCJmcoa/F7aM3jIFN7Bd4uzWRgzjAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQBd80GpWKjp1J+Dgp0blVAox1s/WPWQlex9xrx1GEYbc5elp3svS+S82s7dFm2llHrrNOBt1HZVC+TdW4f+MR1xq8O5lOYjDRsosxZc/u9jVsYWYc3M9bQAx8VyJ8VGpcAK+fLqRNabYlqTnj/t9bzX8fS90sp8JsALV4g84Aj0G8RpYJokw+pJUmOpuxsZN5U84MmLPnVfmrnuCVh/HkiLNV2c8Pk8LSomg6q1M1dQUTsz/HVxcOhHLj/owwh3IzXf/KXV/E8vSYW8o4WWCAnruYOWdJMI4Z8NG1Mfv7zvb7U3FL1C/KLV04DqzALXGj+LVmxtDvuxqC042apoIDQV",
                "acs": [
                    "https://ss-dev.stepup.coin.surf.net/app_dev.php/registration/gssf/tiqr/consume-assertion"
                ],
                "loa": {
                    "__default__": "https://gw-dev.stepup.coin.surf.net/authentication/loa1"
                },
                "second_factor_only": false,
                "second_factor_only_nameid_patterns": [],
                "assertion_encryption_enabled": false,
                "blacklisted_encryption_algorithms": []
            },
            {
                "entity_id": "https://ra-dev.stepup.coin.surf.net/app_dev.php/vetting-procedure/gssf/tiqr/metadata",
                "public_key": "MIIEJTCCAw2gAwIBAgIJANug+o++1X5IMA0GCSqGSIb3DQEBCwUAMIGoMQswCQYDVQQGEwJOTDEQMA4GA1UECAwHVXRyZWNodDEQMA4GA1UEBwwHVXRyZWNodDEVMBMGA1UECgwMU1VSRm5ldCBCLlYuMRMwEQYDVQQLDApTVVJGY29uZXh0MRwwGgYDVQQDDBNTVVJGbmV0IERldmVsb3BtZW50MSswKQYJKoZIhvcNAQkBFhxzdXJmY29uZXh0LWJlaGVlckBzdXJmbmV0Lm5sMB4XDTE0MTAyMDEyMzkxMVoXDTE0MTExOTEyMzkxMVowgagxCzAJBgNVBAYTAk5MMRAwDgYDVQQIDAdVdHJlY2h0MRAwDgYDVQQHDAdVdHJlY2h0MRUwEwYDVQQKDAxTVVJGbmV0IEIuVi4xEzARBgNVBAsMClNVUkZjb25leHQxHDAaBgNVBAMME1NVUkZuZXQgRGV2ZWxvcG1lbnQxKzApBgkqhkiG9w0BCQEWHHN1cmZjb25leHQtYmVoZWVyQHN1cmZuZXQubmwwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDXuSSBeNJY3d4p060oNRSuAER5nLWT6AIVbv3XrXhcgSwc9m2b8u3ksp14pi8FbaNHAYW3MjlKgnLlopYIylzKD/6Ut/clEx67aO9Hpqsc0HmIP0It6q2bf5yUZ71E4CN2HtQceO5DsEYpe5M7D5i64kS2A7e2NYWVdA5Z01DqUpQGRBc+uMzOwyif6StBiMiLrZH3n2r5q5aVaXU4Vy5EE4VShv3Mp91sgXJj/v155fv0wShgl681v8yf2u2ZMb7NKnQRA4zM2Ng2EUAyy6PQ+Jbn+rALSm1YgiJdVuSlTLhvgwbiHGO2XgBi7bTHhlqSrJFK3Gs4zwIsop/XqQRBAgMBAAGjUDBOMB0GA1UdDgQWBBQCJmcoa/F7aM3jIFN7Bd4uzWRgzjAfBgNVHSMEGDAWgBQCJmcoa/F7aM3jIFN7Bd4uzWRgzjAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQBd80GpWKjp1J+Dgp0blVAox1s/WPWQlex9xrx1GEYbc5elp3svS+S82s7dFm2llHrrNOBt1HZVC+TdW4f+MR1xq8O5lOYjDRsosxZc/u9jVsYWYc3M9bQAx8VyJ8VGpcAK+fLqRNabYlqTnj/t9bzX8fS90sp8JsALV4g84Aj0G8RpYJokw+pJUmOpuxsZN5U84MmLPnVfmrnuCVh/HkiLNV2c8Pk8LSomg6q1M1dQUTsz/HVxcOhHLj/owwh3IzXf/KXV/E8vSYW8o4WWCAnruYOWdJMI4Z8NG1Mfv7zvb7U3FL1C/KLV04DqzALXGj+LVmxtDvuxqC042apoIDQV",
                "acs": [
                    "https://ra-dev.stepup.coin.surf.net/app_dev.php/vetting-procedure/gssf/tiqr/verify"
                ],
                "loa": {
                    "__default__": "https://gw-dev.stepup.coin.surf.net/authentication/loa1"
                },
                "second_factor_only": false,
                "second_factor_only_nameid_patterns": [],
                "assertion_encryption_enabled": false,
                "blacklisted_encryption_algorithms": []
            },
            {
                "entity_id": "https://ra-dev.stepup.coin.surf.net/app_dev.php/authentication/metadata",
                "public_key": "MIIEJTCCAw2gAwIBAgIJANug+o++1X5IMA0GCSqGSIb3DQEBCwUAMIGoMQswCQYDVQQGEwJOTDEQMA4GA1UECAwHVXRyZWNodDEQMA4GA1UEBwwHVXRyZWNodDEVMBMGA1UECgwMU1VSRm5ldCBCLlYuMRMwEQYDVQQLDApTVVJGY29uZXh0MRwwGgYDVQQDDBNTVVJGbmV0IERldmVsb3BtZW50MSswKQYJKoZIhvcNAQkBFhxzdXJmY29uZXh0LWJlaGVlckBzdXJmbmV0Lm5sMB4XDTE0MTAyMDEyMzkxMVoXDTE0MTExOTEyMzkxMVowgagxCzAJBgNVBAYTAk5MMRAwDgYDVQQIDAdVdHJlY2h0MRAwDgYDVQQHDAdVdHJlY2h0MRUwEwYDVQQKDAxTVVJGbmV0IEIuVi4xEzARBgNVBAsMClNVUkZjb25leHQxHDAaBgNVBAMME1NVUkZuZXQgRGV2ZWxvcG1lbnQxKzApBgkqhkiG9w0BCQEWHHN1cmZjb25leHQtYmVoZWVyQHN1cmZuZXQubmwwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDXuSSBeNJY3d4p060oNRSuAER5nLWT6AIVbv3XrXhcgSwc9m2b8u3ksp14pi8FbaNHAYW3MjlKgnLlopYIylzKD/6Ut/clEx67aO9Hpqsc0HmIP0It6q2bf5yUZ71E4CN2HtQceO5DsEYpe5M7D5i64kS2A7e2NYWVdA5Z01DqUpQGRBc+uMzOwyif6StBiMiLrZH3n2r5q5aVaXU4Vy5EE4VShv3Mp91sgXJj/v155fv0wShgl681v8yf2u2ZMb7NKnQRA4zM2Ng2EUAyy6PQ+Jbn+rALSm1YgiJdVuSlTLhvgwbiHGO2XgBi7bTHhlqSrJFK3Gs4zwIsop/XqQRBAgMBAAGjUDBOMB0GA1UdDgQWBBQCJmcoa/F7aM3jIFN7Bd4uzWRgzjAfBgNVHSMEGDAWgBQCJmcoa/F7aM3jIFN7Bd4uzWRgzjAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQBd80GpWKjp1J+Dgp0blVAox1s/WPWQlex9xrx1GEYbc5elp3svS+S82s7dFm2llHrrNOBt1HZVC+TdW4f+MR1xq8O5lOYjDRsosxZc/u9jVsYWYc3M9bQAx8VyJ8VGpcAK+fLqRNabYlqTnj/t9bzX8fS90sp8JsALV4g84Aj0G8RpYJokw+pJUmOpuxsZN5U84MmLPnVfmrnuCVh/HkiLNV2c8Pk8LSomg6q1M1dQUTsz/HVxcOhHLj/owwh3IzXf/KXV/E8vSYW8o4WWCAnruYOWdJMI4Z8NG1Mfv7zvb7U3FL1C/KLV04DqzALXGj+LVmxtDvuxqC042apoIDQV",
                "acs": [
                    "https://ra-dev.stepup.coin.surf.net/app_dev.php/authentication/consume-assertion"
                ],
                "loa": {
                    "__default__": "https://gw-dev.stepup.coin.surf.net/authentication/loa3"
                },
                "second_factor_only": false,
                "second_factor_only_nameid_patterns": [],
                "assertion_encryption_enabled": false,
                "blacklisted_encryption_algorithms": []
            }
        ]
    }
}
```


# Using the Institution Configuration API

This is the second of the three configuration APIs.

Institution configuration options for all institutions can be queried through the Institution Configuration API
using a `GET` request:
```
curl -XGET -v \
    -u username:password \
    -H "Accept: application/json" \
    -H "Content-type: application/json" \
    http://middleware.tld/management/institution-configuration
```
A json object containing institution names as keys and objects with all options as values is returned.

Institution configuration options can be configured through the Institution Configuration API using a `POST` request:
```
curl -XPOST -v \
    -u username:password \
    -H "Accept: application/json" \
    -H "Content-type: application/json" \
    -d @institution_configuration_options.json \
    http://middleware.tld/management/institution-configuration
```

## Institution Configuration Structure
The institution configuration options must be a json object containing the institution names as keys and objects with all options as values. The name of the institution is the value for the "urn:mace:terena.org:attribute-def:schacHomeOrganization" attribute in the SAML Assertion that the Gateway receives from the remote IdP.

The options must have the following keys:
* `use_ra_locations`: (boolean) whether an institution uses configurable RA locations instead of
 information of specific RA(A)s. Default: false
* `show_raa_contact_information`: (boolean) whether an institution shows RAAs' contact information when listing RAs, for example when showing locations for the vetting process. Default: true
* `verify_email`: (boolean) If disabled, users of this institution are not required to validate their e-mail address when registering new tokens. Default: true
* `number_of_tokens_per_identity` (integer) The number of tokens an identity is allowed to vet. If the option is not set, the default value of `1` is set.
* `allowed_second_factors`: (string[]) a list of second factor types that are allowed to be registered by users of this institution. This option only affects the registration of new second factors, it does not affect second factors that have been registered or vetted. If the list is empty all supported second factors are allowed. The supported second factors are found in the [Stepup-bundle](https://github.com/OpenConext/Stepup-bundle/blob/develop/src/Value/SecondFactorType.php#L31-L37). Default: empty list (all available second factors are allowed).
* `self_vet`: (boolean) Are users allowed to vet their other tokens with a previously vetted token?
* `sso_on_2fa`: (boolean) Are identities of the institution allowed to use SSO on 2FA?

And optionally the configuration can have these authorization related options:

See this [RFC](https://github.com/OpenConext/Stepup-Deploy/wiki/rfc-fine-grained-authorization) for more details.

* `use_ra`: (string[]) a list of SHOs of the institutions from which the RAs are also RAs in the institution. 
* `use_raa`: (string[]) a list of SHOs of the institutions from which the RAAs are also RAAs in the institution.
* `select_raa`: (string[]) a list of SHOs of the institutions from which the users may become RA(A)s in the institution.

Note that the FGA options should consist of whitelisted institutions. 

The structure of an institution configuration is therefore:
```
{
    "organisation.example": {
        "use_ra_locations": false,
        "show_raa_contact_information": true,
        "verify_email": true,
        "number_of_tokens_per_identity": 3,
        "allowed_second_factors": ["yubikey", "sms"],
        "self_vet": false        
    },
    "another-organisation.example": {
        "use_ra_locations": true,
        "show_raa_contact_information": false,
        "verify_email": true,
        "self_vet": true,
        "number_of_tokens_per_identity": 1,
        "allowed_second_factors": [],
        "use_ra": ["organisation.example"],
        "use_raa": [],
        "select_raa": ["organisation.example", "another-organisation.example"]
    }
}
```

When an institution is not present in the institution configuration the default configuration options are applied for that institution.


# Using the Whitelist API

This is the last of the three configuration APIs.

The whitelist API is used to add or remove institutions from the institution whitelist.

By default any SAML service provider that is configured to the gateway can authenticate a user at the remote IdP at LoA 1. This is basically a pass-though operation as no stepup will take place. In order to be allowed to use any of the stepup functionality the institution of the user must be on the whitelist. If an institution is not on the whitelist no Stepup functionality is available to users of that institution and only LoA 1 authentication through the gateway is allowed.

Stepup functionality governed by the whitelist:
- Login using the Stepup Gateway to any service provider at a LoA > 1
- Login to the Stepup Self-Service interface
- Login to the Stepup RA interface

The whitelist is a list of the value of the "urn:mace:terena.org:attribute-def:schacHomeOrganization" of the institution.

The whitelist is set by making a `POST` to the whitelist API:

```
curl -XPOST -v \
    -u username:password \
    -H "Accept: application/json" \
    -H "Content-type: application/json" \
    -d @new_configuration.json \
    http://middleware.tld/management/whitelist/replace
```

## Configuration Structure

The configuration must be a json object that has one key `institutions` that is a list of schacHomeOrganization values:
```json
{
 "institutions": [
    "surfnet.nl",
    "example.org"
  ]
}
```
