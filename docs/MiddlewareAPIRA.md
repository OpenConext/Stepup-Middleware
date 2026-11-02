# Middleware APIs used by the RA component

This document describes every API call the RA component (`Stepup-RA`) makes against the
Middleware. It complements [MiddlewareAPI.md](MiddlewareAPI.md) (the full Middleware API) and
[MiddlewareAPICommands.md](MiddlewareAPICommands.md) (command payloads).

The RA talks to two Middleware endpoints:

| API | Base URL (RA config) | HTTP | Purpose |
| --- | --- | --- | --- |
| Read API | `middleware_url_api` (e.g. `https://middleware.example.org/`) | `GET` | Read Middleware resources |
| Command API | `middleware_url_command_api` (e.g. `https://middleware.example.org/command`) | `POST` | Execute commands |

Both are protected with HTTP Basic authentication using the RA's Middleware credentials
(`middleware_credentials_username` / `middleware_credentials_password` in the RA configuration,
typically the `ra` user). GET requests send `Accept: application/json`; command requests send
`Accept: application/json` and a JSON body.

## Read (GET) API calls

`p` is the (1-based) page number used for paged collections.

### Identity

#### `GET identity/{identityId}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used when logging in (loading the authenticated RA identity) and when vetting (loading the
identity of the person being vetted, to translate the SMS challenge into their preferred locale).

#### `GET identity`

| Query | Required | Description |
| --- | --- | --- |
| `NameID` | yes | NameID of the identity |
| `institution` | yes | Institution of the identity (name or UUID) |

Used by the SAML login flow to resolve the identity from the NameID in the SAML assertion.
The RA expects the result to contain exactly one identity.

### Profile

#### `GET profile/{identityId}`

| Query | Required | Description |
| --- | --- | --- |
| `actorId` | yes | Identity ID of the acting RA |

Used by RAA-facing pages (profile, RA locations, vetting type hints) to display details of the
authenticated RAA.

### Verified second factors

#### `GET verified-second-factors`

| Query | Required | Description |
| --- | --- | --- |
| `actorId` | yes | Identity ID of the acting RA |
| `registrationCode` | yes | Registration code from the vetting link |
| `identityId` | no | Identity ID |
| `secondFactorId` | no | Second factor ID |
| `institution` | no | Institution |

Used to start a vetting procedure: the RA looks up the verified second factor by registration
code (the RA expects exactly one match).

#### `GET verified-second-factor/{secondFactorId}/skip-prove-possession`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used when starting a vetting procedure to determine whether the prove-possession step can be
skipped for this second factor.

### Recovery tokens

#### `GET recovery_tokens`

| Query | Required | Description |
| --- | --- | --- |
| `actorId` | yes | Identity ID of the acting RA |
| `identityId` | no | Identity ID |
| `type` | no | Recovery token type |
| `name` | no | Common name of the identity |
| `email` | no | Email address |
| `institution` | no | Institution |
| `status` | no | `vetted`, `active`, `expired` or `revoked` |
| `orderBy` | no | Sort field |
| `orderDirection` | no | `asc` or `desc` |
| `p` | no | Page number |

Used by the recovery token overview page (paginated, filterable).

### RA candidates

#### `GET ra-candidate`

| Query | Required | Description |
| --- | --- | --- |
| `actorId` | yes | Identity ID of the acting RA |
| `institution` | no | Institution of the candidate |
| `commonName` | no | Common name of the candidate |
| `email` | no | Email address of the candidate |
| `raInstitution` | no | RA institution the candidate would be accredited at |
| `secondFactorTypes` | yes | Array of second factor types that satisfy LOA3 for on-premises vetting |
| `orderBy` | no | Sort field |
| `orderDirection` | no | `asc` or `desc` |
| `p` | no | Page number |

Used by the "accredit RA" overview page to list identities that can be accredited as RA/RAA.

#### `GET ra-candidate/{identityId}`

| Query | Required | Description |
| --- | --- | --- |
| `actorId` | yes | Identity ID of the acting RA |

Used on the candidate detail page, to show the institutions and roles the candidate can be
accredited at.

### RA listings

#### `GET ra-listing`

| Query | Required | Description |
| --- | --- | --- |
| `actorId` | yes | Identity ID of the acting RA/RAA |
| `institution` | no | Institution |
| `identityId` | no | Identity ID |
| `name` | no | Common name |
| `email` | no | Email address |
| `role` | no | `ra` or `raa` |
| `raInstitution` | no | RA institution |
| `orderBy` | no | Sort field (defaults to `commonName`) |
| `orderDirection` | no | `asc` or `desc` (defaults to `asc`) |
| `p` | no | Page number |

Used by the RA management overview page. When exporting to CSV, the RA fetches all pages
sequentially using this endpoint until the last page is reached.

#### `GET ra-listing/{identityId}/{institution}`

| Query | Required | Description |
| --- | --- | --- |
| `actorId` | yes | Identity ID of the acting RA/RAA |

Used on the RA detail page (amending contact information / retracting the RA role).

### RA second factors

#### `GET ra-second-factors`

| Query | Required | Description |
| --- | --- | --- |
| `actorId` | yes | Identity ID of the acting RA/RAA |
| `name` | no | Common name of the identity |
| `type` | no | Second factor type |
| `secondFactorId` | no | Second factor ID |
| `email` | no | Email address |
| `status` | no | `vetted`, `active`, `expired` or `revoked` |
| `institution` | no | Institution |
| `orderBy` | no | Sort field |
| `orderDirection` | no | `asc` or `desc` |
| `p` | no | Page number |

Used by the second factor overview page (paginated, filterable).

#### `GET ra-second-factors-export`

Same filters as `ra-second-factors` above, minus `p` (the export is not paginated).

Used by the CSV export of second factors.

### Second factor audit log

#### `GET audit-log/second-factors`

| Query | Required | Description |
| --- | --- | --- |
| `institution` | yes | Institution of the identity |
| `identityId` | yes | Identity ID |
| `orderBy` | yes | Sort field (defaults to `recordedOn`) |
| `orderDirection` | yes | `asc` or `desc` (defaults to `desc`) |
| `p` | yes | Page number |

Used by the second factor audit log page (shown from the second factor detail view).

### RA locations

#### `GET ra-location`

| Query | Required | Description |
| --- | --- | --- |
| `institution` | yes | RA institution |
| `orderBy` | yes | Sort field (defaults to `name`) |
| `orderDirection` | yes | `asc` or `desc` (defaults to `asc`) |

Used by the RA location overview page (RAA only).

#### `GET ra-location/{raLocationId}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used on the RA location edit page (RAA only).

### Institutions

#### `GET institution-listing`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used to populate the institution dropdowns on the RA location, RAA and vetting type hint pages.

### Institution configuration

#### `GET institution-configuration-options/{institutionName}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used by the institution configuration page (RAA only).

### Vetting type hints

#### `GET vetting-type-hint/{institution}`

| Query | Required | Description |
| --- | --- | --- |
| — | — | none |

Used by the vetting type hint page to load the hints previously saved for the acting RA.

## Command API calls

All commands are sent as `POST {middleware_url_command_api}` with a JSON body of the shape:

```json
{
  "command": {
    "name": "Identity:AccreditIdentity",
    "uuid": "generated-by-the-RA",
    "payload": { "...": "command-specific payload" }
  },
  "meta": {
    "actor_id": "identity UUID of the acting RA",
    "institution": "institution of the acting RA"
  }
}
```

The command name follows the `<AggregateRoot>:<Command>` notation. A successful response is
`200 OK` with `{ "command": "<uuid>", "processed_by": "<middleware server name>" }`; a failed
command is returned with an HTTP error code and `{ "errors": ["..."] }`.

### Identity commands

#### `Identity:AccreditIdentity`

Accredit a candidate as RA or RAA (candidate detail page → "accredit").

| Payload field | Required | Description |
| --- | --- | --- |
| `institution` | yes | Institution of the candidate identity |
| `identity_id` | yes | UUID of the candidate identity |
| `role` | yes | `ra` or `raa` |
| `location` | no | RA location of the new RA (empty string when not set) |
| `contact_information` | no | Contact information of the new RA (empty string when not set) |
| `ra_institution` | yes | Institution the identity is accredited at |

#### `Identity:AmendRegistrationAuthorityInformation`

Update contact information/location of an RA (RA detail page → "amend").

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the RA identity |
| `location` | yes | New RA location |
| `contact_information` | yes | New contact information |
| `ra_institution` | yes | RA institution the information is amended at |

#### `Identity:AppointRole`

Change the role of an RA (RA detail page → "change role").

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the RA identity |
| `role` | yes | New role: `ra` or `raa` |

#### `Identity:RetractRegistrationAuthority`

Retract an RA/RAA (RA detail page → "retract").

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the RA identity |
| `ra_institution` | yes | RA institution the role is retracted at |

#### `Identity:ExpressLocalePreference`

Change the preferred locale of the logged-in RA (language switch in the RA menu).

| Payload field | Required | Description |
| --- | --- | --- |
| `identity_id` | yes | UUID of the acting RA identity |
| `preferred_locale` | yes | `nl` or `en` |

#### `Identity:VetSecondFactor`

Vet a second factor (final step of the vetting procedure).

| Payload field | Required | Description |
| --- | --- | --- |
| `authority_id` | yes | UUID of the RA performing the vetting |
| `identity_id` | yes | UUID of the identity owning the second factor |
| `second_factor_id` | yes | UUID of the second factor |
| `registration_code` | yes | Registration code from the vetting link |
| `second_factor_type` | yes | Type of the second factor |
| `second_factor_identifier` | yes | Identifier of the second factor (e.g. phone number, public ID) |
| `document_number` | yes | Document number used to verify the identity (or empty) |
| `identity_verified` | yes | `true` when the identity was verified in person |
| `prove_possession_skipped` | yes | `true` when the prove-possession step was skipped |

#### `Identity:RevokeRegistrantsSecondFactor`

Revoke a second factor of another user (second factor detail page → "revoke").

| Payload field | Required | Description |
| --- | --- | --- |
| `authority_id` | yes | UUID of the RA performing the revocation |
| `identity_id` | yes | UUID of the identity owning the second factor |
| `second_factor_id` | yes | UUID of the second factor |

#### `Identity:RevokeRegistrantsRecoveryToken`

Revoke a recovery token of another user (recovery token page → "revoke").

| Payload field | Required | Description |
| --- | --- | --- |
| `authority_id` | yes | UUID of the RA performing the revocation |
| `identity_id` | yes | UUID of the identity owning the recovery token |
| `recovery_token_id` | yes | UUID of the recovery token |

#### `Identity:SaveVettingTypeHint`

Save the vetting type hints of the acting RA (vetting type hint page).

| Payload field | Required | Description |
| --- | --- | --- |
| `identityId` | yes | UUID of the acting RA identity |
| `institution` | yes | Institution of the acting RA |
| `hints` | yes | Array of the selected vetting type hints |

### Configuration commands

#### `Configuration:AddRaLocation`

Create a new RA location (RA location page → "add", RAA only).

| Payload field | Required | Description |
| --- | --- | --- |
| `ra_location_id` | yes | UUID generated by the RA for the new location |
| `institution` | yes | RA institution the location belongs to |
| `ra_location_name` | yes | Name of the location |
| `location` | yes | Address/location description |
| `contact_information` | yes | Contact information of the location |

#### `Configuration:ChangeRaLocation`

Update an existing RA location (RA location edit page → "save", RAA only).

| Payload field | Required | Description |
| --- | --- | --- |
| `ra_location_id` | yes | UUID of the RA location |
| `institution` | yes | RA institution the location belongs to |
| `ra_location_name` | yes | Name of the location |
| `location` | yes | Address/location description |
| `contact_information` | yes | Contact information of the location |

#### `Configuration:RemoveRaLocation`

Remove an RA location (RA location page → "remove", RAA only).

| Payload field | Required | Description |
| --- | --- | --- |
| `institution` | yes | RA institution the location belongs to |
| `ra_location_id` | yes | UUID of the RA location |

## Out of scope: other API calls made by the RA

The RA also talks to other Stepup components, which are not Middleware APIs:

- `POST api/verify-yubikey` on the **Gateway** — used to verify a Yubikey OTP during vetting,
  with body `{ "requester": { "institution": "...", "identity": "..." }, "otp": { "value": "..." } }`.
- GSSP (eIDAS) verification — used during on-premises vetting of GSSP second factors.
- SMS service — used to send and verify SMS challenges during vetting.
