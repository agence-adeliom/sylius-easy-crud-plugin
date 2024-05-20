
## Requirement

- Be sure you have php 8.2 on your machine.
- Be sure you have docker on your machine.
- Be sure you have symfony cli on your machine.
- Create .makefile/composer.mk files and put you're composer token in it:

```
GITHUB_TOKEN ?= ghp_xxxxxxxxxxxxx
```
https://getcomposer.org/doc/articles/authentication-for-private-packages.md#github-oauth

## Installation

`make install`

The installation will download and configure default sylius standard version with this bundle directly installed as a symlinked dependency.

## Usage

### List all available commands

`make help`

### Stop

`make down`

### Reset project

`make reset`

