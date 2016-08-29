# This make file contains shortcuts for developing. There is no
# magic here, it just combines commonly used items into single make targets.

.PHONY: test

test:
	vendor/phpunit/phpunit/phpunit tests
