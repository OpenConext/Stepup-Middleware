# Middleware APIs Commands

## Identity context

### AccreditIdentityCommand
- RA command

|**Fieldname**|**Required**|**Type**|**Remarks**|
|--|--|--|--|
|`identityId`|yes|`string`|UUID of the Identity|
|`institution`|yes|`string`|UUID of the Institution of the Identity|
|`role`|yes|`string`|The rolename the Identity is accredited with, possible options `[ra, raa]`|
|`contactInformation`|yes|`string`|Contact information of the Identity|
|`raInstitution`|yes|`string`|UUID of the RA Institution the Identity is accredited a role at.|

### AddToWhitelistCommand
- Management command

|**Fieldname**|**Required**|**Type**|**Remarks**|
|--|--|--|--|
|`institutionsToBeAdded`|yes|`array` of `string`|A list of Institutions to be added to the whitelist|

### AmendRegistrationAuthorityInformationCommand
- RA command

|**Fieldname**|**Required**|**Type**|**Remarks**|
|--|--|--|--|
|`identityId`|yes|`string`|UUID of the Identity|
|`contactInformation`|yes|`string`|Contact information of the Identity|
|`raInstitution`|yes|`string`|UUID of the RA Institution the Identity is amending information at.|

### AppointRoleCommand
- RA command

|**Fieldname**|**Required**|**Type**|**Remarks**|
|--|--|--|--|
|`identityId`|yes|`string`|UUID of the Identity|
|`role`|yes|`string`|The rolename the Identity is accredited with, possible options `[ra, raa]`|
|`raInstitution`|yes|`string`|UUID of the RA Institution the Identity is appointed a role at.|

### BootstrapIdentityWithYubikeySecondFactorCommand
Used to bootstrap SRAA users

- Admin command, fired from command line using Symfony console command

|**Fieldname**|**Required**|**Type**|**Remarks**|
|--|--|--|--|
|`identityId`|yes|`string`|UUID of the Identity|
|`nameId`|yes|`string`|The nameId of the Identity|
|`institution`|yes|`string`|UUID of the Institution of the Identity|
|`email`|yes|`string`, `email`|Email address of the Identity|
|`commanName`|yes|`string`|Common name of the Identity|
|`preferredLocale`|yes|`string`|Preferred locale of the Identity. Possible options `[nl, en]`|
|`secondFactorId`|yes|`string`|UUID of the second factor token of the Identity|
|`yubikeyPublicId`|yes|`string`|The public id of the yubikey the Identity is going to use|

### CreateIdentityCommand
- SelfService command

|**Fieldname**|**Required**|**Type**|**Remarks**|
|--|--|--|--|
|`id`|yes|`string`|UUID of the Identity|
|`nameId`|yes|`string`|The nameId of the Identity|
|`email`|yes|`string`, `email`|Email address of the Identity|
|`institution`|yes|`string`|UUID of the Institution of the Identity|
|`commanName`|yes|`string`|Common name of the Identity|
|`preferredLocale`|yes|`string`|Preferred locale of the Identity. Possible options `[nl, en]`|

### ExpressLocalePreferenceCommand
- SelfService command
- RA command

|**Fieldname**|**Required**|**Type**|**Remarks**|
|--|--|--|--|
|`identityId`|yes|`string`|UUID of the Identity|
|`preferredLocale`|yes|`string`|Preferred locale of the Identity. Possible options `[nl, en]`|

### ForgetIdentityCommand

- Deprovision command
- Management command

|**Fieldname**|**Required**|**Type**|**Remarks**|
|--|--|--|--|
|`nameId`|yes|`string`|NameId of the Identity to be forgotten|
|`institution`|yes|`string`|UUID of the Institution of the Identity|

### MigrateVettedSecondFactorCommand

- Admin command, fired from command line using Symfony console command

|**Fieldname**|**Required**|**Type**|**Remarks**|
|--|--|--|--|
|`sourceIdentityId`|yes|`string`|UUID of the Identity the tokens are copied from|
|`targetIdentityId`|yes|`string`|UUID of the Identity the tokens are copied to|
|`sourceSecondFactorId`|yes|`string`|The source second factor UUID that is to be moved|
|`targetSecondFactorId`|yes|`string`|The new destination second factor UUID|


### PromiseSafeStoreSecretTokenPossessionCommand
- SelfService command

|**Fieldname**|**Required**|**Type**|**Remarks**|
|--|--|--|--|
|`identityId`|yes|`string`|UUID of the Identity|
|`recoveryTokenId`|yes|`string`|The ID of the recovery code to create|
|`identityId`|yes|`string`|UUID of the Identity|
|`recoveryTokenType`|yes|`string`|The recovery token type, defaults to 'safe-store'|
|`secret`|yes|`string`|The unhashed safe-store password|
