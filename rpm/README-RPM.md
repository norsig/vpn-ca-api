This configuration describes how to use the software after installing it. It
is assumed you already installed the `vpn-cert-service` package and restarted
Apache.

    $ sudo yum -y install vpn-cert-service
    $ sudo systemctl restart httpd

Now you can create a configuration file from the template in 
`/etc/vpn-cert-service`:

    $ sudo cp /etc/vpn-cert-service/config.ini.defaults /etc/vpn-cert-service/config.ini

Modify it to suit your environment. After that you can initialize the 
configuration:

    $ sudo -u apache vpn-cert-service-init

This will create the correct files in `/var/lib/vpn-cert-service` and generate 
a CA. 

To generate the server configuration use the following:

    $ sudo -u apache vpn-cert-service-generate-server-config

You can use this file and place it in your server directory as 
`/etc/openvpn/server.conf`. Now you can start OpenVPN:

    $ sudo systemctl start openvpn@server

This is all that is needed to get going on the server. See the project's 
README.md on how to use the API to generate client configuration files. It is
out of the scope of this document to setup the clients.
