
The middleware exposes a REST interface for configuring the middleware. 
The configuration is stored in the database. This page is documents the 
requirements and structure of the configuration.

The configuration uses the JSON syntax.

# Structure

The configuration must be a json object with the following keys:

* [sraa](#sraa)
* [email_templates](#template-variables)
* [gateway](#gateway)

Each of these keys will be described in detail in a section below. The minimum structure the configuration must have is therefore:

```
{
    sraa: [],
    email_templates: {},
    gateway: {
        identity_providers: [],
        service_providers: []
    }
} 
```

As a full example:

```
{
  "sraa": [
    "39ba648867aa14a873339bb2a3031791ef319894"
  ],
  "email_templates": {
    "confirm_email": {
        "nl_NL": "<p>Beste {{ commonName }},</p>\n\n<p>Bedankt voor het registreren van je token. Klik op onderstaande link om je e-mailadres te bevestigen:</p>\n<p><a href=\"{{ verificationUrl }}\">{{ verificationUrl }}</a></p>\n<p>Is klikken op de link niet mogelijk? Kopieer dan de link en plak deze in de adresbalk van je browser.</p>\n<p>SURFnet</p>",
        "en_GB":"<p>Dear {{ commonName }},</p>\n\n<p>Thank you for registering your token. Please visit this link to verify your email address:</p>\n<p><a href=\"{{ verificationUrl }}\">{{ verificationUrl }}</a></p>\n<p>If you can not click on the URL, please copy the link and paste it in the address bar of your browser.</p>\n<p>SURFnet</p>"
    },
    "registration_code": {
        "nl_NL": "<p>Beste {{ commonName }},</p>\n\n<p>Bedankt voor het registreren van je token. Je token is bijna klaar voor gebruik. Ga naar de Service Desk om je token te laten activeren. </p>\n<p>Neem aub het volgende mee:</p>\n<ul>\n    <li>Je token</li>\n    <li>Een geldig legitimatiebewijs (paspoort, rijbewijs of nationale ID-kaart)</li>\n    <li>De registratiecode uit deze e-mail</li>\n</ul>\n\n<p style=\"font-size: 150%; text-align: center\">\n    <code>{{ registrationCode }}</code>\n</p>\n\n<p>Service Desk medewerkers die je token kunnen activeren:</p>\n\n{% if ras is empty %}\n    <p>Er zijn geen Service Desk medewerkers beschikbaar.</p>\n{% else %}\n    <ul>\n        {% for ra in ras %}\n            <li>\n                <address>\n                    <strong>{{ ra.commonName }}</strong><br>\n                    {{ ra.location }}<br>\n                    {{ ra.contactInformation }}\n                </address>\n            </li>\n        {% endfor %}\n    </ul>\n{% endif %}",
        "en_GB": "<p>Dear {{ commonName }},</p>\n\n<p>Thank you for registering your token, you are almost ready now. Please visit the Service Desk to activate your token.</p>\n<p>Please bring the following:</p>\n<ul>\n    <li>Your token</li>\n    <li>A valid identity document (passport, drivers license or national ID-card)</li>\n    <li>The registration code from this e-mail</li>\n</ul>\n\n<p style=\"font-size: 150%; text-align: center\">\n    <code>{{ registrationCode }}</code>\n</p>\n\n<p>Service Desk employees authorized to activate your token:</p>\n\n{% if ras is empty %}\n    <p>No Service Desk employees are available.</p>\n{% else %}\n    <ul>\n        {% for ra in ras %}\n            <li>\n                <address>\n                    <strong>{{ ra.commonName }}</strong><br>\n                    {{ ra.location }}<br>\n                    {{ ra.contactInformation }}\n                </address>\n            </li>\n        {% endfor %}\n    </ul>\n{% endif %}"
    },
    "vetted": {
        "nl_NL": "<p>Beste {{ commonName }},</p>",
        "en_GB": "<p>Dear {{ commonName }},</p>"
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
            "assertion_encryption_enabled": false,
            "blacklisted_encryption_algorithms": []
        }
    ]
  }
}
```

# Usage

See the README.md in the repository

## SRAA
### Specification

The Super Registration Authority Authority is configured by sending a list of NameIDs that should be granted SRAA rights when logged in to the RA application with a sufficient LOA.

```
sraa: [
	"NameID of RAA as received by the Gateway from OpenConext",
	"NameID of a different RAA as received by the Gateway from OpenConext"
]
```

### Processing

The list of current SRAA's will be deleted and the supplied list of SRAAs will be stored.

### Example

```
"sraa": [
    "39ba648867aa14a873339bb2a3031791ef319894"
]
```


## Email Templates
### Specification

The email_templates key must contain an object. Each property of this object denotes a specific type of email, the types available will be:

* confirm_email: (required) the email sent when the Registrant should prove the possession of his email address.
* registration_code: (required) the email sent when the Registrant has successfully registered a new Second Factor.
* vetted: (required) the email sent when the Registrant has successfully vetted a Second Factor.

The following list of emails is intended to be used in the future, the functionality requiring these is not yet implemented. 

* registration_code_expiration_warning: the email sent when the Registrant has not vetted his Second Factor after 1 week.
* second_factor_expiration_first_reminder: the email sent when the Second Factor has not been used for 5 months
* second_factor_expiration_second_reminder: the email sent when the Second Factor has not been used for 5 months + 2 weeks.
    second_factor_revocation_confirmation: the email sent when a Second Factor has successfully been revoked.

Each email contains an object, where each property corresponds with an IETF language tag (2 letter lower cased language code + underscore + 2 letter upper cased country code, i.e. nl_NL, nl_BE) that may be supported in the application.

```
email_templates: {
    confirm_email: {
        nl_NL: "Volledige template met een {{ variableName }} variabele in Twig syntax. May include <b>HTML</b> and 
new lines.",
        en_GB: "Full template with a {{ variableName }} variable in Twig syntax"
    }
}
```

### Processing

There will only be validation if the required email-template properties are present, each with at least the default language ("en_GB") template available. All previous templates will be removed from the database and the new templates will be inserted.


### Template Variables

#### e-mail verification (confirm_email)

| variable name   | type   | example                                                   |
|:----------------|:-------|:----------------------------------------------------------|
| commonName      | string | Jan Modaal                                                |
| email           | string | `jan@modaal.nl`                                           |
| verificationUrl | string | `http://self-service.com/verify-email?n=0123456789abcdef` |


#### registration (registration_code)

| variable name    | type   | example         |
|:-----------------|:-------|:----------------|
| commonName       | string | Jan Modaal      |
| email            | string | `jan@modaal.nl` |
| registrationCode | string | ABC23456        |


#### ras array

```
[
	[
		'commonName' => 'Jan Modaal',
		'location' => 'Goeman Borgesiuslaan 77, Utrecht'
		'contactInformation' => 'mail naar info@ibuildings.nl'
    ],
	[
		'commonName' => 'Henk Modaal',
		'location' => 'Moreelsepark, Utrecht'
		'contactInformation' => 'mail naar info@surfnet.nl'
    ]
]
```

* commonName: string
* location: string
* contactInformation: string

### After vetting (vetted)

| variable name    | type   | example         |
|:-----------------|:-------|:----------------|
| commonName       | string | Jan Modaal      |
| email            | string | `jan@modaal.nl` |


### Example


## Gateway
### Specification

The gateway section contains the configured saml entities for the gateway. This allows the registration of various IdPs and SPs with their respective configurations.

It must contain an object with the `identity_providers` and `service_providers` properties. Both must contain an array as value.

**TODO: Update doc. identity_providers is not used**
Each element in the `identity_providers` array must be an object and contain the entity_id and loa properties. The `entity_id` has a string as value that identifies the IdP that is listed as Authenticating Authority in the SAML assertion. The loa property must contain a hash (object) with at least the key `__default__` with the default required loa as value. Each additional key is used as EntityID of an SP, with the value as the minimum required LoA for that SP that should be required when you log in.

Each element in the `service_providers` array must be an object and contain the following properties: `entity_id`, `public_key`, `acs`, `loa`, `assertion_encryption_enabled`, `blacklisted_encryption_algorithms`.

The entity_id has a string as value that identifies the IdP that is listed as Authenticating Authority in the SAML assertion. The loa property must contain a hash (object) with at least the key `__default__` with the default required loa as value. Each additional key is used as EntityID of an IdO, with the value as the minimum required LoA for that IdP that should be required when you log in at that IdP. The public_key contain the certificate contents of the public key of the SP as it can be extracted from metadata (i.e. without `----BEGIN CERTIFICATE-----` etc.).
The `acs` property contains a list of AssertionConsumerUrls to which the SAMLResponse should be sent. Currently entries other than the first are ignored until ACS index is supported. assertion_encryption_enabled must be a boolean value that allows configuring whether or not the assertion that is sent to the SP should be encrypted. blacklisted_encryption_algorithms contains an array that lists (each as single string-element) algorithms that may not be used for encryption.

### Processing

Everything will be validated against the requirements listed above. Once the validation passes, the whole configuration that is in the database is removed and the new configuration is inserted. In other words: the configuration is overwritten.

### Example

```
gateway: {
	identity_providers: [
		{
			entity_id: "https://example.idp.tld/metadata",
			loa: {
				"__default__": "https://gateway.tld/assurance/loa2",
				"https://example.sp.tld/metadata": "https://gateway.tld/assurance/loa2"
			}
		}
	],
    service_providers: [
        {
            "entity_id": "https://ss-dev.stepup.coin.surf.net/app_dev.php/authentication/metadata",
            "public_key": "MIIEJTCCAw2gAwIBAgIJANug+o++<<SNIP FOR BREVITY>>KLV04DqzALXGj+LVmxtDvuxqC042apoIDQV",
            "acs": [
                "https://ss-dev.stepup.coin.surf.net/app_dev.php/authentication/consume-assertion"
            ], 
            "loa": {
                "__default__": "https://gw-dev.stepup.coin.surf.net/authentication/loa1"
            },
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
            "assertion_encryption_enabled": false,
            "blacklisted_encryption_algorithms": []
        }
    ]
}
```
