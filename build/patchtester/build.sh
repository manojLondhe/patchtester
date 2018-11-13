#!/bin/sh
cd ../../
rm -rf build/packaging && mkdir build/packaging
rm -rf build/packages && mkdir build/packages
composer install --no-dev -o
cp -r administrator/components/com_patchtester build/packaging/admin
cp -r administrator/templates/atum/html/com_patchtester build/packaging/atum
cp -r administrator/templates/hathor/html/com_patchtester build/packaging/hathor
cp -r media/com_patchtester build/packaging/media
rm -rf build/packaging/admin/backups/*.txt
mv build/packaging/admin/patchtester.xml build/packaging/patchtester.xml
mv build/packaging/admin/script.php build/packaging/script.php
cd build/packaging
tar jcf ../packages/com_patchtester.tar.bz2 .
tar zcf ../packages/com_patchtester.tar.gz .
zip -r ../packages/com_patchtester.zip .
cd ../../
composer install
php build/patchtester/hash_generator.php
