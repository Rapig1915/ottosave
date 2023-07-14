# Run Code Locally With Docker

You'll want to review this document in its entirety to help you run the code quickly and easily.

## Setup Steps:

The following link will help you run this codebase locally within Docker containers. These steps are very important.

+ [Steps to setup and run this repository](https://github.com/bbuie/docs/wiki/Docker-Setup)

## Service Specific Documentation

Each docker service in this repository has further documentation that can be reviewed at a later date or if you have trouble with that particular service.

- [laravel-service](./laravel/README.md)
- [mysql-service](./mysql/README.md)
- [node-service](./node/README.md)

## Image Specific Documentation

Here is a list of images that are downloaded or created by a docker build:

- ubuntu:20.04
- redis:6.0
- mysql:5.7
- dym-laravel-image
- dym-ios-build-image
- dym-mysql-image
- dym-redis-image

Do do a full rebuild and clear all related cache, you can delete all these images.


##Docker setup definitions:

- git_base_repo_link: git@bitbucket.org:defendyourmoney/defendyourmoney-laravel-vue.git
- local_folder_name: ottosave_app - We recommend you create a folder *in your USER folder* with this name
- docker_container_names: see [docker-compose.yml](../docker-compose.yml)
- local_development_url:  Windows/Linux [http://192.168.99.100/](http://192.168.99.100/) Mac & Windows Pro [http://localhost](http://localhost)
- containers_finished_running_string: "[docker_container_names] is running!"