# Introduction
These are all the files to get a Docker instance running with 
`vpn-cert-service`.

To build the Docker image:

    docker build --rm -t fkooman/vpn-cert-service .

To run the container:

    docker run -d -p 443:443 fkooman/vpn-cert-service

That should be all. You can replace `fkooman` with your own name of course.

Once you have Docker it is possible to retrieve the server configuration:

    $ curl -k https://localhost/server.conf

Install this in your OpenVPN server, which can a different Docker image, or 
a physical server.

Now generate a client configuration and use that in the client:

    $ curl -k -X POST https://localhost/vpn-cert-service/api.php/fkooman@tuxed.net

For now you'll have to manually edit the IP address of the server in the client
configuration as changing it would require changes to the configuration 
inside Docker. On a real system the configuration could be changed to 
automatically point to the correct service once the configuration is generated.

This should now work!

The CRL implementation is not yet perfect as the server needs to read the CRL,
to test it you can revoke the certifcate:

    $ curl -k -X DELETE https://localhost/vpn-cert-service/api.php/fkooman@tuxed.net

Now you can download the CRL and add it to the server configuration:

    $ curl -k https://localhost/vpn-cert-service/api.php/crl

By now adding `crl-verify /path/to/crl` to your `server.conf` the generated 
client configuration should no longer be accepted.
