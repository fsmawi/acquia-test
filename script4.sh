#!/bin/bash

systemctl stop postfix && systemctl disable postfix && yum -y remove postfix
systemctl stop chronyd && systemctl disable chronyd && yum -y remove chrony
systemctl stop avahi-daemon.socket avahi-daemon.service
systemctl disable avahi-daemon.socket avahi-daemon.service
yum -y remove avahi-autoipd avahi-libs avahi
service network restart
chkconfig network on
systemctl restart network
