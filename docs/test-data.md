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
$ 
```