<?php
/**
 * 2024 - TKL
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 *
 * @author TKL <support@example.com>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Tkl_Customerexport extends Module
{
    public function __construct()
    {
        $this->name = 'tkl_customerexport';
        $this->tab = 'export';
        $this->version = '1.0.0';
        $this->author = 'TKL';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Customer Address Export');
        $this->description = $this->l('Export all customer address data to CSV format');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
    }

    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit_tkl_export')) {
            if ($this->exportCustomerAddresses()) {
                $output .= $this->displayConfirmation($this->l('Customer addresses exported successfully!'));
            } else {
                $output .= $this->displayError($this->l('An error occurred during export.'));
            }
        }

        $output .= $this->renderForm();
        return $output;
    }

    private function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Export Customer Addresses'),
                    'icon' => 'icon-download'
                ),
                'submit' => array(
                    'title' => $this->l('Export to CSV'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submit_tkl_export'
                ),
                'inputs' => array(
                    array(
                        'type' => 'hidden',
                        'name' => 'submit_tkl_export',
                    ),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submit_tkl_export';
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->currentIndex = AdminController::$currentIndex;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $this->context->language;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_language = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANGUAGE');

        return $helper->generateForm(array($fields_form));
    }

    private function exportCustomerAddresses()
    {
        try {
            $addresses = Db::getInstance()->executeS('
                SELECT 
                    a.id_address,
                    c.id_customer,
                    c.firstname,
                    c.lastname,
                    c.email,
                    a.firstname as address_firstname,
                    a.lastname as address_lastname,
                    a.company,
                    a.address1,
                    a.address2,
                    a.postcode,
                    a.city,
                    a.phone,
                    a.phone_mobile,
                    co.name as country,
                    s.name as state,
                    a.active,
                    a.date_add
                FROM ' . _DB_PREFIX_ . 'address a
                LEFT JOIN ' . _DB_PREFIX_ . 'customer c ON a.id_customer = c.id_customer
                LEFT JOIN ' . _DB_PREFIX_ . 'country_lang co ON a.id_country = co.id_country AND co.id_lang = ' . (int)$this->context->language->id . '
                LEFT JOIN ' . _DB_PREFIX_ . 'state s ON a.id_state = s.id_state
                WHERE a.deleted = 0
                ORDER BY c.id_customer, a.id_address
            ');

            if (empty($addresses)) {
                return false;
            }

            $filename = 'customer_addresses_' . date('Y-m-d_H-i-s') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write header
            $headers = array(
                'ID Address',
                'ID Customer',
                'Customer First Name',
                'Customer Last Name',
                'Email',
                'Address First Name',
                'Address Last Name',
                'Company',
                'Address 1',
                'Address 2',
                'Postcode',
                'City',
                'Phone',
                'Phone Mobile',
                'Country',
                'State',
                'Active',
                'Date Added'
            );
            fputcsv($output, $headers, ',', '"');
            
            // Write data
            foreach ($addresses as $address) {
                $row = array(
                    $address['id_address'],
                    $address['id_customer'],
                    $address['firstname'],
                    $address['lastname'],
                    $address['email'],
                    $address['address_firstname'],
                    $address['address_lastname'],
                    $address['company'],
                    $address['address1'],
                    $address['address2'],
                    $address['postcode'],
                    $address['city'],
                    $address['phone'],
                    $address['phone_mobile'],
                    $address['country'],
                    $address['state'],
                    $address['active'],
                    $address['date_add']
                );
                fputcsv($output, $row, ',', '"');
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            return false;
        }
    }
}
