.PHONY: test share

test:
	php vendor/bin/phpunit

share:
	ngrok http "cloud.dev:80" -subdomain=laravel-cloud -host-header=rewrite

fresh:
	php artisan migrate:fresh
	php artisan passport:install --force
	rm storage/app/keys/*

default: test
