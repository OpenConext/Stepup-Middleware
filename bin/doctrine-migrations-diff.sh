#!/usr/bin/env bash

bin/console doctrine:migrations:diff --em=middleware --filter-expression='~^(?!event_stream).*$~'
sleep 1
bin/console doctrine:migrations:diff --em=gateway