# Middleware Management API

## Configuration

### Request
URL: `http://middleware.tld/management/configuration`
Method: POST
Request Body: The full request body of the configuration API is documented in a [separate section](MiddlewareConfiguration.md).

### Response
`200 OK`
```json
{
    "command": "d12cb994-5719-405a-9533-af1beef78ee3",
    "processed_by": "mw-dev.stepup.coin.surf.net"
    "applied_at": "2015-07-17T15:10:11+02:00"
}
```



## Whitelist

The institutions values in the whitelist are the values used in the SAML schacHomeOrganization attribute.

## Whitelist - Get Whitelist

### Request
URL: `http://middleware.tld/management/whitelist`
Method: GET
Request Parameters: None

### Response
`200 OK`
```json
{
    "institutions": [
        "surfnet.nl",
        "ibuildings.nl"
    ]
}
```


## Whitelist - Add Institutions to Whitelist

### Request
URL: `http://middleware.tld/management/whitelist/add`
Method: POST
Request Body:
```json
{
    "institutions": [
        "surfnet.nl",
        "ibuildings.nl"
    ]
}
```

### Response
`200 OK`
```json
{
    "status": "OK",
    "processed_by": "mw-dev.stepup.coin.surf.net"
    "applied_at": "2015-07-17T15:10:11+02:00"
}
```


## Whitelist - Remove Institutions from Whitelist

### Request
URL: `http://middleware.tld/management/whitelist/remove`
Method: POST
Request Body:
```json
{
    "institutions": [
        "surfnet.nl",
        "ibuildings.nl"
    ]
}
```

### Response
`200 OK`
```json
{
    "status": "OK",
    "processed_by": "mw-dev.stepup.coin.surf.net"
    "applied_at": "2015-07-17T15:10:11+02:00"
}
```


## Whitelist - Replace the full Whitelist

### Request
URL: `http://middleware.tld/management/whitelist/remove`
Method: POST
Request Body:
```json
{
    "institutions": [
        "surfnet.nl",
        "ibuildings.nl"
    ]
}
```

### Response
`200 OK`
```json
{
    "status": "OK",
    "processed_by": "mw-dev.stepup.coin.surf.net"
    "applied_at": "2015-07-17T15:10:11+02:00"
}
```



## Forget Identity

### Request
URL: `	http://middleware.tld/management/forget-identity`
Method: POST
Request Body:
```json
{
  "name_id": "2592ab2afb52eea9a61f5db90febd631966d49f5",
  "institution": "ibuildings.nl"
}
```

### Response
`200 OK`
```json
{
    "status": "OK",
    "processed_by": "mw-dev.stepup.coin.surf.net"
    "applied_at": "2015-07-17T15:10:11+02:00"
}
```
