# Regarding test data
During development and test it is important to have representable data in the event stream. Adding this data via
SelfService and RA is a tedious task to say the least. To simplify this procedure, but to ensure a valid event stream,
console commands where created to bootstrap identities and second factor tokens. This readme will instruct the reader 
how to use these commands

## Bootstrap an identity

**Required arguments**

In order of appearance:
1. NameID: Example: `urn:collab:person:institution-b.example.com:joe-b1`
2. Institution: the institution identifier, should be a whitelisted institution known in your StepUp installation. Example: `institution-b.example.com` 
3. Common name: Example: `Joe Be-one`
4. Email
5. Preferred locale: Example `nl_NL` or `en_GB` 
5. Actor ID: The identity id of the actor that is adding the user (Uuid found in identity projection). Example: `112e8d3e-b748-416f-9501-eda1eac0daad` 

**Example usage**

```bash
$ app/console middleware:bootstrap:identity urn:collab:person:institution-b:joe-beone institution-b.example.com "Joe Beone" "joe@institution-b.co.uk" en_GB db9b8bdf-720c-44ba-a4c4-154953e45f14
Adding an identity named: Joe Beone
Creating a new identity
Successfully created identity with UUID 16c40d6b-9808-429f-b906-30655dd74429

```

## Bootstrap a SMS second factor token

**Required arguments**

In order of appearance:
1. NameID: Example: `urn:collab:person:institution-b.example.com:joe-b1`
2. Institution: the institution identifier, should be a whitelisted institution known in your StepUp installation. Example: `institution-b.example.com`
3. Token identifier: phone number formatted like `+31 (0) 612345678`
4. Token state: allowed states: `unverified`, `verified` or `vetted` 
5. Actor ID: The identity id of the actor that is adding the user (Uuid found in identity projection). Example: `112e8d3e-b748-416f-9501-eda1eac0daad` 

**Example usage**

```bash
$ app/console middleware:bootstrap:sms urn:collab:person:institution-b:joe-beone institution-b.example.com "+31 (0) 612345678" vetted 'db9b8bdf-720c-44ba-a4c4-154953e45f14'
Adding a vetted SMS token for Joe Beone
Creating an unverified SMS token
Creating a verified SMS token
Vetting the verified SMS token
Successfully created identity with UUID 16c40d6b-9808-429f-b906-30655dd74429 and vetted second factor with UUID 29c5204e-604d-4975-ab14-7706643f88b9
```