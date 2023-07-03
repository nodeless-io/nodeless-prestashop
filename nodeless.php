<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    throw new RuntimeException('Missing autoload');
}

require_once __DIR__ . '/vendor/autoload.php';

use NodelessIO\Prestashop\Constants;
use NodelessIO\Prestashop\NodelessApi;

/** @noinspection AutoloadingIssuesInspection */

class Nodeless extends PaymentModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'nodeless';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Nodeless.io';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Nodeless.io Payments');
        $this->description = $this->l('Accept Bitcoin and Lightning payments in your online store, charity or fundraiser, all without all the complexities of managing a lightning node. Get payments sent directly to your cold storage or lightning address.');

        $this->confirmUninstall = $this->l('Are you sure to uninstall Nodeless module, the Bitcoin payment option should be removed?');

        $this->limited_currencies = ['USD', 'CAD', 'EUR'];

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $createTable = ' CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'nodeless_payment` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `cart_id` int(10) unsigned NOT NULL,
            `order_id` int(10) unsigned NULL,
            `status` varchar(30) NOT NULL,
            `invoice_id` varchar(255) NOT NULL,
            `amount_sats` varchar(30) NOT NULL,
            `amount_fiat` varchar(30) NOT NULL,
            `currency` varchar(10) NOT NULL,
            `checkout_url` varchar(255) NOT NULL,
            `created_at` datetime NOT NULL,
            `updated_at` datetime NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;';

        $orderStateErrors = (new OrderStates($this->name))->install();
        if (!empty($orderStateErrors)) {
            return $this->trans('Error installing order states.', [], 'Admin.Payment.Notification');
        }

        return parent::install() &&
            \Db::getInstance()->execute($createTable) &&
            Configuration::updateValue(Constants::LIVE_MODE, true) &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('displayPaymentReturn');
    }

    public function uninstall()
    {
        $dropTable = "DROP TABLE IF EXISTS " . _DB_PREFIX_ . "nodeless_payment";

        // todo: uninstall order states needed?

        return parent::uninstall() &&
            \Db::getInstance()->execute($dropTable) &&
            Configuration::deleteByName(Constants::LIVE_MODE) &&
            Configuration::deleteByName(Constants::STORE_ID) &&
            Configuration::deleteByName(Constants::API_KEY) &&
            Configuration::deleteByName(Constants::WEBHOOK_SECRET) &&
            Configuration::deleteByName(Constants::WEBHOOK_ID);
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitNodelessModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitNodelessModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Production mode'),
                        'name' => Constants::LIVE_MODE,
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'col' => 5,
                        'type' => 'text',
                        'required' => true,
                        'prefix' => '<i class="icon icon-shopping-cart"></i>',
                        'desc' => $this->l('Copy the store id from your nodeless.io stores dashboard here https://nodeless.io/app/stores/dashboard'),
                        'name' => Constants::STORE_ID,
                        'label' => $this->l('Store ID'),
                    ],
                    [
                        'col' => 5,
                        'type' => 'text',
                        'required' => true,
                        'desc' => $this->l('Copy an api key from your profile here https://nodeless.io/app/profile/api-keys'),
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => Constants::API_KEY,
                        'label' => $this->l('API Key'),
                    ],
                    [
                        'col' => 5,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-lock"></i>',
                        'desc' => $this->l('When saving the settings the webhook will get created automatically for you.'),
                        'name' => Constants::WEBHOOK_ID,
                        'label' => $this->l('Webhook ID'),
                        'readonly' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return [
            Constants::LIVE_MODE => Configuration::get(Constants::LIVE_MODE, null),
            Constants::STORE_ID => Configuration::get(Constants::STORE_ID, null),
            Constants::API_KEY => Configuration::get(Constants::API_KEY, null),
            Constants::WEBHOOK_ID => Configuration::get(Constants::WEBHOOK_ID, null),
        ];
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            // Update all values but webhook id as it gets set in ensureWebhook() if needed.
            if ($key !== Constants::WEBHOOK_ID) {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }

        $nlApi = new NodelessApi();
        $nlApi->ensureWebhook();
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     *
     * @return array|null
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $option = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $option->setCallToActionText($this->l('Pay with Bitcoin, Lightning Network'))
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect', [], true));
        //->setAdditionalInformation($this->l('You will be redirected to nodeless.io to complete the payment.'));
        return [
            $option
        ];
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hookDisplayPaymentReturn()
    {
        return "test hookDisplayPaymentReturn";
    }

}
