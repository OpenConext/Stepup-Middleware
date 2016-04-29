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

## Management API

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
            "nl_NL": "<p>Beste {{ commonName }},</p>\n\n<p>Bedankt voor het registreren van je token. Je token is bijna klaar voor gebruik. Ga naar de Service Desk om je token te laten activeren. </p>\n<p>Neem aub het volgende mee:</p>\n<ul>\n    <li>Je token</li>\n    <li>Een geldig legitimatiebewijs (paspoort, rijbewijs of nationale ID-kaart)</li>\n    <li>De registratiecode uit deze e-mail</li>\n</ul>\n\n<p style=\"font-size: 150%; text-align: center\">\n    <code>{{ registrationCode }}</code>\n</p>\n\n<p>Service Desk medewerkers die je token kunnen activeren:</p>\n\n{% if ras is empty %}\n    <p>Er zijn geen Service Desk medewerkers beschikbaar.</p>\n{% else %}\n    <ul>\n        {% for ra in ras %}\n            <li>\n                <address>\n                    <strong>{{ ra.commonName }}</strong><br>\n                    {{ ra.location }}<br>\n                    {{ ra.contactInformation }}\n                </address>\n            </li>\n        {% endfor %}\n    </ul>\n{% endif %}",
            "en_GB": "<p>Dear {{ commonName }},</p>\n\n<p>Thank you for registering your token, you are almost ready now. Please visit the Service Desk to activate your token.</p>\n<p>Please bring the following:</p>\n<ul>\n    <li>Your token</li>\n    <li>A valid identity document (passport, drivers license or national ID-card)</li>\n    <li>The registration code from this e-mail</li>\n</ul>\n\n<p style=\"font-size: 150%; text-align: center\">\n    <code>{{ registrationCode }}</code>\n</p>\n\n<p>Service Desk employees authorized to activate your token:</p>\n\n{% if ras is empty %}\n    <p>No Service Desk employees are available.</p>\n{% else %}\n    <ul>\n        {% for ra in ras %}\n            <li>\n                <address>\n                    <strong>{{ ra.commonName }}</strong><br>\n                    {{ ra.location }}<br>\n                    {{ ra.contactInformation }}\n                </address>\n            </li>\n        {% endfor %}\n    </ul>\n{% endif %}"
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
