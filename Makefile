.DEFAULT_GOAL := help
SHELL=/bin/bash

-include .makefile/composer.mk
-include .makefile/global.mk

###
### VERSIONS
### ¯¯¯

SYLIUS_VERSION=1.12.6
SYMFONY_VERSION=6.4
PLUGIN_NAME=agence-adeliom/sylius-easy-crud-plugin
PLUGIN_DIR=lib/sylius-easy-crud-plugin

###
### DEV
### Commands to install sylius standard version and this plugin automatically
### ¯¯¯¯¯¯¯¯¯¯¯

-include .makefile/dev.mk

###
### QA
### Commands to test the code quality
### ¯¯¯¯¯¯¯¯¯¯¯

-include .makefile/ci.mk




