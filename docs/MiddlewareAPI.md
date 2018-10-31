# Middleware APIs

## Standard Error Responses

| Response Code | Definition            | Used When | Response Format |
| ------------- | --------------------- | --------- | --------------- |  
| 400           | Bad Request           | the request can not be handled due to malformed syntax | `{ "errors": [ "detailed error message", "another detailed error message"] }` |
| 401           | Unauthorized          | The requester cannot be authenticated | |
| 403           | Forbidden             | The authenticated user is not authorized to perform the current action | `WWW-Authenticate` header |
| 404           | Unauthorized          | The requested resource cannot be found | |
| 409           | Conflict              | The request cannot be handled because of a conflict with the current state of the resource. As an example, this will be returned when trying to forget an Identity that is listed as (S)RA(A) | Response body contains information on what to do to resolve this conflict |
| 500           | Internal Server Error | The application encountered an unexpected condition which prevented it from fulfilling the request | If possible, the error that caused this is documented in the body. |

## Development Specific Error Response

| Response Code | Definition            | Used When | Response Format |
| ------------- | --------------------- | --------- | --------------- |  
| 501           | Not Implemented       | The requested resource/action has not yet been implemented | `{ "errors": ["Some descriptive message"] }` |


## Command API

### Request
URL: `http://middleware.tld/command/`
Method: POST
Basic Command Structure
```json
{
    "meta": {
        "actor": "John Doe"
        "actor_institution": "SURFnet"
    },
    "command": {
        "name": "Identity:CreateIdentity"
        "uuid":"d12cb994-5719-405a-9533-af1beef78ee3",
        "payload":{
            "id": "abb1b9f8-20c9-44a9-9694-176f00aaa618",
            "name_id": "29c41b84214b4fd5fb4d508e680fc921",
            "institution": "Example Orgbhkjglgiliyih.",
            "email": "foo@bar.com",
            "common_name": "Sjaak Trekhaak"
        }
    }
}
```


### Response
`200 OK`
```json
{
    "command": "d12cb994-5719-405a-9533-af1beef78ee3",
    "processed_by": "mw-dev.stepup.coin.surf.net"
}
```


## Identity & Second Factors

### Identities - Single Identity

#### Request
URL: `http://middleware.tld/identity/{identityId}`
Method: GET
Request parameters:
- identityId: UUIDv4 of the identity

#### Response
`200 OK`
```json
{
    "id": "c78240b1-612f-49b7-85b7-83eae5f63a85",
    "name_id": "39ba648867aa14a873339bb2a3031791ef319894",
    "institution": "Ibuildings",
    "email": "info@ibuildings.nl",
    "common_name": "SRAA Account",
    "preferred_locale": "en_GB"

}
```

### Identities - Identity Collection

#### Request
URL: `http://middleware.tld/identity/?insitution=Ibuildings{&NameID=}{&commonName=}{&email=}{&p=3}`
Method: GET
Request parameters:
- Institution: (required) string, the institution as scope determination
- NameID: (optional) string, the NameID to search (equality check)
- commonName: (optional) string, the commonName to match against
- email: (optional) string, the email to match against
- p: (optional, default 1) integer, the requested result page

#### Response
`200 OK`
```json
{
    "collection": {
        "total_items": 2,
        "page": 1,
        "page_size": 25
    },
    "items": [
        {
            "id": "8b5cdd14-74b1-43a2-a806-c171728b1bf1",
            "name_id": "2592ab2afb52eea9a61f5db90febd631966d49f5",
            "institution": "Ibuildings",
            "email": "info@ibuildings.nl",
            "common_name": "SMS Account",
            "preferred_locale": "nl_NL"
        },
        {
            "id": "c78240b1-612f-49b7-85b7-83eae5f63a85",
            "name_id": "39ba648867aa14a873339bb2a3031791ef319894",
            "institution": "Ibuildings",
            "email": "info@ibuildings.nl",
            "common_name": "SRAA Account",
            "preferred_locale": "en_GB"
        }
    ]
}
```


### Unverified Second Factors - Single Unverified Second Factor

#### Request
URL: `http://middleware.tld/unverified-second-factor/{secondFactorId}`
Method: GET
Request parameters:
- secondFactorId: (required) UUIDv4 of the second factor to get

#### Response
`200 OK`
```json
{
    "id": "4984057f-5952-4a82-a77f-44bc9cd62ce4",
    "type": "sms",
    "second_factor_identifier": "+31 (0) 610101010"
}
```


### Unverified Second Factors - Search Unverified Second Factors

#### Request
URL: `http://middleware.tld/unverified-second-factors?{identityId=}{&verificationNonce=}(&p=}`
Method: GET
Request parameters:
- IdentityId: (optional) UUIDv4 of the identity to search for
- verificationNonce: (optional) string, verification nonce to search for
- p: (optional, default 1) integer, the requested result page

#### Response
`200 OK`
```json
{
    "collection": {
        "total_items": 1,
        "page": 1,
        "page_size": 25
    },
    "items": [
        {
            "id": "4984057f-5952-4a82-a77f-44bc9cd62ce4",
            "type": "sms",
            "second_factor_identifier": "+31 (0) 610101010"
        }
    ]
}
```



### Verified Second Factor - Single Verified Second Factor

#### Request
URL: `http://middleware.tld/verified-second-factor/{secondFactorId}`
Method: GET
Request parameters:
- secondFactorId: (required) UUIDv4 of the second factor to get

#### Response
`200 OK`
```json
{
    "id": "4984057f-5952-4a82-a77f-44bc9cd62ce4",
    "type": "sms",
    "second_factor_identifier": "+31 (0) 610101010",
    "registration_code": "WCYC6MQH",
    "identity_id": "8b5cdd14-74b1-43a2-a806-c171728b1bf1",
    "institution": "Ibuildings",
    "common_name": "SMS Account"
}
```


### Verified Second Factor - Search Verified Second Factors

#### Request
URL: `http://middleware.tld/verified-second-factors?{actorInstitution}&{identityId=}{&secondFactorId=}{&registrationCode=}(&p=}`
Method: GET
Request parameters:
- actorInstitution: (required) string, the institution as scope determination
- actorId: (required) UUIDv4 of the actor. When provided, the actor id can be used to determine the actor role.
- IdentityId: (optional) UUIDv4 of the identity to search for
- secondFactorId: (optional) UUIDv4 of the second factor to search for
- registrationCode: (optional) string, registration code to search for
- p: (optional, default 1) integer, the requested result page

#### Response
`200 OK`
```json
{
    "collection": {
        "total_items": 1,
        "page": 1,
        "page_size": 25
    },
    "items": [
        {
            "id": "4984057f-5952-4a82-a77f-44bc9cd62ce4",
            "type": "sms",
            "second_factor_identifier": "+31 (0) 610101010",
            "registration_code": "WCYC6MQH",
            "identity_id": "8b5cdd14-74b1-43a2-a806-c171728b1bf1",
            "institution": "Ibuildings",
            "common_name": "SMS Account"
        }
    ]
}
```



### Vetted Second Factor - Single Vetted Second Factor

#### Request
URL: `http://middleware.tld/vetted-second-factor/{secondFactorId}`
Method: GET
Request parameters:
- secondFactorId: (required) UUIDv4 of the second factor to get

#### Response
`200 OK`
```json
{
    "id": "c732d0ac-9f61-4ae1-924e-40d5172fca86",
    "type": "yubikey",
    "second_factor_identifier": "ccccccbtbhnf"
}
```


### Vetted Second Factor - Search Vetted Second Factors

#### Request
URL: `http://middleware.tld/vetted-second-factors?{identityId=}(&p=}`
Method: GET
Request parameters:
- identityId: (optional) UUIDv4 of the identity to search for
- p: (optional, default 1) integer, the requested result page

#### Response
`200 OK`
```json
{
    "collection": {
        "total_items": 1,
        "page": 1,
        "page_size": 25
    },
    "items": [
        {
            "id": "c732d0ac-9f61-4ae1-924e-40d5172fca86",
            "type": "yubikey",
            "second_factor_identifier": "ccccccbtbhnf"
        }
    ]
}
```



## Registration Authorities

### SRAA - Single SRAA

#### Request
URL: `http://middleware.tld/sraa/{nameId}`
Method: GET
Request parameters:
- nameId: the NameID that might be an SRAA

#### Response
`200 OK`
```json
{
    "name_id": "the-name-id"
}
```



### Registration Authority Credentials - Single Registration Authority

#### Request
URL: `http://middleware.tld/registration-authority/{identityId}`
Method: GET
Request parameters:
- identityId: UUIDv4 of the Identity

#### Response
`200 OK`
```json
{
    "id": "c78240b1-612f-49b7-85b7-83eae5f63a85",
    "attributes": {
        "institution": "Ibuildings",
        "common_name": "SRAA Account",
        "location": "Goeman Borgesiuslaat 77, Utrecht",
        "contact_information": "zie ibuildings.nl",
        "is_raa": true,
        "is_sraa": true
    }
}
```


### Registration Authority Credentials - Multiple Registration Authorities

#### Request
URL: `http://middleware.tld/registration-authority?institution=`
Method: GET
Request parameters:
- institution: (required) string, the institution as scope determination

#### Response
`200 OK`
```json
{
    "id": "c78240b1-612f-49b7-85b7-83eae5f63a85",
    "attributes": {
        "institution": "Ibuildings",
        "common_name": "SRAA Account",
        "location": "Goeman Borgesiuslaat 77, Utrecht",
        "contact_information": "zie ibuildings.nl",
        "is_raa": true,
        "is_sraa": true
    }
}
```



### Registration Authority Listings - Single RaListing

#### Request
URL: `http://middleware.tld/ra-listing/{identityId}`
Method: GET
Request parameters:
- identityId: UUIDv4 of the Identity of which to retrieve the possible RaListing

#### Response
`200 OK`
```json
{
    "identity_id": "c78240b1-612f-49b7-85b7-83eae5f63a85",
    "institution": "Ibuildings",
    "common_name": "SRAA Account",
    "email": "info@ibuildings.nl",
    "role": "ra",
    "location": "Goeman Borgesiuslaat 77, Utrecht",
    "contact_information": "zie ibuildings.nl"
}
```


### Registration Authority Listings - Search RaListings

#### Request
URL: `http://middleware.tld/ra-listing?institution={&orderBy=commonName}{&orderDirection=asc}`
Method: GET
Request parameters:
- institution: (optional) string, the institution as scope determination
- identityId: (optional) string, the identity to load the RA listing items for (from FGA and onwards there can be more than one entry per identity).
- orderBy: (optional, default `commonName`) string, sorting column; only `commonName` is allowed
- orderDirection: (optional, default `asc`) string, sorting direction; only `asc` or `desc` allowed.

#### Response
`200 OK`
```json
{
    "collection": {
        "total_items": 1,
        "page": 1,
        "page_size": 1000
    },
    "items": [
        {
            "identity_id": "c78240b1-612f-49b7-85b7-83eae5f63a85",
            "institution": "Ibuildings",
            "common_name": "SRAA Account",
            "email": "info@ibuildings.nl",
            "role": "ra",
            "location": "Ergens",
            "contact_information": "Roepen"
        }
    ]
}
```



### Registration Authority Candidate - Single RaCandidate

#### Request
URL: `http://middleware.tld/ra-candidate/{identityId}`
Method: GET
Request parameters:
- identityId: (required) UUIDv4 of the Identity of which to retrieve the possible RaCandidate

#### Response
`200 OK`
```json
{
    "identity_id": "8b5cdd14-74b1-43a2-a806-c171728b1bf1",
    "institution": "Ibuildings",
    "common_name": "SMS Account",
    "email": "info@ibuildings.nl",
    "name_id": "2592ab2afb52eea9a61f5db90febd631966d49f5"
}
```


### Registration Authority Candidate - Search RaCandidate

#### Request
URL: `http://middleware.tld/ra-candidate?institution={&commonName=}{&email=}(&p=}`
Method: GET
Request parameters:
- institution: (required) string, the institution as scope determination
- commonName: (optional) string, the commonName to match against
- email: (optional) string, the email to match against
- p: (optional, default 1) integer, the requested result page

#### Response
`200 OK`
```json
{
    "collection": {
        "total_items": 1,
        "page": 1,
        "page_size": 25
    },
    "items": [
        {
            "identity_id": "8b5cdd14-74b1-43a2-a806-c171728b1bf1",
            "institution": "Ibuildings",
            "common_name": "SMS Account",
            "email": "info@ibuildings.nl",
            "name_id": "2592ab2afb52eea9a61f5db90febd631966d49f5"
        }
    ]
}
```

### Registration Authority Candidate - Search second factors

#### Request
URL: `http://middleware.tld/ra-second-factors?institution={&name=}{&type=}{&secondFactorId=}{&email=}{&status=}{&p=}`
Method: GET
Request parameters:
- actorInstitution: (required) string, the institution as scope determination
- name: (optional) string, the second factor name to match against
- type: (optional) string, the type to match against
- secondFactorId: (optional) string, the secondFactorId to match against
- email: (optional) string, the email to match against
- institution: (optional) string, the institution to match against
- status: (optional) string, the status to match against
- p: (optional, default 1) integer, the requested result page
- orderBy: (optional) string, sorting column; possible values: name, type, secondFactorId, email, institution, status
- orderDirection: (optional, default desc) string, sorting direction; only asc or desc allowed.

#### Response
`200 OK`
```json
{
    "collection": {
        "total_items": 1,
        "page": 1,
        "page_size": 25
    },
    "items": [
        {
            "id": "45b8220b-0ac2-43da-88ce-ecd0d1e9ce2f",
            "type": "yubikey",
            "second_factor_id": "02513949",
            "status": "unverified",
            "identity_id": "8fd69a41-0d37-4365-9e46-a6a4a70572af",
            "name": "Yubi",
            "document_number": null,
            "email": "info@ibuildings.nl",
            "actorInstitution": "Ibuildings",
            "institution": "SURFnet"
        }
    ]
}
```

### Registration Authority Candidate - Search second factors for export

#### Request
URL: `http://middleware.tld/ra-second-factors-export?actorInstitution={&name=}{&type=}{&secondFactorId=}{&email=}{&status=}
Method: GET
Request parameters:
- actorInstitution: (required) string, the institution as scope determination
- name: (optional) string, the second factor name to match against
- type: (optional) string, the type to match against
- secondFactorId: (optional) string, the secondFactorId to match against
- email: (optional) string, the email to match against
- institution: (optional) string, the institution to match against
- status: (optional) string, the status to match against

#### Response
`200 OK`
```json
[
    {
        "id": "45b8220b-0ac2-43da-88ce-ecd0d1e9ce2f",
        "type": "yubikey",
        "second_factor_id": "02513949",
        "status": "unverified",
        "identity_id": "8fd69a41-0d37-4365-9e46-a6a4a70572af",
        "name": "Yubi",
        "document_number": null,
        "email": "info@ibuildings.nl",
        "actorInstitution": "Ibuildings",
        "institution": "SURFnet"
    }
]
```

## AuditLog

### Query Second Factor AuditLog

#### Request
URL: `http://middleware.tld/audit-log/second-factors?institution=&identityId=&orderBy=recordedOn&orderDirection=asc{&p=}`
Method: GET
Request parameters:
- institution: (required) string, the institution as scope determination
- identityId: (required) UUIDv4 of the identity to search for
- orderBy: (optional, default recordedOn) string, sorting column; possible values: secondFactorId, secondFactorType, event, recordedOn, actorId, actorCommonName, actorInstitution
- orderDirection: (optional, default asc) string, sorting direction; only asc or desc allowed.
- p: (optional, default 1) integer, the requested result page

#### Response
`200 OK`
```json
{
    "collection": {
        "total_items": 3,
        "page": 1,
        "page_size": 25
    },
    "items": [
        {
            "actor_id": "8b5cdd14-74b1-43a2-a806-c171728b1bf1",
            "actor_institution": "Ibuildings",
            "actor_common_name": "SMS Account",
            "identity_id": "8b5cdd14-74b1-43a2-a806-c171728b1bf1",
            "identity_institution": "Ibuildings",
            "second_factor_id": "4984057f-5952-4a82-a77f-44bc9cd62ce4",
            "second_factor_type": "sms",
            "second_factor_identifier": "+31 (0) 610101010",
            "action": "possession_proven",
            "recorded_on": "2015-06-17T09:44:04+02:00"
        },
        {
            "actor_id": "8b5cdd14-74b1-43a2-a806-c171728b1bf1",
            "actor_institution": "Ibuildings",
            "actor_common_name": "SMS Account",
            "identity_id": "8b5cdd14-74b1-43a2-a806-c171728b1bf1",
            "identity_institution": "Ibuildings",
            "second_factor_id": "4984057f-5952-4a82-a77f-44bc9cd62ce4",
            "second_factor_type": "sms",
            "second_factor_identifier": "+31 (0) 610101010",
            "action": "email_verified",
            "recorded_on": "2015-06-17T10:06:08+02:00"
        },
        {
            "actor_id": "c78240b1-612f-49b7-85b7-83eae5f63a85",
            "actor_institution": "Ibuildings",
            "actor_common_name": "SRAA Account",
            "identity_id": "8b5cdd14-74b1-43a2-a806-c171728b1bf1",
            "identity_institution": "Ibuildings",
            "second_factor_id": "4984057f-5952-4a82-a77f-44bc9cd62ce4",
            "second_factor_type": "sms",
            "second_factor_identifier": "+31 (0) 610101010",
            "action": "vetted",
            "recorded_on": "2015-06-17T12:02:22+02:00"
        }
    ]
}
```



## Institutions

### Get all known Institutions

#### Request
URL: `http://middleware.tld/institution-listing`
Method: GET
Request parameters: None

#### Response
`200 OK`
```json
[
    {
        "name": "SURFnet"
    },
    {
        "name": "Ibuildings"
    }
]
```
