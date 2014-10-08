# Introduction
These are all the files to get a Docker instance running with 
`vpn-cert-service`.

To build the Docker image:

    docker build --rm -t fkooman/vpn-cert-service .

To run the container:

    docker run -d -p 443:443 fkooman/vpn-cert-service

That should be all. You can replace `fkooman` with your own name of course.
