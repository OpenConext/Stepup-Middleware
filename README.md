Step-up Middleware
==================

[![Build Status](https://travis-ci.org/SURFnet/Stepup-Middleware.svg)](https://travis-ci.org/SURFnet/Stepup-Middleware) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/SURFnet/Stepup-Middleware/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/SURFnet/Stepup-Middleware/?branch=develop) [![SensioLabs Insight](https://insight.sensiolabs.com/projects/ffe7f88f-648e-4ad8-b809-31ff4fead16a/mini.png)](https://insight.sensiolabs.com/projects/ffe7f88f-648e-4ad8-b809-31ff4fead16a)

## Requirements

 * PHP 5.4+
 * [Composer](https://getcomposer.org/)
 * A web server (Apache, Nginx)
 * MariaDB 10
 * Graylog2 (or disable this Monolog handler)
 * A working [Gateway](https://github.com/SURFnet/Stepup-Gateway)

## Installation

Clone the repository or download the archive to a directory. Install the dependencies by running `composer install` and fill out the database credentials et cetera.

Make sure to run database migrations using `app/console middleware:migrations:migrate`. 

## Management API

The configuration of the Gateway should be pushed to the Gateway 
so that it can be configured from the outside. 
This is done by making a POST request to an URL at the middleware, 
with the new configuration as JSON in the request body. 
This section is intended to document the requirements and structure of the configuration.

### Configuration API

Example cURL usage:
```
curl -XPOST -v \
    -u username:password \
    -H "Accept: application/json" \
    -H "Content-type: application/json" \
    -d @new_configuration.json \
    http://middleware.tld/management/configuration
```

### Configuration Structure

The configuration must be a json object with the following keys:
* sraa
* email_templates
* gateway
Each of these keys will be described in detail in a section below. The minimum structure the configuration must have is therefore:
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

As a full example:

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
        "registration_code": {
            "nl_NL": "<p>Beste {{ commonName }},</p>\n\n<p>Bedankt voor het registreren van je token. Je token is bijna klaar voor gebruik. Ga naar de Service Desk om je token te laten activeren. </p>\n<p>Neem aub het volgende mee:</p>\n<ul>\n    <li>Je token</li>\n    <li>Een geldig legitimatiebewijs (paspoort, rijbewijs of nationale ID-kaart)</li>\n    <li>De registratiecode uit deze e-mail</li>\n</ul>\n\n<p style=\"font-size: 150%; text-align: center\">\n    <code>{{ registrationCode }}</code>\n</p>\n\n<p>Service Desk medewerkers die je token kunnen activeren:</p>\n\n{% if vettingLocations is empty %}\n    <p>Er zijn geen Service Desk medewerkers beschikbaar.</p>\n{% else %}\n    <ul>\n        {% for vettingLocation in vettingLocations %}\n            <li>\n                <address>\n                    <strong>{{ vettingLocation.name }}</strong><br>\n                    {{ vettingLocation.location }}<br>\n                    {{ vettingLocation.contactInformation }}\n                </address>\n            </li>\n        {% endfor %}\n    </ul>\n{% endif %}",
            "en_GB": "<p>Dear {{ commonName }},</p>\n\n<p>Thank you for registering your token, you are almost ready now. Please visit the Service Desk to activate your token.</p>\n<p>Please bring the following:</p>\n<ul>\n    <li>Your token</li>\n    <li>A valid identity document (passport, drivers license or national ID-card)</li>\n    <li>The registration code from this e-mail</li>\n</ul>\n\n<p style=\"font-size: 150%; text-align: center\">\n    <code>{{ registrationCode }}</code>\n</p>\n\n<p>Service Desk employees authorized to activate your token:</p>\n\n{% if vettingLocations is empty %}\n    <p>No Service Desk employees are available.</p>\n{% else %}\n    <ul>\n        {% for vettingLocation in vettingLocations %}\n            <li>\n                <address>\n                    <strong>{{ vettingLocation.name }}</strong><br>\n                    {{ vettingLocation.location }}<br>\n                    {{ vettingLocation.contactInformation }}\n                </address>\n            </li>\n        {% endfor %}\n    </ul>\n{% endif %}"
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

## SRAA
### Specification
The Super Registration Authority Authority is configured by sending a list of NameIDs that should be granted SRAA rights when logged in to the RA application with a sufficient LOA.
```json
"sraa": [
    "NameID of RAA as received by the Gateway from SURFConext",
    "NameID of a different RAA as received by the Gateway from SURFConext"
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

The email_templates key must contain an object. 
Each property of this object denotes a specific type of email, the types available will be:
* ```confirm_email```: **(required)** the email sent when the Registrant should prove the possession of his email address.
* ```registration_code```: **(required)** the email sent when the Registrant has successfully registered a new Second Factor.
* ```vetted```: **(required)** the email sent when the Registrant has successfully vetted a Second Factor.

The following list of emails is intended to be used in the future, 
the functionality requiring these is not yet implemented. 
* ```registration_code_expiration_warning```: the email sent when the Registrant has not vetted his Second Factor after 1 week.
* ```second_factor_expiration_first_reminder```: the email sent when the Second Factor has not been used for 5 months
* ```second_factor_expiration_second_reminder```: the email sent when the Second Factor has not been used for 5 months + 2 weeks.
* ```second_factor_revocation_confirmation```: the email sent when a Second Factor has successfully been revoked.

Each email contains an object, where each property corresponds with an IETF language tag (2 letter lower cased language code + underscore + 2 letter upper cased country code, i.e. nl_NL, nl_BE) that may be supported in the application.

```json
"email_templates": {
    "confirm_email": {
        "nl_NL": "Volledige template met een {{ variableName }} variabele in Twig syntax. May include <b>HTML</b> and 
new lines.",
        "en_GB": "Full template with a {{ variableName }} variable in Twig syntax"
    }
}
```

### Processing
There will only be validation if the required email-template properties are present, 
each with at least the default language ("en_GB") template available. 
All previous templates will be removed from the database and the new templates will be inserted. 

### Template Variables

##### e-mail verification (confirm_email)
| variable name   | type   | example                                                 |
|-----------------|--------|---------------------------------------------------------|
| commonName      | string | Jan Modaal                                              |
| email           | string | jan@modaal.nl                                           |
| verificationUrl | string | http://self-service.com/verify-email?n=0123456789abcdef |

#### registration (registration_code)
<table>
	<thead>
		<tr>
			<th>
			<p>variable name</p>
			</th>
			<th>
			<p>type</p>
			</th>
			<th>
			<p>example</p>
			</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>commonName</td>
			<td>string</td>
			<td>Jan Modaal</td>
		</tr>
		<tr>
			<td>email</td>
			<td>string</td>
			<td>jan@modaal.nl</td>
		</tr>
		<tr>
			<td>registrationCode</td>
			<td>string</td>
			<td>ABC23456</td>
		</tr>
		<tr>
			<td colspan="1">ras</td>
			<td colspan="1">array</td>
			<td colspan="1">
			<table border="0" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td>
						<p><code>[</code></p>
						<p><code>&nbsp;&nbsp;&nbsp;&nbsp;</code><code>[</code></p>
						<p><code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</code><code>&#39;commonName&#39;</code>&nbsp;<code>=&gt;&nbsp;</code><code>&#39;Jan Modaal&#39;</code><code>,</code></p>
						<p><code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</code><code>&#39;location&#39;</code>&nbsp;<code>=&gt;&nbsp;</code><code>&#39;Goeman Borgesiuslaan 77, Utrecht&#39;</code></p>
						<p><code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</code><code>&#39;contactInformation&#39;</code>&nbsp;<code>=&gt;&nbsp;</code><code>&#39;mail naar info@ibuildings.nl&#39;</code></p>
						<p><code>&nbsp;&nbsp;&nbsp;&nbsp;</code><code>],</code></p>
						<p><code>&nbsp;&nbsp;&nbsp;&nbsp;</code><code>[</code></p>
						<p><code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</code><code>&#39;commonName&#39;</code>&nbsp;<code>=&gt;&nbsp;</code><code>&#39;Henk Modaal&#39;</code><code>,</code></p>
						<p><code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</code><code>&#39;location&#39;</code>&nbsp;<code>=&gt;&nbsp;</code><code>&#39;Moreelsepark, Utrecht&#39;</code></p>
						<p><code>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</code><code>&#39;contactInformation&#39;</code>&nbsp;<code>=&gt;&nbsp;</code><code>&#39;mail naar info@surfnet.nl&#39;</code></p>
						<p><code>&nbsp;&nbsp;&nbsp;&nbsp;</code><code>]</code></p>
						<p><code>]</code></p>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
		</tr>
		<tr>
			<td colspan="1">╰ commonName</td>
			<td colspan="1">string</td>
			<td colspan="1">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="1">╰ location</td>
			<td colspan="1">string</td>
			<td colspan="1">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="1">╰ contactInformation</td>
			<td colspan="1">string</td>
			<td colspan="1">&nbsp;</td>
		</tr>
	</tbody>
</table>

#### After vetting (vetted)

| name       | type   | example       |
|------------|--------|---------------|
| commonName | string | Jan Modaal    |
| email      | string | jan@modaal.nl |


## Gateway
### Specification:
The gateway section contains the configured saml entities for the gateway. 
This allows the registration of various IdPs and SPs with their respective configurations.
It must contain an object with the ```identity_providers``` and ```service_providers``` properties. 
Both must contain an array as value.

Each element in the ```identity_providers``` array must be an object and contain the ```entity_id``` and ```loa``` properties. 
* ```entity_id``` has a string as value that identifies the IdP that is listed as Authenticating Authority in the SAML assertion. 
* ```loa``` property must contain a hash (object) with at least the key __default__ with the default required loa as value. Each additional key is used as EntityID of an SP, with the value as the minimum required LoA for that SP that should be required when you log in.

Each element in the ```service_providers``` array must be an object and contain the following properties: 
* ```entity_id``` has a string as value that identifies the IdP that is listed as Authenticating Authority in the SAML assertion. 
* ```public_key``` contain the certificate contents of the public key of the SP as it can be extracted from metadata (i.e. without ----CERTIFATE----- etc.). 
* ```acs``` property contains a list of AssertionConsumerUrls to which the SAMLResponse should be sent. Currently entries other than the first are ignored until ACS index is supported. 
* ```loa``` property must contain a hash (object) with at least the key __default__ with the default required loa as value.
* ```second_factor_only``` boolean determines whether this SP is allowed to use the Second Factor Only (/second-factor-only/metadata) mode, note that it then **may not** use the regular Gateway.
* ```second_factor_only_nameid_patterns``` should contain a list of patterns (strings that may contain a wildcard character) that are allowed to use the Second Factor Only mode. Does nothing if ```second_factor_only``` is not set to true.
* ```assertion_encryption_enabled``` must be a boolean value that allows configuring whether or not the assertion that is sent to the SP should be encrypted. 
* ```blacklisted_encryption_algorithms``` contains an array that lists (each as single string-element) algorithms that may not be used for encryption.

### Processing
Everything will be validated against the requirements listed above. Once the validation passes, the whole configuration that is in the database is removed and the new configuration is inserted. In other words: the configuration is overwritten.

### LOA Resolution
It is possible to specify a LOA in 3 places:
  1. The AuthnContextClassRef in the Authentication Request (SAML2 AuthnRequest).
  2. The ```loa``` on the ```service_providers``` configuration.
  3. The ```loa``` on the ```identity_providers``` configuration.

The Gateway will authenticate the user with the highest LOA of all these.
Second Factor Only mode requires that AuthnRequests use LOA aliases. However these are quickly translated to the equivalent of 'regular' LOAs. The configuration MUST NOT use Second Factor Only LOA aliases.

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

## Notes

### Mocking time

Due to a limitation of mocking of static methods, to mock time, the helper `DateTimeHelper::stubNow(DateTime $now)` was
created. Call `::stubNow($now)` to set a fixed date/time, and call `::stubNow(null)` to disable stubbing. It is
recommended to run tests in a separate process when using this helper so the stub value doesn't persist between tests.

```php
/** @runTestInSeparateProcess */
public function testItWorks()
{
    # Trick `DateTime::now()` into thinking it is 1970.
    DateTimeHelper::stubNow(new DateTime('@0'));

    $this->assertEquals('1970-01-01T00:00:00+00:00', (string) \Surfnet\Stepup\DateTime\DateTime::now());
}
```

### Adding support for a new Generic SAML Second Factor `biometric`, by example

 * https://github.com/SURFnet/Stepup-bundle/pull/31/commits/55279033a7f4e261277008603d9be94ebb582469
 * Release a new minor version of `surfnet/stepup-bundle`.
 * https://github.com/SURFnet/Stepup-Middleware/pull/106/commits/c3b42c92593f10587f9e0051420e711c974dd319
 * https://github.com/SURFnet/Stepup-SelfService/pull/96/commits/efa7feb29f0ee26d0d9860849f3f379131ba23cd
 * https://github.com/SURFnet/Stepup-RA/pull/102/commits/f2c0d4f57912a6c026c58db2818735bacf7a7787
 * https://github.com/SURFnet/Stepup-Gateway/pull/90/commits/1463cf05d1bec9e5e1fa1103b81fa6ada00a611f
 * Add the Self-Service and RA applications to the `gssp_allowed_sps` parameters:
```yaml
gssp_allowed_sps:
   - (...)
   - 'https://ss-dev.stepup.coin.surf.net/app_dev.php/registration/gssf/biometric/metadata'
   - 'https://ra-dev.stepup.coin.surf.net/app_dev.php/vetting-procedure/gssf/biometric/metadata'
```
 * Configure these SPs through the Middleware configuration API.
