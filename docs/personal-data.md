# Personal Data in Stepup-Middleware

See https://github.com/OpenConext/Stepup-Deploy/wiki/Personal-Data for an overview of all personal data in Stepup.

The Stepup-Middleware uses [Event Sourcing](https://github.com/OpenConext/Stepup-Deploy/wiki/Technical-Design#event-sourcing). This means that there is an `event_steam` table in the `middleware` database that contains a history of all changes to the data that is managed by the middleware component. When an event is added the projections are updated. Projections are tables in the middleware and gateway databases that are created and updated from the information in the event stream.

As part of the effort to implement the [Right to be Forgotton](https://github.com/OpenConext/Stepup-Deploy/wiki/Right-to-be-forgotten) a mechanism was implemented whereby sensitive data is not stored in the event_steam table, but is stored in a separate `event_steam_sensitive_data` table in the `middleware` database. For each event containing sensitive data there is a row in the event_steam_sensitive_data table containing the sensitive data. This allows the linked data in `event_steam_sensitive_data` to be removed, without changing or removing the event in the `event_steam`. The following data types are stored in the `event_steam_sensitive_data`, and not in the `event_stream`:
- *commonName* - The common name attribute as received from the remote IdP. This is the full name of the user
- *email* - The email attribute as received from the remote IdP. This is the email address of the user
- *secondFactorIdentifier* - The identifier of the second factor. For SMS this is the mobile phone number, for Yubikey this is the Yubikey ID, for GSSPs (like e.g. Tiqr) this is the Subject NameID that the GSSP returns during registration. For Tiqr this is the Tiqr account name.
- secondFactorType - The type of second factor (sms, yubikey, tiqr)
- *documentNumber* - What the RA entered in the document number field during registration. The RA is instructed to enter the last 6 digits of the document number of the ID (i.e. passport or drivers license) of the user.

The data in the `event_stream` and `event_steam_sensitive_data` is replicated in the projection tables. These tables reflect the current state of the system, whereas the event_stream can be used to reconstruct all previous states. When a user is deleted, the projections need to be updated as well. The current implementation uses the [IdentityForgottenEvent](../src/Surfnet/Stepup/Identity/Event/IdentityForgottenEvent.php) to accomplish this. Because no new information is added to the projection tables compared to what is stored in the event_stream, we do not further describe the data that the projections contain. Instead we describe the data that is stored in the events.

All the event types that can be present in the `event_steam` are defined in [events](../config/packages/events.yaml). Two different namespaces are used for Events:
- [Surfnet\\Stepup\\Configuration\\Event\\](../src/Surfnet/Stepup/Configuration/Event/)
- [Surfnet\\Stepup\\Identity\\Event](../src/Surfnet/Stepup/Identity/Event/)

Each event contains metadata. Data that we think should be considered "personal data" is *emphasised*.

| Data type |  Description |
|:----------|:---|
| event_type | PHP classname of the event |
| recorded_on | timestamp of when event was created |
| *uuid* | Aggregate GUID genegated by Stepup. It is used internally in Stepup only. It identifies a set or related events. For identity events this is the identity_id. |
| playhead | ID of an event in a aggegate |
| *actorId* | the identity_id of the user that initiated the action |
| *actorInstitution* | the institution identifier (i.e. schacHomeOrganization) of the actor |

Below a list of all the events in Stepup, together with their data types. The data types that are stored in the `event_steam_sensitive_data` table are prefixed with "Forgettable:"

## Data types

These are all the datatypes that are used in the events (i.e. the `payload`). Data that we think should be considered "personal data" is *emphasised*.

| Data type                     | In sensitive data stream | Description                                                                                                                                          |
|:------------------------------|:-------------------------|:-----------------------------------------------------------------------------------------------------------------------------------------------------|
| *authority_id*                |                          | Used in revocation events. The identity_id of the user doing the revocation.                                                                         |
| *contact_information*         |                          | Contact information entered by an RA(A)                                                                                                              |
| email_verification_nonce      |                          | The nonce that was sent to the user to verify the email address during registration                                                                  |
| email_verification_required   |                          | Whether email verification is required (deprecated)                                                                                                  |
| email_verification_window     |                          | Start and end times of the validity of the email_verification_nonce                                                                                  |
| *identity_id*                 |                          | GUID. This is the unique identifier of an identity in Stepup. It is used internally in Stepup only.                                                  |
| *identity_institution*        |                          | The identifier of the institution that the user belongs to. This is the value of the schacHomeOrganization attribute as received from the remote IdP |
| institution                   |                          | The identifier of the institution to which the event applies                                                                                         |
| *location*                    |                          | Contact location entered by an RA(A)                                                                                                                 |
| *name_id*                     |                          | The nameID in the Subject of the Assertion as received from the remote IdP                                                                           |
| preferred_locale              |                          | The locate of the user                                                                                                                               |
| *registration_authority_role* |                          | Role identifier (1=RA or 2=RAA)                                                                                                                      |
| registration_code             |                          | The registration code                                                                                                                                |
| registration_requested_at     |                          | Timestamp at which registration was requested                                                                                                        |
| second_factor_id              |                          | UID of the second factor method                                                                                                                      |
| *second_factor_identifier*    | &#9745;                  | GUID. Unique identifier of the second factor                                                                                                         |
| *second_factor_type*          | &#9745;                  | The type of second factor (e.g. tiqr, yubikey, sms)                                                                                                  |
| *email*                       | &#9745;                  | The email of the user                                                                                                                                |
| *vetting_type*                | &#9745;                  | Registration type used at the RA                                                                                                                     |

## Identity Events

A list of all the [Identity events]((../src/Surfnet/Stepup/Identity/Event/) in stepup and the data that these events store in the `payload`.

[~~AppointedAsRaaEvent~~](../src/Surfnet/Stepup/Identity/Event/AppointedAsRaaEvent.php)  
Status: deprecated  
Superseded by: [AppointedAsRaaForInstitutionEvent](../src/Surfnet/Stepup/Identity/Event/AppointedAsRaaForInstitutionEvent.php)
- identity_id
- institution
- name_id

[AppointedAsRaaForInstitutionEvent](../src/Surfnet/Stepup/Identity/Event/AppointedAsRaaForInstitutionEvent.php)
- identity_id
- institution
- name_id
- ra_institution

[~~AppointedAsRaEvent~~](../src/Surfnet/Stepup/Identity/Event/AppointedAsRaEvent.php)  
Status: deprecated  
Superseded by: [AppointedAsRaForInstitutionEvent](../src/Surfnet/Stepup/Identity/Event/AppointedAsRaForInstitutionEvent.php)
- identity_id
- institution
- name_id

[AppointedAsRaForInstitutionEvent](../src/Surfnet/Stepup/Identity/Event/AppointedAsRaForInstitutionEvent.php)
- identity_id
- institution
- name_id
- ra_institution

[CompliedWithUnverifiedSecondFactorRevocationEvent](../src/Surfnet/Stepup/Identity/Event/CompliedWithUnverifiedSecondFactorRevocationEvent.php), [CompliedWithVerifiedSecondFactorRevocationEvent](../src/Surfnet/Stepup/Identity/Event/CompliedWithVerifiedSecondFactorRevocationEvent.php), [CompliedWithVerifiedSecondFactorRevocationEvent](../src/Surfnet/Stepup/Identity/Event/CompliedWithVerifiedSecondFactorRevocationEvent.php) and [CompliedWithVettedSecondFactorRevocationEvent](../src/Surfnet/Stepup/Identity/Event/CompliedWithVettedSecondFactorRevocationEvent.php) (all derived from [CompliedWithRevocationEvent](../src/Surfnet/Stepup/Identity/Event/CompliedWithRevocationEvent.php))
- identity_id
- identity_institution
- second_factor_id
- second_factor_type
- authority_id
- Forgettable: second_factor_identifier

[EmailVerifiedEvent](../src/Surfnet/Stepup/Identity/Event/EmailVerifiedEvent.php)
- identity_id
- identity_institution
- second_factor_id
- second_factor_type
- registration_requested_at
- registration_code
- preferred_locale
- Forgettable: email
- Forgettable: common_name
- Forgettable: second_factor_identifier

[GssfPossessionProvenAndVerifiedEvent](../src/Surfnet/Stepup/Identity/Event/GssfPossessionProvenAndVerifiedEvent.php)
- identity_id
- identity_institution
- second_factor_id
- stepup_provider (is second_factor_type)
- registration_requested_at
- registration_code
- Forgettable: gssfId (is second_factor_identifier)
- Forgettable: email
- Forgettable: common_name

[GssfPossessionProvenEvent](../src/Surfnet/Stepup/Identity/Event/GssfPossessionProvenEvent.php)
- identity_id
- identity_institution
- second_factor_id
- stepup_provider
- email_verification_required
- email_verification_window
- email_verification_nonce
- preferred_locale
- Forgettable: gssfId (is second_factor_identifier)
- Forgettable: email
- Forgettable: common_name

[~~IdentityAccreditedAsRaaEvent~~](../src/Surfnet/Stepup/Identity/Event/IdentityAccreditedAsRaaEvent.php)  
Status: deprecated  
Superseded by: [IdentityAccreditedAsRaaForInstitutionEvent](../src/Surfnet/Stepup/Identity/Event/IdentityAccreditedAsRaaForInstitutionEvent.php)
- identity_id
- name_id
- institution
- registration_authority_role
- location
- contact_information

[IdentityAccreditedAsRaaForInstitutionEvent](../src/Surfnet/Stepup/Identity/Event/IdentityAccreditedAsRaaForInstitutionEvent.php)
- identity_id
- name_id
- institution
- registration_authority_ro
- location
- contact_information
- ra_institution

[~~IdentityAccreditedAsRaEvent~~](../src/Surfnet/Stepup/Identity/Event/IdentityAccreditedAsRaEvent.php)  
Status: deprecated  
Superseded by: [IdentityAccreditedAsRaForInstitutionEvent](../src/Surfnet/Stepup/Identity/Event/IdentityAccreditedAsRaForInstitutionEvent.php)
- identity_id
- name_id
- institution
- registration_authority_role
- location
- contact_information

[IdentityCreatedEvent](../src/Surfnet/Stepup/Identity/Event/IdentityCreatedEvent.php)
- id
- institution
- name_id
- preferred_locale
- Forgettable: email
- Forgettable: common_name

[IdentityEmailChangedEvent](../src/Surfnet/Stepup/Identity/Event/IdentityEmailChangedEvent.php)
- id
- institution
- Forgettable: email

[IdentityForgottenEvent](../src/Surfnet/Stepup/Identity/Event/IdentityForgottenEvent.php)
- identity_id
- institution

[IdentityRenamedEvent](../src/Surfnet/Stepup/Identity/Event/IdentityRenamedEvent.php)
- id
- institution
- Forgettable: common_name

[InstitutionsAddedToWhitelistEvent](../src/Surfnet/Stepup/Identity/Event/InstitutionsAddedToWhitelistEvent.php)
- added_institutions

[InstitutionsRemovedFromWhitelistEvent](../src/Surfnet/Stepup/Identity/Event/InstitutionsRemovedFromWhitelistEvent.php)
- removed_institutions

[LocalePreferenceExpressedEvent](../src/Surfnet/Stepup/Identity/Event/LocalePreferenceExpressedEvent.php)
- id
- institution
- preferred_locale

[PhonePossessionProvenAndVerifiedEvent](../src/Surfnet/Stepup/Identity/Event/PhonePossessionProvenAndVerifiedEvent.php)
- identity_id
- identity_institution
- second_factor_id
- registration_requested_at
- registration_code
- Forgettable: phoneNumber (is second_factor_identifier)
- Forgettable: email
- Forgettable: common_name

[PhonePossessionProvenEvent](../src/Surfnet/Stepup/Identity/Event/PhonePossessionProvenEvent.php)
- identity_id
- identity_institution
- second_factor_id
- email_verification_required
- email_verification_window
- email_verification_nonce
- preferred_locale
- Forgettable: phoneNumber (is second_factor_identifier)
- Forgettable: email
- Forgettable: common_name

[~~RegistrationAuthorityInformationAmendedEvent~~](../src/Surfnet/Stepup/Identity/Event/RegistrationAuthorityInformationAmendedEvent.php)  
Status: deprecated  
Superseded by: [RegistrationAuthorityInformationAmendedEvent](../src/Surfnet/Stepup/Identity/Event/RegistrationAuthorityInformationAmendedEvent.php)
- identity_id
- institution
- name_id
- location
- contact_information

[RegistrationAuthorityInformationAmendedForInstitutionEvent](../src/Surfnet/Stepup/Identity/Event/RegistrationAuthorityInformationAmendedForInstitutionEvent.php) 
- identity_id
- institution
- name_id
- location
- contact_information
- ra_institution

[~~RegistrationAuthorityRetractedEvent~~](../src/Surfnet/Stepup/Identity/Event/RegistrationAuthorityRetractedEvent.php)  
Status: deprecated  
Superseded by: [RegistrationAuthorityRetractedForInstitutionEvent](../src/Surfnet/Stepup/Identity/Event/RegistrationAuthorityRetractedForInstitutionEvent.php)
- identity_id
- identity_institution
- name_id
- Forgettable: email
- Forgettable: common_name

[RegistrationAuthorityRetractedForInstitutionEvent](../src/Surfnet/Stepup/Identity/Event/RegistrationAuthorityRetractedForInstitutionEvent.php)
- identity_id
- identity_institution
- name_id
- ra_institution
- Forgettable: email
- Forgettable: common_name

[SecondFactorMigratedEvent](../src/Surfnet/Stepup/Identity/Event/SecondFactorMigratedEvent.php)
- identity_id
- source_institution
- target_name_id
- identity_institution
- second_factor_id
- new_second_factor_id
- second_factor_type
- preferred_locale
- Forgettable: second_factor_identifier
- Forgettable: common_name
- Forgettable: email

[SecondFactorMigratedToEvent](../src/Surfnet/Stepup/Identity/Event/SecondFactorMigratedToEvent.php)
- identity_id
- identity_institution
- second_factor_id
- target_institution
- target_second_factor_id
- second_factor_type
- Forgettable: second_factor_identifier

[UnverifiedSecondFactorRevokedEvent](../src/Surfnet/Stepup/Identity/Event/UnverifiedSecondFactorRevokedEvent.php), [VerifiedSecondFactorRevokedEvent](../src/Surfnet/Stepup/Identity/Event/VerifiedSecondFactorRevokedEvent.php) and [VettedSecondFactorRevokedEvent](../src/Surfnet/Stepup/Identity/Event/VettedSecondFactorRevokedEvent.php) (all derived from [SecondFactorRevokedEvent](../src/Surfnet/Stepup/Identity/Event/SecondFactorRevokedEvent.php))
- identity_id
- identity_institution
- second_factor_id
- second_factor_type
- Forgettable: second_factor_identifier

[SecondFactorVettedEvent](../src/Surfnet/Stepup/Identity/Event/SecondFactorVettedEvent.php)
- identity_id
- name_id
- identity_institution
- second_factor_id
- second_factor_type
- preferred_locale
- Forgettable: email
- Forgettable: common_name
- Forgettable: second_factor_identifier
- Forgettable: vetting_type (each vetting type has different data)

[SecondFactorVettedWithoutTokenProofOfPossession](../src/Surfnet/Stepup/Identity/Event/SecondFactorVettedEvent.php)
- identity_id
- name_id
- identity_institution
- second_factor_id
- second_factor_type
- preferred_locale
- Forgettable: email
- Forgettable: common_name
- Forgettable: U2F (is second_factor_identifier)
- Forgettable: vetting_type (each vetting type has different data)

[~~U2fDevicePossessionProvenAndVerifiedEvent~~](../src/Surfnet/Stepup/Identity/Event/U2fDevicePossessionProvenAndVerifiedEvent.php)  
Status: deprecated  
Removed: Built in U2F support is dropped from StepUp, this Event was not removed to support event replay
- identity_id
- identity_institution
- second_factor_id
- registration_requested_at
- preferred_locale
- second_factor_type
- Forgettable: U2F (is second_factor_identifier)
- Forgettable: email
- Forgettable: common_name

[~~U2fDevicePossessionProvenEvent~~](../src/Surfnet/Stepup/Identity/Event/U2fDevicePossessionProvenEvent.php)  
Status: deprecated  
Removed: Built in U2F support is dropped from StepUp, this Event was not removed to support event replay
- identity_id
- identity_institution
- second_factor_id
- preferred_locale
- second_factor_type
- Forgettable: U2F (second_factor_identifier)
- Forgettable: email
- Forgettable: common_name

[WhitelistCreatedEvent](../src/Surfnet/Stepup/Identity/Event/WhitelistCreatedEvent.php)
- whitelisted_institutions

[WhitelistReplacedEvent](../src/Surfnet/Stepup/Identity/Event/WhitelistReplacedEvent.php)
- whitelisted_institutions

[YubikeyPossessionProvenAndVerifiedEvent](../src/Surfnet/Stepup/Identity/Event/YubikeyPossessionProvenAndVerifiedEvent.php)
- identity_id
- identity_institution
- second_factor_id
- registration_requested_at
- registration_code
- Forgettable: yubikeyPublicId (is second_factor_identifier)
- Forgettable: email
- Forgettable: common_name

[YubikeyPossessionProvenEvent](../src/Surfnet/Stepup/Identity/Event/YubikeyPossessionProvenEvent.php)
- identity_id
- identity_institution
- second_factor_id
- email_verification_required
- email_verification_window
- email_verification_nonce
- preferred_locale
- Forgettable: yubikeyPublicId (is second_factor_identifier)
- Forgettable: email
- Forgettable: common_name

[YubikeySecondFactorBootstrappedEvent](../src/Surfnet/Stepup/Identity/Event/YubikeySecondFactorBootstrappedEvent.php)
- identity_id
- name_id
- identity_institution
- preferred_locale
- second_factor_id
- Forgettable: yubikeyPublicId (is second_factor_identifier)
- Forgettable: email
- Forgettable: common_name

## Configuration Events

The [configuration events](../src/Surfnet/Stepup/Configuration/Event/) in Stepup handle the the run time configuration of Stepup. With the exception of [SraaUpdatedEvent](../src/Surfnet/Stepup/Configuration/Event/SraaUpdatedEvent.php), which contains the *name_id* of the SRAA's, these events do not involve Personal Data
