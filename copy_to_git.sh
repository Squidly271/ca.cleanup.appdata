#!/bin/bash

mkdir -p "/tmp/GitHub/ca.cleanup.appdata/source/ca.cleanup.appdata/usr/local/emhttp/plugins/ca.cleanup.appdata/"

cp /usr/local/emhttp/plugins/ca.cleanup.appdata/* /tmp/GitHub/ca.cleanup.appdata/source/ca.cleanup.appdata/usr/local/emhttp/plugins/ca.cleanup.appdata -R -v -p
find . -maxdepth 9999 -noleaf -type f -name "._*" -exec rm -v "{}" \;

