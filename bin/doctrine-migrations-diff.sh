#!/usr/bin/env bash

php72 ./bin/console do:mi:di --em=middleware --filter-expression='~^(?!event_stream).*$~'
sleep 1
php72 ./bin/console do:mi:di --em=gateway