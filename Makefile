-include .env
export

# setup for docker-compose-ci build directory
# delete "build" directory to update docker-compose-ci

ifeq (,$(wildcard ./build/Makefile))
    $(shell git submodule update --init --remote)
endif

EXTENSION=SimpleTooltip

# docker images
MW_VERSION?=1.43
PHP_VERSION?=8.3
DB_TYPE?=mysql
DB_IMAGE?=""

# extensions

# composer
# Enables "composer update" inside of extension
COMPOSER_EXT?=true

# nodejs
# Enables node.js related tests and "npm install"
NODE_JS?=true

.PHONY: composer-phan
composer-phan: .init
ifdef COMPOSER_EXT
	$(show-current-target)
	$(compose-exec-wiki) bash -c "cd $(EXTENSION_FOLDER) && composer phan $(COMPOSER_PARAMS)"
endif

# check for build dir and git submodule init if it does not exist
include build/Makefile