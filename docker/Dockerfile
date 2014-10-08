FROM       fedora:20
#FROM       centos:centos6
#FROM       centos:centos7
MAINTAINER François Kooman <fkooman@tuxed.net>

# Add EPEL repository (CentOS 6)
#RUN yum -y install https://download.fedoraproject.org/pub/epel/6/x86_64/epel-release-6-8.noarch.rpm ; yum clean all

# Add EPEL repository (CentOS 7)
#RUN yum -y install https://download.fedoraproject.org/pub/epel/7/x86_64/e/epel-release-7-1.noarch.rpm ; yum clean all

# Add php-oauth repository (Fedora, CentOS 6, CentOS 7)
RUN curl -s -L -o /etc/yum.repos.d/fedora-php-oauth.repo https://repos.fedorapeople.org/repos/fkooman/php-oauth/fedora-php-oauth.repo

# Perform updates
RUN yum -y update; yum clean all

# Install dependencies (and SSL module)
RUN yum install -y mod_ssl vpn-cert-service; yum clean all

# Allow connections from everywhere
RUN sed -i 's/Require local/Require all granted/' /etc/httpd/conf.d/vpn-cert-service.conf
RUN sed -i 's/Allow from 127.0.0.1/Allow from All/' /etc/httpd/conf.d/vpn-cert-service.conf
RUN sed -i 's/Allow from ::1//' /etc/httpd/conf.d/vpn-cert-service.conf

USER apache

# Init
RUN vpn-cert-service-init

# Generate Server Configuration
# We expose this configuration file with private key on the webserver, so it
# can be downloaded in the actualy OpenVPN server by retrieving it from the 
# web server. THIS IS ONLY FOR DOCKER, NOT FOR PRODUCTION!
RUN vpn-cert-service-generate-server-config > /var/www/html/server.conf

USER root

# Expose port 443 and set httpd as our entrypoint
EXPOSE 443
ENTRYPOINT ["/usr/sbin/httpd"]
CMD ["-D", "FOREGROUND"]
