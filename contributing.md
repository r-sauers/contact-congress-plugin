# Welcome
I appreciate anyone that would like to help!

## Prerequisites
Make sure these are installed!
 * [npm](https://www.npmjs.com/) is a package manager for Node.js
 * [Docker](https://www.docker.com/get-started/) makes it easy to start an instance of wordpress on your computer.
 * [Composer](https://getcomposer.org/) is a package manager for php.

## Getting Started
1. Clone the repo: `git clone https://github.com/r-sauers/contact-congress-plugin.git`
2. Go into the project: `cd contact-congress-plugin`
3. Install dependencies: `npm i; composer i;`
4. Start docker: `cd docker; sudo docker compose up;`
5. Go to [https://localhost:8880](https://localhost:8880) and follow instructions to set up wordpress.
6. Go to the [plugins page](https://localhost:8880/wp-admin/plugins.php) and upload `congress.zip`
7. Activate the plugin.

## Development
Now there is code in two locations: Docker and the directory you cloned the repo.
- Docker Pros: developing inside the docker container is great because the website will update with a simple refresh!
- Docker Cons: If you delete your docker volume or the plugin, you will lose everything.
For these reasons, I develop in docker, but have a symbolic link (no worries if you don't know what that means) and scripts to copy to and from the volume.
Here's how to set that up (note that you need sudo permissions, and this may only work on linux and not wsl or Mac):
1. Add permissions so you can copy to docker: `sudo setfacl -m u:$(whoami):rx /var/lib/docker var/lib/docker/volumes var/lib/docker/volumes/docker_wordpress var/lib/docker/volumes/docker_wordpress/_data var/lib/docker/volumes/docker_wordpress/_data/wp-content var/lib/docker/volumes/docker_wordpress/_data/wp-content/plugins`
2. Go to the root of your directory.
3. Add a symbolic link: `ln -s /var/lib/docker/volumes/docker_wordpress/_data/wp-content/plugins/congress congress-volume`
4. Run `composer run cp-to-volume`

Now you can edit inside docker and use `composer run cp-from-volume` and `composer run cp-to-volume` to move files around!

## Making a pull request
Once you make the changes you want, you can make a pull request. Make sure you are following best practices:
 * Run `composer run lint` to make sure your code is up to standard, you can use `composer run fix` to help you.
 * Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
 * Follow [WordPress Best Practices](https://developer.wordpress.org/plugins/plugin-basics/best-practices/)
 * Understand [WordPress Security](https://developer.wordpress.org/apis/security/)

Don't worry  if that looks like a lot, the linter should catch anything that's not good enough for wordpress (security, styling, etc), but its helpful to familiarize yourself with everything above.
