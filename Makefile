.DEFAULT_GOAL := help
SHELL=/bin/bash

GITHUB_TOKEN ?= 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

-include .makefile/global.mk

###
### ENV VERSIONS
### ¯¯¯

SYLIUS_VERSION=1.12.6
SYMFONY_VERSION=6.4
PLUGIN_NAME=agence-adeliom/sylius-easy-crud-plugin

###
### DEVELOPMENT
### ¯¯¯¯¯¯¯¯¯¯¯

-include .makefile/dev.mk

###
### CI
### ¯¯¯¯¯¯¯¯¯¯¯

-include .makefile/ci.mk




