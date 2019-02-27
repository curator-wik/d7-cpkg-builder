#!/bin/bash
# set -e is critical so that bash bails if any commands exit nonzero
set -e

curator/vendor/bin/phpunit -c curator/phpunit.xml.dist curator/tests/Integration/Cpkg/CpkgValidateTest

# Yes, phpunit already pretty much did this, but it can't hurt.
if [[ ! -f "current_version/index.php" ]]; then
  echo "Did not find expected current_version/index.php"
  exit 1
fi

# diff exits 1 when there is a difference
/usr/bin/diff --brief -r $TEST_SITE_ROOT current_version

# Okay, the cpkg that we applied looks good. Upload it.
CPKG="Drupal${DELTA_VERSION}-${CURRENT_VERSION}.cpkg.zip"

if [[ ! -f $CPKG ]]; then
  echo "Did not find cpkg ${CPKG} to upload"
  exit 1
fi

scp -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i secret_identity_file $CPKG $SCP_DEST
