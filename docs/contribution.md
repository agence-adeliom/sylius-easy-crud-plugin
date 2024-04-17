
## Requirement

- Be sure you have php 8.2 on your machine.
- Be sure you have docker on your machine.

## Installation

`make install`

This will run a Sylius app (the one in tests/Application/) with the plugin installed and all Sylius' sample data. It uses the symfony binary.

Add `Adeliom\SyliusEasyCrudPlugin\SyliusEasyCrudPlugin::class => ['all' => true],` into `tests/Application/config/bundles.php`

`make install_bundle`

This will install the last dev version of this bundle 


## Usage

### List all available commands

`make help`

### Stop

`make down`

### Reset project

`make reset`

