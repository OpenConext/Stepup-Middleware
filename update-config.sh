#!/bin/bash
curl -XPOST -v \
    -H "Accept: application/json" \
    -H "Content-type: application/json" \
    -k \
    -d @config.json \
    http://management:admin@mw-dev.stepup.coin.surf.net/app_dev.php/management/configuration
