#!/bin/bash

# The ssh_wrapper has to exist in the unix user's home directory in
# $HOME/bin/ssh_wrapper or wip cannot start tasks. Ensure that it is
# there and that the symlink points to the correct location.

test -d /home/$1/bin || mkdir -p /home/$1/bin
cd /home/$1/bin
test -e /home/$1/bin/ssh_wrapper || (rm -f /home/$1/bin/ssh_wrapper && ln -s /var/www/html/$1.$2/scripts/ssh_wrapper)
ls -lha /home/$1/bin
