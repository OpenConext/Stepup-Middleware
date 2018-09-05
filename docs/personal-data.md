# Personal Data in Stepup-Middleware

See https://github.com/OpenConext/Stepup-Deploy/wiki/Personal-Data for an overview of all personal data in Stepup.

The Stepup-Middleware uses [Event Sourcing](https://github.com/OpenConext/Stepup-Deploy/wiki/Technical-Design#event-sourcing). This means that there is an `event_steam` table in the `middleware` database that contains a history of all changes to the data that is managed by the middleware component. When an event is added the projections are updated. Projections are tables in the middleware and gateway databases that are created and updated from the information in the event stream.

As part of the effort to implement the [Right to be Forgotton](https://github.com/OpenConext/Stepup-Deploy/wiki/Right-to-be-forgotten) a mechanism was implemented whereby sensitive data is not stored in the event_steam table, but is stored in a separate `event_steam_sensitive_data` table in the `middleware` database. For each event containing sensitive data there is a row in the event_steam_sensitive_data table containing the sensitive data. This allows the linked data in `event_steam_sensitive_data` to be removed, without changing or removing the event in the `event_steam`. The following data types are stored in the `event_steam_sensitive_data`, and not in the `event_stream`:
- *commonName* - The common name attribute as received from the remote IdP. This is the full name of the user
- *email* - The email attribute as received from the remote IdP. This is the email address of the user
- *secondFactorIdentifier* - The identifier of the second factor. For SMS this is the mobile phone number, for Yubikey this is the Yubikey ID, for GSSPs (like e.g. Tiqr) this is the Subject NameID that the GSSP returns during registration. For Tiqr this is the Tiqr account name.
- secondFactorType - The type of second factor (sms, yubikey, tiqr, u2f)
- *documentNumber* - What the RA entered in the document number field during registration. The RA is instructed to enter the last 6 digits of the document number of the ID (i.e. passport or drivers license) of the user.

The data in the `event_stream` and `event_steam_sensitive_data` is replicated in the projection tables. These tables reflect the current state of the system, whereas the event_stream can be used to reconstruct all previous states. When a user is deleted, the projections need to be updated as well. The current implementation uses the [IdentityForgottenEvent](../src/Surfnet/Stepup/Identity/Event/IdentityForgottenEvent.php) to accomplish this. Because no new information is added to the projection tables compared to what is stored in the event_stream, we do not further describe the data that the projections contain. Instead we describe the data that is stored in the events.

All the event types that can be present in the `event_steam` are defined in [events](../app/config/events.yml). Two different namespaces are used for Events:
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

| Data type                                                                                 | Description |
|:------------------------------------------------------------------------------------------|:------------|
| *authority_id* | Used in revocation events. The identity_id of the user doing the revocation. |
| *contact_information* | contact information entered by an RA(A) |
| *email_verification_nonce* | the nonce that was sent to the user to verify the email address during registration |
| email_verification_required | whether email verification is required (deprecated) |
| email_verification_window | start and end times of the validity of the email_verification_nonce |
| *identity_id* | GUID. This is the unique identifier of an identity in Stepup. It is used internally in Stepup only. |
| *identity_institution* | the identifier of the institution that the user belongs to. This is the value of the schacHomeOrganization attribute as received from the remote IdP |
| institution | the identifier of the institution to which the event applies |
| *location* | contact location entered by an RA(A) |
| *name_id* | The nameID in the Subject of the Assertion as received from the remote IdP |
| preferred_locale | The locate of the user |
| *registration_authority_role* | role identifier (1=RA or 2=RAA) |
| *registration_code* | the registration code |
| registration_requested_at | timestamp at which registration was requested |
| *second_factor_id* | GUID. Unique identifier of the second factor |
| *second_factor_type* | The type of second factor (e.g. tiqr, yubikey, sms, u2f) |

## Identity Events

A list of all the [Identity events]((../src/Surfnet/Stepup/Identity/Event/) in stepup and the data that these events store in the `payload`.

[AppointedAsRaaEvent](../src/Surfnet/Stepup/Identity/Event/AppointedAsRaaEvent.php)
- identity_id
- institution
- name_id

[AppointedAsRaEvent](../src/Surfnet/Stepup/Identity/Event/AppointedAsRaEvent.php)
- identity_id
- institution
- name_id

[CompliedWithUnverifiedSecondFactorRevocationEvent](../src/Surfnet/Stepup/Identity/Event/CompliedWithUnverifiedSecondFactorRevocationEvent.php), [CompliedWithVerifiedSecondFactorRevocationEvent](../src/Surfnet/Stepup/Identity/Event/CompliedWithVerifiedSecondFactorRevocationEvent.php), [CompliedWithVerifiedSecondFactorRevocationEvent](../src/Surfnet/Stepup/Identity/Event/CompliedWithVerifiedSecondFactorRevocationEvent.php) and [CompliedWithVettedSecondFactorRevocationEvent](../src/Surfnet/Stepup/Identity/Event/CompliedWithVettedSecondFactorRevocationEvent.php) (all derived from [CompliedWithRevocationEvent](../src/Surfnet/Stepup/Identity/Event/CompliedWithRevocationEvent.php))
- identity_id
- identity_institution
- second_factor_id
- second_factor_type
- authority_id
- Forgattable: secondFactorIdentifier

[EmailVerifiedEvent](../src/Surfnet/Stepup/Identity/Event/EmailVerifiedEvent.php)
- identity_id
- identity_institution
- second_factor_id
- second_factor_type
- registration_requested_at
- registration_code
- preferred_locale
- Forgettable: email
- Forgettable: commonName
- Forgettable: secondFactorIdentifier

[GssfPossessionProvenAndVerifiedEvent](../src/Surfnet/Stepup/Identity/Event/GssfPossessionProvenAndVerifiedEvent.php)
- identity_id
- identity_institution
- second_factor_id
- stepup_provider (is second_factor_type)
- registration_requested_at
- registration_code
- Forgettable: gssfId (is secondFactorIdentifier)
- Forgettable: email
- Forgettable: commonName

[GssfPossessionProvenEvent](../src/Surfnet/Stepup/Identity/Event/GssfPossessionProvenEvent.php)
- identity_id
- identity_institution
- second_factor_id
- stepup_provider
- email_verification_required
- email_verification_window
- email_verification_nonce
- preferred_locale
- Forgettable: gssfId (is secondFactorIdentifier)
- Forgettable: email
- Forgettable: commonName

[IdentityAccreditedAsRaaEvent](../src/Surfnet/Stepup/Identity/Event/IdentityAccreditedAsRaaEvent.php)
- identity_id
- name_id
- institution
- registration_authority_role
- location
- contact_information

[IdentityAccreditedAsRaEvent](../src/Surfnet/Stepup/Identity/Event/IdentityAccreditedAsRaEvent.php)
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
- Forgettable: commonName

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
- Forgettable: commonName

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
- Forgettable: phoneNumber (is secondFactorIdentifier)
- Forgettable: email
- Forgettable: commonName

[PhonePossessionProvenEvent](../src/Surfnet/Stepup/Identity/Event/PhonePossessionProvenEvent.php)
- identity_id
- identity_institution
- second_factor_id
- email_verification_required
- email_verification_window
- email_verification_nonce
- preferred_locale
- Forgettable: phoneNumber (is secondFactorIdentifier)
- Forgettable: email
- Forgettable: commonName

[RegistrationAuthorityInformationAmendedEvent](../src/Surfnet/Stepup/Identity/Event/RegistrationAuthorityInformationAmendedEvent.php)
- identity_id
- institution
- name_id
- location
- contact_information

[RegistrationAuthorityRetractedEvent](../src/Surfnet/Stepup/Identity/Event/RegistrationAuthorityRetractedEvent.php)
- identity_id
- identity_institution
- name_id
- Forgettable: email
- Forgettable: commonName

[UnverifiedSecondFactorRevokedEvent](../src/Surfnet/Stepup/Identity/Event/UnverifiedSecondFactorRevokedEvent.php), [VerifiedSecondFactorRevokedEvent](../src/Surfnet/Stepup/Identity/Event/VerifiedSecondFactorRevokedEvent.php) and [VettedSecondFactorRevokedEvent](../src/Surfnet/Stepup/Identity/Event/VettedSecondFactorRevokedEvent.php) (all derived from [SecondFactorRevokedEvent](../src/Surfnet/Stepup/Identity/Event/SecondFactorRevokedEvent.php))
- identity_id
- identity_institution
- second_factor_id
- second_factor_type
- Forgettable: secondFactorIdentifier

[SecondFactorVettedEvent](../src/Surfnet/Stepup/Identity/Event/SecondFactorVettedEvent.php)
- identity_id
- name_id
- identity_institution
- second_factor_id
- second_factor_type
- preferred_locale
- Forgettable: email
- Forgettable: commonName
- Forgettable: secondFactorIdentifier
- Forgettable: documentNumber

[U2fDevicePossessionProvenAndVerifiedEvent](../src/Surfnet/Stepup/Identity/Event/U2fDevicePossessionProvenAndVerifiedEvent.php)
- identity_id
- identity_institution
- second_factor_id
- registration_requested_at
- registration_code
- Forgettable: keyHandle (is secondFactorIdentifier)
- Forgettable: email
- Forgettable: commonName

[U2fDevicePossessionProvenEvent](../src/Surfnet/Stepup/Identity/Event/U2fDevicePossessionProvenEvent.php)
- identity_id
- identity_institution
- second_factor_id
- email_verification_required
- email_verification_window
- email_verification_nonce
- preferred_locale
- Forgettable: keyHandle
- Forgettable: email
- Forgettable: commonName

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
- Forgettable: yubikeyPublicId (is secondFactorIdentifier)
- Forgettable: email
- Forgettable: commonName

[YubikeyPossessionProvenEvent](../src/Surfnet/Stepup/Identity/Event/YubikeyPossessionProvenEvent.php)
- identity_id
- identity_institution
- second_factor_id
- email_verification_required
- email_verification_window
- email_verification_nonce
- preferred_locale
- Forgettable: yubikeyPublicId (is secondFactorIdentifier)
- Forgettable: email
- Forgettable: commonName

[YubikeySecondFactorBootstrappedEvent](../src/Surfnet/Stepup/Identity/Event/YubikeySecondFactorBootstrappedEvent.php)
- identity_id
- name_id
- identity_institution
- preferred_locale
- second_factor_id
- Forgettable: yubikeyPublicId (is secondFactorIdentifier)
- Forgettable: email
- Forgettable: commonName

## Configuration Events

The configuration events [configuration events](../../../tree/develop/src/Surfnet/Stepup/Configuraton/Event/) in Stepup handle the the run time configuration of Stepup. With the exception of [SraaUpdatedEvent](../src/Surfnet/Stepup/Configuration/Event/SraaUpdatedEvent.php), which contains the *name_id* of the SRAA's, these events do not involve Personal Data
