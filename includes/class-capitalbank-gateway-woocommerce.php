<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Gateway_Kapitalbank class.
 *
 * @since 1.0.0
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Kapitalbank extends WC_Payment_Gateway
{

    /**
     * Constructor
     */
    public function __construct()
    {

        // Register plugin information

        $this->id = 'kapitalbank';
        $this->method_title = 'kapitalbank';
        $this->has_fields = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->select_mode = $this->settings['select_mode'];
        $this->redirect_page_id = $this->settings['redirect_page_id'];
        $this->icon = WC_Kapitalbank_PLUGIN_URL . '/images/logo.svg';

        $this->merch_name = get_bloginfo('name');
        $this->merch_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
        $this->email = get_bloginfo('admin_email');
        /*$this->backref = WC_Kapitalbank_PLUGIN_URL . '/capitalbank-gateway-woocommerce.php';*/

        $this->msg['message'] = "";
        $this->msg['class'] = "";

        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
        } else {
            add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        }
        // add_action('woocommerce_receipt_Kapitalbank', array(&$this, 'receipt_page'));
        add_action('woocommerce_thankyou_kapitalbank', array(&$this, 'receipt_page'), 1);
            add_action('woocommerce_view_order_kapitalbank', array(&$this, 'receipt_page'), 8);

    }

    /**
     * Initialize Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'Kapitalbank'),
                'type' => 'checkbox',
                'label' => __('Enable Kapitalbank Payment Module.', 'Kapitalbank'),
                'default' => 'no'],
            'title' => [
                'title' => __('Title:', 'Kapitalbank'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'Kapitalbank'),
                'default' => __('Kapitalbank', 'Kapitalbank')],
            'description' => [
                'title' => __('Description:', 'Kapitalbank'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'Kapitalbank'),
                'default' => __('Pay securely by Credit or Debit card or internet banking through Kapitalbank Secure Servers.', 'Kapitalbank')],

            'redirect_page_id' => [
                'title' => __('Return Page'),
                'type' => 'select',
                'options' => $this->get_pages_az('Select Page'),
                'description' => "URL of success page"
            ]
        ];
    }

    public function admin_options()
    {
        echo '<h3>' . __('Kapitalbank Payment Gateway', 'Kapitalbank') . '</h3>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this->generate_settings_html();
        echo '</table>';
    }

    /**
     *  There are no payment fields for Kapitalbank, but we want to show the description if set.
     **/
    public function payment_fields()
    {
        if ($this->description) echo wpautop(wptexturize($this->description));
    }

    /**
     * Receipt Page
     **/
    public function receipt_page($order)
    {
        $this->generate_kapitalbank_form($order);
    }

    public function hex2bin_az($hexdata)
    {
        $bindata = "";

        for ($i = 0; $i < strlen($hexdata); $i += 2) {
            $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
        }

        return $bindata;
    }

    public function genOrderID_az($str)
    {
        $len = strlen($str);

        if ($len < 6) {
            for ($i = 0; $i < 6 - $len; $i++) {
                $str = '0' . $str;
            }
        }
        return $str;
    }

    public function get_admin_settings()
    {
        $admin_settings = array();

        $admin_settings['url'] = $this->settings['url_production'];
        $admin_settings['terminal'] = $this->settings['terminal_production'];

        return $admin_settings;
    }

    public function showMessage($content)
    {
        return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
    }

    // get all pages
    public function get_pages_az($title = false, $indent = true)
    {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array();
        if ($title) $page_list[] = $title;
        foreach ($wp_pages as $page) {
            $prefix = '';
            // show indented child pages?
            if ($indent) {
                $has_parent = $page->post_parent;
                while ($has_parent) {
                    $prefix .= ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            // add to page list array array
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }

    /**
     * Generate Kapitalbank button link
     **/
    public function generate_kapitalbank_form($order_id)
    {


        $currency_codes = [
            'USD' => 840,
            'AZN' => 944
        ];

        $order = new WC_Order($order_id);

        $currency = $currency_codes[$order->currency] ?? '944';

        $ch = curl_init();
        $data = '<?xml version="1.0" encoding="UTF-8"?>
                        <TKKPG>
                        <Request>
                            <Operation>CreateOrder</Operation>
                            <Language>AZ</Language>
                            <Order>
                                <OrderType>Purchase</OrderType>
                                <Merchant>E1030016</Merchant>
                                <Amount>' . ($order->total * 100) . '</Amount>
                                <Currency>'.$currency.'</Currency>
                                <Description>' . $order_id . '</Description>
                                <ApproveURL>'.$this->merch_url.'</ApproveURL>
                                <CancelURL>'.$this->merch_url.'</CancelURL>
                                <DeclineURL>'.$this->merch_url.'</DeclineURL>
                            </Order>
                        </Request>
                        </TKKPG>
                        ';
        curl_setopt($ch, CURLOPT_URL, "https://e-commerce.kapitalbank.az:5443/Exec");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSLCERT, $_SERVER['DOCUMENT_ROOT'].'/wp-content/plugins/kapitalbank-payment-gateway/certs/certificate.crt');
        curl_setopt($ch, CURLOPT_SSLKEY, $_SERVER['DOCUMENT_ROOT'].'/wp-content/plugins/kapitalbank-payment-gateway/certs/private.key');
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, "PEM");
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        $response = curl_exec($ch);
        
        curl_close($ch);

        $xml = new \SimpleXMLElement($response);


        $order_id_remote = $xml->Response->Order->OrderID;
        $url = $xml->Response->Order->URL;
        $session_id = $xml->Response->Order->SessionID;

        update_post_meta($order_id, 'session_id', (string)$session_id);


        header("Location: $url?ORDERID=$order_id_remote&SESSIONID=$session_id&expm=05&expy=17");
        exit;
    }

    /**
     * Process the payment and return the result
     **/
    public function process_payment($order_id)
    {

        $order = wc_get_order($order_id);
        // Mark as on-hold (we're awaiting the cheque)
        $order->update_status('on-hold', _x('Awaiting check payment', 'Check payment method', 'Kapitalbank'));

        // Reduce stock levels
        wc_reduce_stock_levels($order_id);

        // Remove cart
        WC()->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );

    }



    /**
     * Check for valid Kapitalbank server callback
     **/
    public function check_Kapitalbank_response($data)
    {

        global $woocommerce;
        $order_id = $data->OrderDescription;

        if ($order_id) {
            try {
                $order = new WC_Order((int)$order_id);
                if (($order->status !== 'completed') && ((string)$data->SessionID == $order->get_meta('session_id'))) {

                         if ($data->OrderStatus == 'APPROVED') {

                             $this->msg['message'] = __('Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.', 'Kapitalbank');
                             $this->msg['class'] = 'woocommerce_message';

                             if ($order->status !== 'processing') {
                                 update_post_meta($order_id, 'order_rrn', $data->RRN);

                                 $order->payment_complete();
                                 $order->add_order_note(__('Kapitalbank payment successful<br/>Unnique Id from Kapitalbank: ' . $data->OrderID, 'Kapitalbank'));
                                 $order->add_order_note($this->msg['message']);
                                 $woocommerce->cart->empty_cart();
                             }

                         } else {

                             $this->msg['class'] = 'woocommerce_error';
                             $this->msg['message'] = __('Thank you for shopping with us. However, the transaction has been declined.', 'Kapitalbank');
                             $order->add_order_note(__('Transaction Declined: ', 'Kapitalbank') . $_REQUEST['Error']);
                         }

                     /*if (!$transauthorised) {
                         $order->update_status('failed');
                         $order->add_order_note('Failed');
                         $order->add_order_note($this->msg['message']);
                     }*/
                     add_action('the_content', array(&$this, 'showMessage'));
                 }

            }
            catch (Exception $e) {
                $msg = "Error";
            }

        }
    }

    //CURL query
    public function get_web_page($url, $data_in)
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING => "",       // handle all encodings
            CURLOPT_AUTOREFERER => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT => 120,      // timeout on response
            CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
            //-------to post-------------
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data_in, //$data,
            CURLOPT_SSL_VERIFYPEER => false,    // DONT VERIFY      
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CAINFO => "a.cer",
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;

        return $header;
    }
}

