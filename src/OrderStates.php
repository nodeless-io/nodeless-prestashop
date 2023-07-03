<?php

namespace NodelessIO\Prestashop;

use \Language;
use \OrderState;

class OrderStates
{
    /**
     * @var string
     */
    private $moduleName;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(string $moduleName)
    {
        $this->moduleName = $moduleName;
        $this->configuration = new \Configuration();
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \Exception
     */
    public function install(): bool
    {
        $errors = [];

        // Check and insert "new" (awaiting payment) order status, if needed.
        if (!$this->configuration->get(Constants::ORDER_STATE_NEW)
            || !\Validate::isLoadedObject(new OrderState($this->configuration->get(Constants::ORDER_STATE_NEW)))) {
            if (false === $this->installNew()) {
                $errors[] = [
                    'key' => 'Could not add new order state: NODELESS_OS_NEW',
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        // Check and insert "pending_confirmation" if needed.
        if (!$this->configuration->get(Constants::ORDER_STATE_PENDING_CONFIRMATION)
            || !\Validate::isLoadedObject(new OrderState($this->configuration->get(Constants::ORDER_STATE_PENDING_CONFIRMATION)))) {
            if (false === $this->installPendingConfirmation()) {
                $errors[] = [
                    'key' => 'Could not add new order state: NODELESS_OS_PENDING_CONFIRMATION',
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        // Check and insert "paid" if needed.
        if (!$this->configuration->get(Constants::ORDER_STATE_PAID)
            || !\Validate::isLoadedObject(new OrderState($this->configuration->get(Constants::ORDER_STATE_PAID)))) {
            if (false === $this->installPaid()) {
                $errors[] = [
                    'key' => 'Could not add new order state: NODELESS_OS_PAID',
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        // Check and insert "overpaid" if needed.
        if (!$this->configuration->get(Constants::ORDER_STATE_OVERPAID)
            || !\Validate::isLoadedObject(new OrderState($this->configuration->get(Constants::ORDER_STATE_OVERPAID)))) {
            if (false === $this->installOverpaid()) {
                $errors[] = [
                    'key' => 'Could not add new order state: NODELESS_OS_OVERPAID',
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        // Check and insert "underpaid" if needed.
        if (!$this->configuration->get(Constants::ORDER_STATE_UNDERPAID)
            || !\Validate::isLoadedObject(new OrderState($this->configuration->get(Constants::ORDER_STATE_UNDERPAID)))) {
            if (false === $this->installUnderpaid()) {
                $errors[] = [
                    'key' => 'Could not add new order state: NODELESS_OS_UNDERPAID',
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        // Check and insert "in_flight" if needed.
        if (!$this->configuration->get(Constants::ORDER_STATE_IN_FLIGHT)
            || !\Validate::isLoadedObject(new OrderState($this->configuration->get(Constants::ORDER_STATE_IN_FLIGHT)))) {
            if (false === $this->installInFlight()) {
                $errors[] = [
                    'key' => 'Could not add new order state: NODELESS_OS_IN_FLIGHT',
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        // Check and insert "expired" if needed.
        if (!$this->configuration->get(Constants::ORDER_STATE_EXPIRED)
            || !\Validate::isLoadedObject(new OrderState($this->configuration->get(Constants::ORDER_STATE_EXPIRED)))) {
            if (false === $this->installExpired()) {
                $errors[] = [
                    'key' => 'Could not add new order state: NODELESS_OS_EXPIRED',
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        // Check and insert "cancelled" if needed.
        if (!$this->configuration->get(Constants::ORDER_STATE_CANCELLED)
            || !\Validate::isLoadedObject(new OrderState($this->configuration->get(Constants::ORDER_STATE_CANCELLED)))) {
            if (false === $this->installCancelled()) {
                $errors[] = [
                    'key' => 'Could not add new order state: NODELESS_OS_CANCELLED',
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        foreach ($errors as $error) {
            \PrestaShopLogger::addLog($error['key'], 3);
        }

        return empty($errors);
    }

    /**
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     * @throws \Exception
     */
    private function installNew(): bool
    {
        $order_state = new OrderState();
        $order_state->name = [];
        $order_state->color = '#cedfff';
        $order_state->unremovable = false;
        $order_state->module_name = $this->moduleName;

        foreach (Language::getLanguages(true, false, true) as $languageId) {
            $order_state->name[$languageId] = 'Nodeless status: awaiting payment';
        }

        if (false === $order_state->add()) {
            return false;
        }

        $this->installImage($order_state, 'os_waiting.png');
        $this->configuration->set(Constants::ORDER_STATE_NEW, (int)$order_state->id);

        return true;
    }

    /**
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     * @throws \Exception
     */
    private function installPendingConfirmation(): bool
    {
        $order_state = new OrderState();
        $order_state->name = [];
        $order_state->color = '#92ffd1';
        $order_state->unremovable = true;
        $order_state->module_name = $this->moduleName;

        foreach (Language::getLanguages(true, false, true) as $languageId) {
            $order_state->name[$languageId] = 'Nodeless status: pending confirmation';
        }

        if (false === $order_state->add()) {
            return false;
        }

        $this->installImage($order_state, 'os_waiting.png');
        $this->configuration->set(Constants::ORDER_STATE_PENDING_CONFIRMATION, (int)$order_state->id);

        return true;
    }

    /**
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     * @throws \Exception
     */
    private function installPaid(): bool
    {
        $order_state = new OrderState();
        $order_state->name = [];
        $order_state->paid = true;
        $order_state->pdf_invoice = true;
        $order_state->send_email = true;
        $order_state->template = 'payment';
        $order_state->color = '#108510';
        $order_state->logable = true;
        $order_state->unremovable = true;
        $order_state->module_name = $this->moduleName;

        foreach (Language::getLanguages(true, false, true) as $languageId) {
            $order_state->name[$languageId] = 'Nodeless satus: paid';
        }

        if (false === $order_state->add()) {
            return false;
        }

        $this->installImage($order_state, 'os_bitcoin_paid.png');
        $this->configuration->set(Constants::ORDER_STATE_PAID, (int)$order_state->id);

        return true;
    }

    /**
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     * @throws \Exception
     */
    private function installOverpaid(): bool
    {
        $order_state = new OrderState();
        $order_state->name = [];
        $order_state->paid = true;
        $order_state->pdf_invoice = true;
        $order_state->send_email = true;
        $order_state->template = 'payment';
        $order_state->color = '#d9ff94';
        $order_state->logable = true;
        $order_state->unremovable = true;
        $order_state->module_name = $this->moduleName;

        foreach (Language::getLanguages(true, false, true) as $languageId) {
            $order_state->name[$languageId] = 'Nodeless satus: overpaid';
        }

        if (false === $order_state->add()) {
            return false;
        }

        $this->installImage($order_state, 'os_bitcoin_paid.png');
        $this->configuration->set(Constants::ORDER_STATE_OVERPAID, (int)$order_state->id);

        return true;
    }

    /**
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     * @throws \Exception
     */
    private function installUnderpaid(): bool
    {
        $order_state = new OrderState();
        $order_state->name = [];
        $order_state->color = 'Orange';
        $order_state->unremovable = true;
        $order_state->module_name = $this->moduleName;

        foreach (Language::getLanguages(true, false, true) as $languageId) {
            $order_state->name[$languageId] = 'Nodeless satus: underpaid';
        }

        if (false === $order_state->add()) {
            return false;
        }

        $this->installImage($order_state, 'os_waiting.png');
        $this->configuration->set(Constants::ORDER_STATE_UNDERPAID, (int)$order_state->id);

        return true;
    }

    /**
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     * @throws \Exception
     */
    private function installInFlight(): bool
    {
        $order_state = new OrderState();
        $order_state->name = [];
        $order_state->color = 'Orange';
        $order_state->unremovable = true;
        $order_state->module_name = $this->moduleName;

        foreach (Language::getLanguages(true, false, true) as $languageId) {
            $order_state->name[$languageId] = 'Nodeless satus: in_flight';
        }

        if (false === $order_state->add()) {
            return false;
        }

        $this->installImage($order_state, 'os_failed.png');
        $this->configuration->set(Constants::ORDER_STATE_IN_FLIGHT, (int)$order_state->id);

        return true;
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \Exception
     */
    private function installExpired(): bool
    {
        $order_state = new OrderState();
        $order_state->name = [];
        $order_state->send_email = false;
        $order_state->template = 'payment_error';
        $order_state->color = 'Grey';
        $order_state->logable = true;
        $order_state->unremovable = true;
        $order_state->module_name = $this->moduleName;

        foreach (Language::getLanguages(true, false, true) as $languageId) {
            $order_state->name[$languageId] = 'Nodeless status: expired';
        }

        if (false === $order_state->add()) {
            return false;
        }

        $this->installImage($order_state, 'os_bitcoin_failed.png');
        $this->configuration->set(Constants::ORDER_STATE_EXPIRED, (int)$order_state->id);

        return true;
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \Exception
     */
    private function installCancelled(): bool
    {
        $order_state = new OrderState();
        $order_state->name = [];
        $order_state->send_email = true;
        $order_state->template = 'payment_error';
        $order_state->color = '#ff0000';
        $order_state->logable = true;
        $order_state->unremovable = true;
        $order_state->module_name = $this->moduleName;

        foreach (Language::getLanguages(true, false, true) as $languageId) {
            $order_state->name[$languageId] = 'Nodeless status: cancelled';
        }

        if (false === $order_state->add()) {
            return false;
        }

        $this->installImage($order_state, 'os_bitcoin_failed.png');
        $this->configuration->set(Constants::ORDER_STATE_CANCELLED, (int)$order_state->id);

        return true;
    }

    /**
     * Installs and copies the order state icon to the destination.
     *
     * @param OrderState $order_state
     * @param string $image_name
     * @return void
     */
    private function installImage(OrderState $order_state, string $image_name): void
    {
        $source = \_PS_MODULE_DIR_ . $this->moduleName . '/views/images/' . $image_name;
        $destination = \_PS_ROOT_DIR_ . '/img/os/' . (int)$order_state->id . '.png';
        \copy($source, $destination);
    }
}
