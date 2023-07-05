# Nodeless.io Payments for PrestaShop 1.7 + 8.0

## Requirements

- PrestaShop version 8.0 or 1.7 (currently untested)
- PHP >=8.0
- PHP cURL extension

## Preparation

### Create an account
- Visit nodeless.io and <a href="https://nodeless.io" rel="noopener" target="_blank">create your account.</a>

### Create a store
- In the dashboard create a store
- Note the store id, you can click the copy button next to the store dropdown e.g. #2f2459 which will copy the store id 2f24592b-dcdc-4e1e-9bfc-6955585ad0ac 

### Create an API key / token 

- In the left sidebar, go to "Profile" and click on the "API Tokens" tab
- Click on "Generate Keys"
- Enter a label e.g. "prestashop" and click "Generate"
- Note the shown API key


Note: You can also give it a testrun first by <a href="https://testnet.nodeless.io" rel="noopener" target="_blank">opening a testnet account.</a>.

## Installation

### Upload and install
- On our [GitHub releases page](https://github.com/nodeless-io/prestashop/releases)
- Download the "nodeless.zip" archive of the latest release
- In Prestashop: Modules -> Module Manager click on "Upload a module"
- Scroll to the Nodeless module and click "Configure"

### Configuration

- "Production mode": enabled by default, if you want to use testnet instead switch it off
- "Store ID": enter the above noted store ID
- "API Key": enter the above noted API key
- "Webhook ID": this is empty by default but will be filled after you hit save, a webhook will be created automatically
- Click on "Save"

Congrats! If you see "Webhook ID" has a value after saving everything is properly setup and ready to start earning some Sats.
