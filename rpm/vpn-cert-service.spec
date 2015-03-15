%global github_owner     fkooman
%global github_name      vpn-cert-service

Name:       vpn-cert-service
Version:    0.2.0
Release:    1%{?dist}
Summary:    OpenVPN configuration manager written in PHP

Group:      Applications/Internet
License:    ASL-2.0
URL:        https://github.com/%{github_owner}/%{github_name}
Source0:    https://github.com/%{github_owner}/%{github_name}/archive/%{version}.tar.gz
Source1:    vpn-cert-service-httpd-conf
Source2:    vpn-cert-service-autoload.php

BuildArch:  noarch

Requires:   php >= 5.3.3
Requires:   php-openssl
Requires:   php-pdo
Requires:   httpd
Requires:   easy-rsa >= 2.0.0
Requires:   openvpn

Requires:   php-composer(fkooman/ini) >= 0.2.0
Requires:   php-composer(fkooman/ini) < 0.3.0
Requires:   php-composer(fkooman/rest) >= 0.7.4
Requires:   php-composer(fkooman/rest) < 0.8.0
Requires:   php-composer(fkooman/rest-plugin-basic) >= 0.5.0
Requires:   php-composer(fkooman/rest-plugin-basic) < 0.6.0

Requires:   php-pear(pear.twig-project.org/Twig) >= 1.15
Requires:   php-pear(pear.twig-project.org/Twig) < 2.0

#Starting F21 we can use the composer dependency for Symfony
#Requires:   php-composer(symfony/classloader) >= 2.3.9
#Requires:   php-composer(symfony/classloader) < 3.0
Requires:   php-pear(pear.symfony.com/ClassLoader) >= 2.3.9
Requires:   php-pear(pear.symfony.com/ClassLoader) < 3.0

Requires(post): policycoreutils-python
Requires(postun): policycoreutils-python

%description
This is a configuration generator for OpenVPN. It aims at providing a REST API
that makes it easy to manage client configuration files. It is possible to
generate a configuration and revoke a configuration.

%prep
%setup -qn %{github_name}-%{version}

sed -i "s|dirname(__DIR__)|'%{_datadir}/vpn-cert-service'|" bin/vpn-cert-service-init
sed -i "s|dirname(__DIR__)|'%{_datadir}/vpn-cert-service'|" bin/vpn-cert-service-generate-server-config
sed -i "s|dirname(__DIR__)|'%{_datadir}/vpn-cert-service'|" bin/vpn-cert-service-generate-password-hash

%build

%install
# Apache configuration
install -m 0644 -D -p %{SOURCE1} ${RPM_BUILD_ROOT}%{_sysconfdir}/httpd/conf.d/vpn-cert-service.conf

# Application
mkdir -p ${RPM_BUILD_ROOT}%{_datadir}/vpn-cert-service
cp -pr web views src ${RPM_BUILD_ROOT}%{_datadir}/vpn-cert-service

# use our own class loader
mkdir -p ${RPM_BUILD_ROOT}%{_datadir}/vpn-cert-service/vendor
cp -pr %{SOURCE2} ${RPM_BUILD_ROOT}%{_datadir}/vpn-cert-service/vendor/autoload.php

mkdir -p ${RPM_BUILD_ROOT}%{_bindir}
cp -pr bin/* ${RPM_BUILD_ROOT}%{_bindir}

# Config
mkdir -p ${RPM_BUILD_ROOT}%{_sysconfdir}/vpn-cert-service
cp -p config/config.ini.defaults ${RPM_BUILD_ROOT}%{_sysconfdir}/vpn-cert-service/config.ini
ln -s ../../../etc/vpn-cert-service ${RPM_BUILD_ROOT}%{_datadir}/vpn-cert-service/config

# Data
mkdir -p ${RPM_BUILD_ROOT}%{_localstatedir}/lib/vpn-cert-service

%post
semanage fcontext -a -t httpd_sys_rw_content_t '%{_localstatedir}/lib/vpn-cert-service(/.*)?' 2>/dev/null || :
restorecon -R %{_localstatedir}/lib/vpn-cert-service || :

%postun
if [ $1 -eq 0 ] ; then  # final removal
semanage fcontext -d -t httpd_sys_rw_content_t '%{_localstatedir}/lib/vpn-cert-service(/.*)?' 2>/dev/null || :
fi

%files
%defattr(-,root,root,-)
%config(noreplace) %{_sysconfdir}/httpd/conf.d/vpn-cert-service.conf
%dir %attr(-,apache,apache) %{_sysconfdir}/vpn-cert-service
%config(noreplace) %attr(0600,apache,apache) %{_sysconfdir}/vpn-cert-service/config.ini
%{_bindir}/vpn-cert-service-init
%{_bindir}/vpn-cert-service-generate-server-config
%{_bindir}/vpn-cert-service-generate-password-hash
%dir %{_datadir}/vpn-cert-service
%{_datadir}/vpn-cert-service/src
%{_datadir}/vpn-cert-service/vendor
%{_datadir}/vpn-cert-service/web
%{_datadir}/vpn-cert-service/views
%{_datadir}/vpn-cert-service/config
%dir %attr(0700,apache,apache) %{_localstatedir}/lib/vpn-cert-service
%doc README.md COPYING composer.json config/config.ini.defaults

%changelog
* Sun Mar 15 2015 François Kooman <fkooman@tuxed.net> - 0.2.0-1
- update to 0.2.0

* Mon Feb 09 2015 François Kooman <fkooman@tuxed.net> - 0.1.21-1
- update to 0.1.21

* Tue Jan 20 2015 François Kooman <fkooman@tuxed.net> - 0.1.20-1
- update to 0.1.20
- require at least php-fkooman-rest-plugin-basic 0.3.1 for 
  PHP 5.3 support

* Tue Jan 20 2015 François Kooman <fkooman@tuxed.net> - 0.1.19-1
- update to 0.1.19
- update php-fkooman-rest-plugin-basic >= 0.3.0

* Mon Jan 19 2015 François Kooman <fkooman@tuxed.net> - 0.1.18-1
- update to 0.1.18

* Mon Jan 19 2015 François Kooman <fkooman@tuxed.net> - 0.1.17-1
- update to 0.1.17
