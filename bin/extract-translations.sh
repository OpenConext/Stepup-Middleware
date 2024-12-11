#!/bin/bash
bin/console translation:extract --env=dev nl_NL --format=xliff --force
bin/console translation:extract --env=dev en_GB --format=xliff --force
