This configuration describes how to use the software after installing it. It
is assumed you already installed the `vpn-cert-service` package and restarted
Apache.

    $ sudo yum -y install mod_ssl vpn-cert-service
    $ sudo systemctl enable httpd
    $ sudo systemctl restart httpd

Now you can modify the configuration file in `/etc/vpn-cert-service` named
`config.ini`. Modify it to suit your environment. After that you can initialize 
the configuration:

    $ sudo -u apache vpn-cert-service-init

This will create the correct files in `/var/lib/vpn-cert-service` and generate 
a CA. 

To generate the server configuration use the following, please not that this
will taka a **really long** time:

    $ sudo -u apache vpn-cert-service-generate-server-config

You can use the output and place it in your server directory as 
`/etc/openvpn/server.conf`. Now you should fix the permissions and start 
OpenVPN (at boot):

    $ sudo chown openvpn.openvpn /etc/openvpn/server.conf
    $ sudo chmod 640 /etc/openvpn/server.conf
    $ sudo systemctl enable openvpn@server
    $ sudo systemctl start openvpn@server

With the default config NAT routing needs to be enabled on the server:

    $ sudo sysctl net.ipv4.ip_forward=1
    $ sudo iptables -t nat -A POSTROUTING -s 10.8.0.0/24 -o eth0 -j MASQUERADE

**FIXME**: figure out how to enable this on boot as well, say something about
the firewall, 1194 udp...)

This is all that is needed to get going on the server. See the project's 
README.md on how to use the API to generate client configuration files. It is
out of the scope of this document to setup the clients.
