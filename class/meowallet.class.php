<?php
/**
 * Meo Wallet Standard Payment Gateway
 *
 * Provides a Meo Wallet Standard Payment Gateway for WooCommerce
 *
 * @class 		WC_MEOWALLET_GW
 * @extends		WC_Payment_Gateway
 * @version     0.1
 * @license     GPLv3
 * @author 		WebDS
 */
if (!defined('ABSPATH'))
    exit;

if (!class_exists("MEOWallet_API")) {
    require_once('api.class.php');
}

class WC_MEOWALLET_GW extends WC_Payment_Gateway {

    /** @var boolean Whether or not logging is enabled */
    public static $log_enabled = false;

    /** @var WC_Logger Logger instance */
    public static $log = false;
    protected $SANDBOX_URL = 'https://services.sandbox.meowallet.pt/api/v2';
    protected $WALLET_URL = 'https://services.wallet.pt/api/v2';

    /**
     * Constructor for the gateway.
     */
    function __construct() {
        global $wallet;

        // Default Variables
        $this->id = 'meowallet_wc';
        $this->icon = plugins_url('assets/images/mw.png', dirname(__FILE__));
        $this->has_fields = false;
        $this->method_title = __('Meo Wallet', 'meowallet_wc');
        $this->method_description = __('Aceita pagamentos Meo Wallet, MB e VISA', 'meowallet_wc');
        //$this->notify_url = WC()->api_request_url('WC_MEOWALLET_GW');
        $this->notify_url = str_replace('https:', 'http:', add_query_arg('wc-api', 'wc_gateway_meowallet', home_url('/')));
        ###############
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        ###############
        // Main setings
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->env = $this->get_option('environment');

        $this->url = ($this->env == 'production') ? $this->WALLET_URL : $this->SANDBOX_URL;
        $this->apikey = ($this->env == 'production') ? $this->get_option('apikey_live') : $this->get_option('apikey_sandbox');

        $this->to_euro_rate = $this->get_option('to_euro_rate');
        ###############

        self::$log_enabled = $this->get_option('debug');

        $wallet = new MEOWallet_API($this->url, $this->apikey);


        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_api_wc_gateway_meowallet', array($this, 'meowallet_callback'));
    }

    /**
     * Logging method
     * @param  string $message
     */
    public static function log($message) {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = new WC_Logger();
            }
            self::$log->add('meowallet_wc', $message);
        }
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {

        $this->form_fields = include( plugin_dir_path(dirname(__FILE__)) . 'includes/settings-meowallet.php' );
    }

    /**
     * Admin Options
     *
     * Setup the gateway settings screen.
     * Override this in your gateway.
     *
     * @since 1.0.0
     */
    public function admin_options() {
        $image_path = plugins_url('assets/images/mw.png', dirname(__FILE__));
        ?>
        <!-- <h3><?php _e('MEO Wallet', 'meowallet_wc'); ?></h3> -->
        <a  class="webds_mf_logo" href="http://www.webds.pt" target="_blank"><img src="http://www.webds.pt/webds_logomail.png" alt="WebDS" /></a>
        <center>
            <?php echo "<a href=\"https://wallet.pt\"><img src=\"$image_path\" /></a>"; ?><br>
            <small>by <a href="http://www.webds.pt" target="_blank">WebDS</a></small>
        </center>
        <table class="form-table">
            <?php
            $this->generate_settings_html();
            ?>
        </table>
        <div class="webds_mf_footer">
            <?php _e('Uma empresa', 'meowallet_wc'); ?><br/>
            <a href="https://www.webhs.pt"><img src="https://www.webhs.pt/logowebhs.png" alt="WebHS" /></a><br>
            <?php _e('Soluções de alojamento web, registo de dominios e certificados SSL', 'meowallet_wc'); ?>
        </div>
        <?php
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id) {
        global $wallet;

        $order = new WC_Order($order_id);

        $client_details = array();
        $client_details['name'] = $_POST['billing_first_name'] . ' ' . $_POST['billing_last_name'];
        $client_details['email'] = $_POST['billing_email'];

        $client_address = array();
        $client_address['country'] = $_POST['billing_country'];
        $client_address['address'] = $_POST['billing_address_1'];
        $client_address['city'] = $_POST['billing_city'];
        $client_address['postalcode'] = $_POST['billing_postcode'];

        $items = array();
        if (sizeof($order->get_items()) > 0) {
            foreach ($order->get_items() as $item) {

                if ($item['qty']) {
                    $client_items = array();
                    $client_items['id'] = $item['product_id'];
                    $client_items['name'] = $item['name'];
                    $client_items['descr'] = '';
                    $client_items['qt'] = $item['qty'];

                    $items[] = $client_items;
                }
            }
        }
        if ($order->get_total_shipping() > 0) {
            $items[] = array(
                'id' => 'shippingfee',
                'price' => $order->get_total_shipping(),
                'quantity' => 1,
                'name' => 'Shipping Fee',
            );
        }
        if ($order->get_total_tax() > 0) {
            $items[] = array(
                'id' => 'taxfee',
                'price' => $order->get_total_tax(),
                'quantity' => 1,
                'name' => 'Tax',
            );
        }
        if ($order->get_order_discount() > 0) {
            $items[] = array(
                'id' => 'totaldiscount',
                'price' => $order->get_total_discount() * -1,
                'quantity' => 1,
                'name' => 'Total Discount'
            );
        }
        if (sizeof($order->get_fees()) > 0) {
            $fees = $order->get_fees();
            $i = 0;
            foreach ($fees as $item) {
                $items[] = array(
                    'id' => 'itemfee' . $i,
                    'price' => $item['line_total'],
                    'quantity' => 1,
                    'name' => $item['name'],
                );
                $i++;
            }
        }

        $params = array(
            'payment' => array(
                'client' => array(
                    'name' => $order->billing_first_name . ' ' . $order->billing_last_name,
                    'email' => $_POST['billing_email'],
                    'address' => array(
                        'country' => $_POST['billing_country'],
                        'address' => $_POST['billing_address_1'],
                        'city' => $_POST['billing_city'],
                        'postalcode' => $_POST['billing_postcode']
                    )
                ),
                'amount' => $order->get_total(),
                'currency' => 'EUR',
                'items' => $items,
                'ext_invoiceid' => (string) $order_id,
            ),
            'url_confirm' => $order->get_checkout_order_received_url(),
            'url_cancel' => $order->get_checkout_payment_url($on_checkout = false)
        );


        $response = $wallet->post('checkout', $params);

        return array(
            'result' => 'success',
            'redirect' => $response->url_redirect
        );
    }

    /**
     * Check for MEO Wallet Response
     */
    function meowallet_callback() {
        global $wallet;
        @ob_clean();

        $verbatim_callback = file_get_contents('php://input');
        $callback = json_decode($verbatim_callback);


        if (false === $wallet->post('callback/verify', $verbatim_callback)) {
            $this->log('API Response: Invalid Callback ID: ' . $callback->operation_id);
            header('HTTP/1.1 400 Bad Request', true, 400);
        }

        //$this->log('API Response: Valid Callback ID: ' . $callback->operation_id);
        $this->log('API Response: Callback: ' . print_r($callback, true));

        if ($callback->operation_status == 'COMPLETED') {
            $wc_order = new WC_Order(absint($callback->ext_invoiceid));
            $wc_order->add_order_note(__('MEO Wallet pago usando: ', 'meowallet_wc') . $callback->method);
            if ($callback->method == 'MB')
                $wc_order->add_order_note(sprintf(__('Entidade: %s<br>Referência: $s<br>Valor: $s', 'meowallet_wc'), $callback->mb_entity, $callback->mb_ref, $callback->amount));
            $wc_order->payment_complete();
        }
        if ($callback->operation_status == 'PENDING') {
            $wc_order = new WC_Order(absint($callback->ext_invoiceid));
            $wc_order->add_order_note(__('MEO Wallet aguarda pagamento usando: ', 'meowallet_wc') . $callback->method);
            if ($callback->method == 'MB')
                $wc_order->add_order_note(sprintf(__('Entidade: %s<br>Referência: $s<br>Valor: $s', 'meowallet_wc'), $callback->mb_entity, $callback->mb_ref, $callback->amount));
        }
    }

}
