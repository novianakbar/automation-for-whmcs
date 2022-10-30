#!/bin/bash

# permissions
if [ "$(whoami)" != "root" ]; then
	echo "Root privileges are required to run this, try running with sudo..."
	exit 2
fi

while getopts ":u:" o; do
	case "${o}" in
		u)
			site_url=${OPTARG}
			;;
	esac
done

# restart apache
echo "Disabling $site_url  site in Apache..."
echo `a2ensite $site_url`

echo "Restarting Apache..."
echo `/etc/init.d/apache2 restart`

echo "Process complete, $site_url suspended"
echo "success"
exit 0
