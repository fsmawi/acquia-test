echo "vagrant ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers
sed -i 's/^\(Defaults.*requiretty\)/#\1/' /etc/sudoers
mkdir -m 0700 /home/vagrant/.ssh
curl https://raw.githubusercontent.com/mitchellh/vagrant/master/keys/vagrant.pub >> /home/vagrant/.ssh/authorized_keys
chmod 600 /home/vagrant/.ssh/authorized_keys
chown -R vagrant:vagrant /home/vagrant
sed -i -e 's/^SELINUX=.*/SELINUX=permissive/' /etc/selinux/config
sed -i -e 's/#UseDNS yes/UseDNS no/' /etc/ssh/sshd_config
