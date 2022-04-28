# hesk-docker
HESK help desk, but in a Docker container.

## Dependencies
- You will need a MySQL/MariaDB database. This image does not include a database server so please deploy one before installing.

## How to use
*An example Docker Compose file is included in this repository.*

1. See [this page](https://github.com/luketainton/hesk-docker/pkgs/container/hesk) for the latest version.
2. Pull the version of the image that you want (e.g. latest): `docker pull ghcr.io/luketainton/hesk:latest`.
3. Run the container: `docker run -p 127.0.0.1:80:80 ghcr.io/luketainton/hesk:latest`.
4. Open your web browser to http://127.0.0.1/install (change IP/hostname for what you used in the last step).
5. Follow the instructions to install your HESK instance.
