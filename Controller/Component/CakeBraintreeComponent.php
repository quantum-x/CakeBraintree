<?php
/**
 * CakeBraintreeComponent
 *
 * A component that handles payment processing client token generation for BT / Cake  
 *
 * PHP version 5
 *
 * @package		CakeBraintreeComponent
 * @author		Gregory Gaskill <gregory@chronon.com>
 * @license		MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @link		https://github.com/chronon/CakePHP-CakeBraintreeComponent-Plugin
 */

App::uses('Component', 'Controller');


/**
 * CakeBraintreeComponent
 *
 * @package		CakeBraintreeComponent
 */
class CakeBraintreeComponent extends Component {
    /**
     * @var bool
     */
    protected $production = true;
    /**
     * @var bool
     */
    protected $merchantId = false;
    /**
     * @var bool
     */
    protected $merchantAccountId = false;
    /**
     * @var bool
     */
    protected $publicKey = false;
    /**
     * @var bool
     */
    protected $privateKey = false;
    /**
     * @var bool
     */
    protected $ready = false;
    /**
     * @var string
     */
    protected $context = false;

    /**
     * Constructor
     *
     * @param ComponentCollection $collection The ComponentCollection object
     * @param array $settings Settings passed via controller
     * @throws Exception for data checking
     */
    public function __construct(ComponentCollection $collection, $settings = array()) {
        // Check to see if we're got settings passed in through the configuration variables
        //Process the settings from the config.
        $this->_processConfig(Configure::read('Braintree'));

        //Process any settings we got through reference
        $this->_processConfig($settings);

        //Check to see we've got settings.
        if (!$this->merchantId)                         throw new Exception(__('Merchant ID must be set'));
        if (!$this->publicKey)                          throw new Exception(__('Public Key must be set'));
        if (!$this->privateKey)                         throw new Exception(__('Private Key must be set'));

        $this->_initializeModule();
    }

    /**
     * _initializeModule
     * Initializes the Braintree module
     *
     * @param None
     *      */
    private function _initializeModule() {
        Braintree_Configuration::merchantId($this->merchantId);
        Braintree_Configuration::publicKey($this->publicKey);
        Braintree_Configuration::privateKey($this->privateKey);
        Braintree_Configuration::sslVersion(6);

        if ($this->production) {
            Braintree_Configuration::environment('production');
        } else {
            Braintree_Configuration::environment('sandbox');
        }

        $this->ready = true;
    }

    /**
     * @param array $settings
     * @return bool
     */
    private function _processConfig($settings = array()) {
        if (!empty($settings)) {
            if (isset($settings['production']) && is_bool($settings['production'])) $this->production = $settings['production'];
            if (isset($settings['merchantId']) && !empty($settings['merchantId'])) $this->merchantId = $settings['merchantId'];
            if (isset($settings['publicKey']) && !empty($settings['publicKey'])) $this->publicKey = $settings['publicKey'];
            if (isset($settings['privateKey']) && !empty($settings['privateKey'])) $this->privateKey = $settings['privateKey'];
            if (isset($settings['merchantAccountId']) && !empty($settings['merchantAccountId'])) $this->merchantAccountId = $settings['merchantAccountId'];
            return true;
        } else {
            return false;
        }
    }

    private function _checkReady() {
        if (!$this->ready) throw new Exception(__('System improperly setup. Please see the documentation'));
    }

    public function sale($array = []) {
        if (empty($array)) throw new Exception(__('Transaction information missing. Please see the documentation'));
        if (!isset($array['merchantAccountId']) && $this->merchantAccountId)   $array['merchantAccountId'] = $this->merchantAccountId;

        return  Braintree_Transaction::sale($array);
    }

    public function generateClientToken($_customerId = False) {
        $this->_checkReady();
        $options = [];

        if ($_customerId)   $options['customerId'] = $_customerId;
        if ($this->merchantAccountId)   $options['merchantAccountId'] = $this->merchantAccountId;

        return Braintree_ClientToken::generate($options);
    }

    public function customer() {
        //Convenience function to allow for logically chaining functions
        $this->context = 'customer';
        return $this;
    }

    public function find($id) {
        switch($this->context) {
            case 'customer':
                try {
                    return Braintree_Customer::find($id);
                } catch (Exception $e) {
                    return false;
                }
            break;

        }
    }
}
