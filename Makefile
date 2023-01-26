# This file is licensed under the Affero General Public License version 3 or
# later. See the COPYING file.
# @author Bernhard Posselt <dev@bernhard-posselt.com>
# @copyright Bernhard Posselt 2016

# Generic Makefile for building and packaging a Nextcloud app which uses npm and
# Composer.
#
# Dependencies:
# * make
# * which
# * curl: used if phpunit and composer are not installed to fetch them from the web
# * tar: for building the archive
# * npm: for building and testing everything JS
#
# If no composer.json is in the app root directory, the Composer step
# will be skipped. The same goes for the package.json which can be located in
# the app root or the js/ directory.
#
# The npm command by launches the npm build script:
#
#    npm run build
#
# The npm test command launches the npm test script:
#
#    npm run test
#
# The idea behind this is to be completely testing and build tool agnostic. All
# build tools and additional package managers should be installed locally in
# your project, since this won't pollute people's global namespace.
#
# The following npm scripts in your package.json install and update the bower
# and npm dependencies and use gulp as build system (notice how everything is
# run from the node_modules folder):
#
#    "scripts": {
#        "test": "node node_modules/gulp-cli/bin/gulp.js karma",
#        "prebuild": "npm install && node_modules/bower/bin/bower install && node_modules/bower/bin/bower update",
#        "build": "node node_modules/gulp-cli/bin/gulp.js"
#    },

project := $(OPENXPORT_PROJECT)

app_name=$(notdir $(CURDIR))
build_tools_directory=$(CURDIR)/build/tools
source_build_directory=$(CURDIR)/build/artifacts/source
source_package_name=$(source_build_directory)/$(app_name)
appstore_build_directory=$(CURDIR)/build/artifacts/appstore
appstore_package_name=$(appstore_build_directory)/$(app_name)
nextcloud_test_directory=$(NEXTCLOUD_TEST_DIR)
npm=$(shell which npm 2> /dev/null)
composer=$(shell ls $(build_tools_directory)/composer_fresh.phar 2> /dev/null)
version=$(shell git tag --sort=committerdate | tail -1)

all: init

# Initialize project. Run this before any other target.
# Fetches the PHP and JS dependencies and compiles the JS. If no composer.json
# is present, the composer step is skipped, if no package.json or js/package.json
# is present, the npm step is skipped
.PHONY: init
init: composer
	rm $(build_tools_directory)/composer.phar || true
	ln $(build_tools_directory)/composer_fresh.phar $(build_tools_directory)/composer.phar
	php $(build_tools_directory)/composer.phar install --prefer-dist --no-dev
ifneq (,$(wildcard $(CURDIR)/package.json))
	make npm
endif
ifneq (,$(wildcard $(CURDIR)/js/package.json))
	make npm
endif

# Update dependencies and make dev tools available for development
.PHONY: update
update:
	git submodule update --init --recursive
	php $(build_tools_directory)/composer.phar update --prefer-dist

# Installs and updates the composer dependencies. If composer is not installed
# a copy is fetched from the web
.PHONY: composer
composer:
ifeq (, $(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_directory)
	./get_composer.sh
	mv composer.phar $(build_tools_directory)/composer_fresh.phar
endif

# Installs npm dependencies
.PHONY: npm
npm:
ifeq (,$(wildcard $(CURDIR)/package.json))
	cd js && $(npm) run build
else
	npm run build
endif

# Removes the appstore build
.PHONY: clean
clean:
	rm -rf ./build ./vendor

# Same as clean but also removes dependencies installed by composer, bower and
# npm
.PHONY: distclean
distclean: clean
	rm -rf vendor
	rm -rf node_modules
	rm -rf js/vendor
	rm -rf js/node_modules

# Builds the source and appstore package
.PHONY: dist
dist:
	make source
	make appstore

# Builds the source package
.PHONY: source
source:
	rm -rf $(source_build_directory)
	mkdir -p $(source_build_directory)
	tar cvzf $(source_package_name).tar.gz ../$(app_name) \
	--exclude-vcs \
	--exclude="../$(app_name)/build" \
	--exclude="../$(app_name)/js/node_modules" \
	--exclude="../$(app_name)/node_modules" \
	--exclude="../$(app_name)/*.log" \
	--exclude="../$(app_name)/js/*.log" \

# Builds the source package for the app store, ignores php and js tests
.PHONY: appstore
appstore:
	rm -rf $(appstore_build_directory)
	mkdir -p $(appstore_build_directory)
	tar cvzf $(appstore_package_name).tar.gz ../$(app_name) \
	--exclude-vcs \
	--exclude="../$(app_name)/build" \
	--exclude="../$(app_name)/tests" \
	--exclude="../$(app_name)/Makefile" \
	--exclude="../$(app_name)/*.log" \
	--exclude="../$(app_name)/phpunit*xml" \
	--exclude="../$(app_name)/composer.*" \
	--exclude="../$(app_name)/js/node_modules" \
	--exclude="../$(app_name)/js/tests" \
	--exclude="../$(app_name)/js/test" \
	--exclude="../$(app_name)/js/*.log" \
	--exclude="../$(app_name)/js/package.json" \
	--exclude="../$(app_name)/js/bower.json" \
	--exclude="../$(app_name)/js/karma.*" \
	--exclude="../$(app_name)/js/protractor.*" \
	--exclude="../$(app_name)/package.json" \
	--exclude="../$(app_name)/bower.json" \
	--exclude="../$(app_name)/karma.*" \
	--exclude="../$(app_name)/protractor\.*" \
	--exclude="../$(app_name)/.*" \
	--exclude="../$(app_name)/js/.*" \

# Linting with PHP-CS
.PHONY: lint
lint:
	# Make devtools available again
	php $(build_tools_directory)/composer.phar install --prefer-dist

	# Lint with CodeSniffer
	vendor/bin/phpcs lib/

# Requires:
# * NEXTCLOUD_TEST_DIR - apps/jmap directory of a nextcloud instance. Files will be copied to it.
# * podman to run tests
# Example usage: NEXTCLOUD_TEST_DIR=~/ops/containers/nextcloud/custom_apps/jmap make test
.PHONY: test
test: composer
ifeq (, $(nextcloud_test_directory))
	@echo "Tests must be run inside Nextcloud. You must specify NEXTCLOUD_TEST_DIR."
else
	rm -rf $(nextcloud_test_directory)/vendor
	find . -maxdepth 1 -type f,d -not -regex ".\|./.git.*" -exec cp -r '{}' '$(nextcloud_test_directory)/' ';'
	podman exec -it nc-eval sh -c "cd custom_apps/jmap/ && vendor/phpunit/phpunit/phpunit -c phpunit.xml"
	podman exec -it nc-eval sh -c "cd custom_apps/jmap/ && vendor/phpunit/phpunit/phpunit -c phpunit.integration.xml"
endif

# Build a ZIP for deploying
.PHONY: zip
zip:
	php $(build_tools_directory)/composer.phar install --prefer-dist --no-dev
	php $(build_tools_directory)/composer.phar archive -f zip --dir=build/archives --file=jmap-nextcloud-$(version)
# In case of project build: rename and put jmap folder to root level
ifneq (, $(project))
	mkdir -p build/tmp/jmap
	unzip -q build/archives/jmap-nextcloud-$(version).zip -d build/tmp/jmap
	cd build/tmp && zip -qmr jmap-nextcloud-$(version)-$(project).zip jmap/ && mv jmap-nextcloud-$(version)-$(project).zip ../archives
endif

.PHONY: fulltest
fulltest: lint test
