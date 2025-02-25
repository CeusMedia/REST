
composer-install:
	@test ! -f vendor/autoload.php && composer install --no-dev || true

composer-install-dev:
	@test ! -d vendor/phpunit/phpunit && composer install || true

composer-update:
	@composer update --no-dev

composer-update-dev:
	@composer update

dev-doc: composer-install-dev
	@test -f doc/API/search.html && rm -Rf doc/API || true
	@php vendor/ceus-media/doc-creator/doc.php --config-file=util/doc.xml

dev-test: composer-install-dev
	@vendor/bin/phpunit

dev-test-syntax:
	@find src -type f -print0 | xargs -0 -n1 xargs php -l

dev-phpstan:
	@vendor/bin/phpstan analyse --configuration util/phpstan.neon --xdebug || true

dev-phpstan-save-baseline:
	@vendor/bin/phpstan analyse --configuration util/phpstan.neon --generate-baseline util/phpstan-baseline.neon || true


