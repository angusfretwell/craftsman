#!/usr/bin/env bash

sed -i '/# Set Craftsman Environment Variable/,+1d' /home/vagrant/.profile
sed -i '/env\[.*/,+1d' /etc/php/7.0/fpm/php-fpm.conf
