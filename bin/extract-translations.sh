#!/bin/bash
php bin/console translation:extract --env=dev nl_NL --format=xliff --force
php bin/console translation:extract --env=dev en_GB --format=xliff --force
