# Middleware APIs used by the SelfService component

This document describes every API call the SelfService component (`Stepup-SelfService`) makes
against the Middleware. It complements [MiddlewareAPI.md](MiddlewareAPI.md) (the full Middleware
API), [MiddlewareAPICommands.md](MiddlewareAPICommands.md) (command payloads) and
[MiddlewareAPIRA.md](MiddlewareAPIRA.md) (the calls made by the RA).

The SelfService talks to the same two Middleware endpoints as the RA:

| API | HTTP | Purpose |
| --- | --- | --- |
| Read API (base URL of the Middleware) | `GET` | Read Middleware resources |
| Command API (`/command`) | `POST` | Execute commands |

Both are protected with HTTP Basic authentication using the SelfService's Middleware credentials
(typically the `ss` user). GET requests send `Accept: application/json`; command requests send
`Accept: application/json` and a JSON body.

## Read (GET) API calls

### Identity

#### `GET identity`

| Query | Required | Description |
| --- | --- | --- |
| `NameID` | yes | NameID of the identity |
| `institution` | yes | Institution of the identity (name or UUID) |

Used by the SAML login flow to resolve the identity from the NameID in the SAML assertion.
The SelfService expects the result to contain exactly one identity.

### Unverified second factors

#### `GET unverified-second-factor/{secondFactorId}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used to load the details of an unverified second factor, e.g. when following a registration link.

#### `GET unverified-second-factors`

| Query | Required | Description |
| --- | --- | --- |
| `identityId` | yes | Identity ID of the registrant |
| `verificationNonce` | no | Verification nonce (when following an email verification link) |

Used to list the registrant's unverified second factors (registration overview) and to look up
an unverified second factor by its email verification nonce.

### Verified second factors

#### `GET verified-second-factor/{secondFactorId}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used to load the details of a verified second factor (self-vetting and registration status).

#### `GET verified-second-factors-of-identity`

| Query | Required | Description |
| --- | --- | --- |
| `identityId` | yes | Identity ID of the registrant |

Used to list the registrant's own verified second factors.

### Vetted second factors

#### `GET vetted-second-factor/{secondFactorId}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used to load the details of a vetted second factor.

#### `GET vetted-second-factors`

| Query | Required | Description |
| --- | --- | --- |
| `identityId` | yes | Identity ID of the registrant |

Used to list the registrant's own vetted second factors (the "your tokens" overview).

### Recovery tokens

#### `GET recovery_token/{recoveryTokenId}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used to load the details of a single recovery token.

#### `GET recovery_tokens`

| Query | Required | Description |
| --- | --- | --- |
| `actorId` | yes | Identity ID of the registrant |
| `identityId` | yes | Identity ID of the registrant |
| `status` | yes | `active` (the SelfService only fetches active recovery tokens) |
| `p` | yes | Page number (always 1) |

Used to check whether the registrant has a recovery token and to list its active recovery tokens.

### Authorization

#### `GET authorization/may-register-self-asserted-tokens/{identityId}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used on the registration page to determine whether the identity may register a token using
self-asserted registration (safe store, GSSP or a second Yubikey).

#### `GET authorization/may-self-vet-using-self-asserted-token/{identityId}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used on the self-vetting page to determine whether the identity may self-vet using a
self-asserted token.

#### `GET authorization/may-register-recovery-tokens/{identityId}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used on the registration page to determine whether the identity may register a recovery token.

### Registration authorities

#### `GET registration-authority`

| Query | Required | Description |
| --- | --- | --- |
| `institution` | yes | Institution (name or UUID) |

Used on the self-vetting page to list the RAs (and RAAs) of the registrant's institution that
can be selected for the vetting appointment.

### RA locations

#### `GET ra-location`

| Query | Required | Description |
| --- | --- | --- |
| `institution` | yes | Institution (name or UUID) |
| `orderBy` | yes | Sort field (defaults to `name`) |
| `orderDirection` | yes | `asc` or `desc` (defaults to `asc`) |

Used on the self-vetting page to populate the RA location dropdown for the vetting appointment.

### Institutions

#### `GET institution-configuration-options/{institutionName}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used on the registration and self-vetting pages to determine which second factor types and
vetting types are allowed for the registrant's institution.

### Vetting type hints

#### `GET vetting-type-hint/{institution}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used on the registration page to show the vetting type hints saved for the registrant's
institution.

## Command API calls

All commands are sent as `POST /command` with a JSON body of the shape:

```json
{
  "command": {
    "name": "Identity:ProvePhonePossession",
    "uuid": "generated-by-the-selfservice",
    "payload": { "...": "command-specific payload" }
  },
  "meta": {
    "actor_id": "identity UUID of the registrant",
    "actor_institution": "institution of the registrant"
  }
}
```

The command name follows the `<AggregateRoot>:<Command>` notation. `meta` carries the acting
identity and its institution; when there is no authenticated session yet (e.g. during the first
login) both are `null`. A successful response is `200 OK` with
`{ "command": "<uuid>", "processed_by": "<middleware server name>" }`; a failed command is
returned with an HTTP error code and `{ "errors": ["..."] }`.

### Identity commands

#### `Identity:CreateIdentity`

Create the identity during first login (no existing identity found for the NameID).

| Payload field | Required | Description |
| --- | --- | --- |
| `id` | yes | UUID of the identity |
| `name_id` | yes | NameID of the identity |
| `institution` | yes | Institution of the identity |
| `email` | yes | Email address of the identity |
| `common_name` | yes | Common name of the identity |
| `preferred_locale ` | yes | Preferred locale: `nl` or `en`. Note: as sent by the SelfService this key has a trailing space |

#### `Identity:UpdateIdentity`

Update the registrant's profile (email address and/or common name).

| Payload field | Required | Description |
| --- | --- | --- |
| `id` | yes | UUID of the identity |
| `email` | yes | New email address |
| `common_name` | yes | New common name |
| `institution` | yes | Institution of the identity |

#### `Identity:ExpressLocalePreference`

Change the preferred locale of the logged-in registrant (language switch).

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the registrant identity |
| `preferred_locale` | yes | `nl` or `en` |

#### `Identity:VerifyEmail`

Verify the email address of a second factor (following the verification link).

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the identity |
| `verification_nonce` | yes | Verification nonce from the email link |

#### `Identity:SendSecondFactorRegistrationEmail`

Resend the registration email for a second factor (e.g. when a Yubikey was lost).

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the identity |
| `second_factor_id` | yes | UUID of the second factor |

#### `Identity:RevokeOwnSecondFactor`

Revoke one's own second factor (token overview → "revoke").

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the identity |
| `second_factor_id` | yes | UUID of the second factor |

#### `Identity:RevokeOwnRecoveryToken`

Revoke one's own recovery token (recovery token overview → "revoke").

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the identity |
| `recovery_token_id` | yes | UUID of the recovery token |

#### `Identity:SelfVetSecondFactor`

Self-vet a second factor using a self-asserted token (instead of an RA).

| Payload field | Required | Description |
| --- | --- | --- |
| `authority_id` | yes | UUID of the identity performing the vetting (the registrant) |
| `identity_id` | yes | UUID of the identity |
| `second_factor_id` | yes | UUID of the second factor to vet |
| `second_factor_type` | yes | Type of the second factor |
| `registration_code` | yes | Registration code |
| `second_factor_identifier` | yes | Identifier of the second factor |
| `authoring_second_factor_identifier` | yes | Identifier of the self-asserted token used to authorize the vetting |

#### `Identity:RegisterSelfAssertedSecondFactor`

Register a self-asserted second factor (safe store / GSSP / second Yubikey).

| Payload field | Required | Description |
| --- | --- | --- |
| `authority_id` | yes | UUID of the identity performing the registration (the registrant) |
| `identity_id` | yes | UUID of the identity |
| `second_factor_id` | yes | UUID of the second factor |
| `second_factor_type` | yes | Type of the second factor |
| `registration_code` | yes | Registration code |
| `second_factor_identifier` | yes | Identifier of the second factor |
| `authoring_recovery_token_id` | yes | UUID of the recovery token used to authorize the registration |

#### `Identity:ProvePhonePossession`

Prove possession of a phone number (SMS OTP during second factor registration).

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the identity |
| `second_factor_id` | yes | UUID of the second factor |
| `phone_number` | yes | International phone number |

#### `Identity:ProveYubikeyPossession`

Prove possession of a Yubikey (the SelfService first verifies an OTP against the Gateway, then
sends the public ID extracted from that OTP).

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the identity |
| `second_factor_id` | yes | UUID of the second factor |
| `yubikey_public_id` | yes | Public ID of the Yubikey |

#### `Identity:ProveGssfPossession`

Prove possession of a GSSP (eIDAS) identity (during self-asserted registration).

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the identity |
| `second_factor_id` | yes | UUID of the second factor |
| `stepup_provider` | yes | Name of the Stepup provider used for the GSSP verification |
| `gssf_id` | yes | ID of the verified GSSP |

#### `Identity:ProvePhoneRecoveryTokenPossession`

Prove possession of a phone number during (re)registration of an SMS recovery token.

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the identity |
| `recovery_token_id` | yes | UUID of the recovery token |
| `phone_number` | yes | International phone number |

#### `Identity:PromiseSafeStoreSecretTokenPossession`

Promise possession of a safe store (DigiD/eenheid) secret token during recovery token
registration.

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the identity |
| `recovery_token_id` | yes | UUID of the recovery token |
| `secret` | yes | Secret (as promised by the registrant) |

## Out of scope: other API calls made by the SelfService

The SelfService also talks to other Stepup components, which are not Middleware APIs:

- `POST api/verify-yubikey` on the **Gateway** — used to verify a Yubikey OTP during second
  factor registration.
- GSSP (eIDAS) verification — used during self-asserted registration of GSSP second factors.
- SMS service — used to send and verify SMS challenges for second factors and SMS recovery
  tokens.
