#!/usr/bin/env bash

cd $(dirname $0)/../../

vendor/bin/rector process "$@" -c ./ci/qa/rector.php
