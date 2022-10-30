#!/bin/bash

# permissions
if [ "$(whoami)" != "root" ]; then
	echo "Root privileges are required to run this, try running with sudo..."
	exit 2
fi

current_directory="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
hosts_path="/etc/hosts"
vhosts_path="/etc/apache2/sites-available/"
web_root="/var/www/"


# user input passed as options?
site_url=0
relative_doc_root=0

while getopts ":u:d:" o; do
	case "${o}" in
		u)
			site_url=${OPTARG}
			;;
		d)
			relative_doc_root=${OPTARG}
			;;
	esac
done

# prompt if not passed as options
if [ $site_url == 0 ]; then
	read -p "Please enter the desired URL: " site_url
fi

if [ $relative_doc_root == 0 ]; then
	read -p "Please enter the site path relative to the web root: $web_root_path" relative_doc_root
fi

# construct absolute path
absolute_doc_root=$web_root$relative_doc_root

#deleting dir web
echo "Removing $absolute_doc_root"
`rm -rf $absolute_doc_root`

# update hosts file
sed -i '/^127\.0\.0\.1[[:space:]]$site_url/d' $hosts_path
echo "Updated the hosts file"

# restart apache
echo "Disabling site in Apache..."
echo `a2dissite $site_url`
`rm $vhosts_path$site_url.conf`
echo "Restarting Apache..."
echo `/etc/init.d/apache2 restart`

echo "Process complete, Terminating $site_url"
echo "success"
exit 0
