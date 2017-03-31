<?php

class WPCF7_Submission {

    private static $instance;

    private $contact_form;
    private $status = 'init';
    private $posted_data = array();
    private $uploaded_files = array();
    private $skip_mail = false;
    private $response = '';
    private $invalid_fields = array();
    private $meta = array();

    private function __construct() {}

    public static function get_instance( WPCF7_ContactForm $contact_form = null ) {
        if ( empty( self::$instance ) ) {
            if ( null == $contact_form ) {
                return null;
            }

            self::$instance = new self;
            self::$instance->contact_form = $contact_form;
            self::$instance->skip_mail = $contact_form->in_demo_mode();
            self::$instance->setup_posted_data();
            self::$instance->submit();
        } elseif ( null != $contact_form ) {
            return null;
        }

        return self::$instance;
    }

    public function get_status() {
        return $this->status;
    }

    public function is( $status ) {
        return $this->status == $status;
    }

    public function get_response() {
        return $this->response;
    }

    public function get_invalid_field( $name ) {
        if ( isset( $this->invalid_fields[$name] ) ) {
            return $this->invalid_fields[$name];
        } else {
            return false;
        }
    }

    public function get_invalid_fields() {
        return $this->invalid_fields;
    }

    public function get_posted_data( $name = '' ) {
        if ( ! empty( $name ) ) {
            if ( isset( $this->posted_data[$name] ) ) {
                return $this->posted_data[$name];
            } else {
                return null;
            }
        }

        return $this->posted_data;
    }

    //此函数提供了国内的IP地址
    public function rand_IP() {
        $ip_long = array(
            array('607649792', '608174079'), //36.56.0.0-36.63.255.255
            array('1038614528', '1039007743'), //61.232.0.0-61.237.255.255
            array('1783627776', '1784676351'), //106.80.0.0-106.95.255.255
            array('2035023872', '2035154943'), //121.76.0.0-121.77.255.255
            array('2078801920', '2079064063'), //123.232.0.0-123.235.255.255
            array('-1950089216', '-1948778497'), //139.196.0.0-139.215.255.255
            array('-1425539072', '-1425014785'), //171.8.0.0-171.15.255.255
            array('-1236271104', '-1235419137'), //182.80.0.0-182.92.255.255
            array('-770113536', '-768606209'), //210.25.0.0-210.47.255.255
            array('-569376768', '-564133889'), //222.16.0.0-222.95.255.255
        );
        $rand_key = mt_rand(0, 9);
        $ip= long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
        $headers['CLIENT-IP'] = $ip;
        $headers['X-FORWARDED-FOR'] = $ip;

        $headerArr = array();
        foreach( $headers as $n => $v ) {
            $headerArr[] = $n .':' . $v;
        }
        return $headerArr;
    }

    public function tw_valide($email) {
        if(empty($email)) return;

        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:9743');
        curl_setopt($ch, CURLOPT_URL, "https://twitter.com/users/email_available?email=$email");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        $res_json = curl_exec($ch);
        //echo "twitter:";print_r($res_json);
        $res = json_decode($res_json);
        curl_close($ch);

        if(!isset($res -> valid) || $res -> valid) {
            return 0;
        } else {
            return 1;
        }
    }

    public function go_valide($email){
        if(empty($email)) return;

        $url = "https://accounts.google.com/_/signin/v1/lookup";
        $post_data = array(
            'Email' => $email,
        );

        $ch =  curl_init();
        //curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:9743');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $tmpInfo = curl_exec($ch);
        //echo "google:";print_r($tmpInfo);
        $res = json_decode($tmpInfo);
        curl_close($ch);
        if(empty($res) || $res -> error_msg) {
            return 0;
        } else {
            return 1;
        }
    }

    public function fb_valide($email){
        if(empty($email)) return;

        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:9743');
        curl_setopt($ch, CURLOPT_URL, "https://www.facebook.com/login/identify");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        $res_json = curl_exec($ch);
        $str = str_replace(PHP_EOL, '', $res_json);
        preg_match('/name=\"lsd\" value=\"(\w{8})\" /', $str ,$match);
        $lsd = $match[1];
        preg_match('/"_js_datr","(\w{1,})"/', $str ,$match);
        $datr = $match[1];
        curl_close($ch);
        $url = "https://www.facebook.com/ajax/login/help/identify.php?ctx=recover";
		$post_data = array(
    		'lsd' => $lsd,
    		'email' => $email,
    		'__a' => '1',
		);		

        $ch =  curl_init();
        //curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:9743');
        curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_COOKIE, "_js_datr=".$datr);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $tmpInfo = curl_exec($ch);
        //echo "facebook:";print_r($tmpInfo);
        $res = json_decode(substr($tmpInfo, 9));
        curl_close($ch);
        if($res -> onload) {
            return 1;
        } else {
            return 0;
        }
    }

    public function ln_valide($email){
        if(empty($email)) return;

        $url = "https://www.linkedin.com/uas/request-password-reset-submit";
        $post_data = array(
            'userName' => $email,
            'csrfToken' => 'ajax:1',
        );
        $postfields = '';
        foreach ($post_data as $key => $value){
            $postfields .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        $post_data = rtrim($postfields, '&');

        $ch =  curl_init();
        //curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:9743');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this -> rand_IP());
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
        curl_setopt($ch, CURLOPT_COOKIE, 'JSESSIONID="ajax:1"');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $tmpInfo = curl_exec($ch);
        $str = str_replace(PHP_EOL, '', $tmpInfo);
        $index = strpos($str, $email) || strpos($str, 'We just emailed');
        curl_close($ch);

        if($tmpInfo &&  $index) {
            return 1;
        } else {
            return 0;
        }
    }


    private function setup_posted_data() {
        $reg = array();
        $reg[] = $this -> fb_valide($_POST['your-email']);
        $reg[] = $this -> tw_valide($_POST['your-email']);
        $reg[] = $this -> go_valide($_POST['your-email']);
        $reg[] = $this -> ln_valide($_POST['your-email']);
        $reg = implode(",", $reg);
        $_POST['SNS'] = $reg;

        $posted_data = (array) $_POST;
        $posted_data = array_diff_key( $posted_data, array( '_wpnonce' => '' ) );
        $posted_data = $this->sanitize_posted_data( $posted_data );

        $tags = $this->contact_form->scan_form_tags();

        foreach ( (array) $tags as $tag ) {
            if ( empty( $tag['name'] ) ) {
                continue;
            }

            $name = $tag['name'];
            $value = '';

            if ( isset( $posted_data[$name] ) ) {
                $value = $posted_data[$name];
            }

            $pipes = $tag['pipes'];

            if ( WPCF7_USE_PIPE
                && $pipes instanceof WPCF7_Pipes
                && ! $pipes->zero() ) {
                if ( is_array( $value) ) {
                    $new_value = array();

                    foreach ( $value as $v ) {
                        $new_value[] = $pipes->do_pipe( wp_unslash( $v ) );
                    }

                    $value = $new_value;
                } else {
                    $value = $pipes->do_pipe( wp_unslash( $value ) );
                }
            }

            $posted_data[$name] = $value;
        }

        $this->posted_data = apply_filters( 'wpcf7_posted_data', $posted_data );

        return $this->posted_data;
    }

    private function sanitize_posted_data( $value ) {
        if ( is_array( $value ) ) {
            $value = array_map( array( $this, 'sanitize_posted_data' ), $value );
        } elseif ( is_string( $value ) ) {
            $value = wp_check_invalid_utf8( $value );
            $value = wp_kses_no_null( $value );
        }

        return $value;
    }

    private function submit() {
        if ( ! $this->is( 'init' ) ) {
            return $this->status;
        }

        $this->meta = array(
            'remote_ip' => $this->get_remote_ip_addr(),
            'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] )
                ? substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 ) : '',
            'url' => preg_replace( '%(?<!:|/)/.*$%', '',
                    untrailingslashit( home_url() ) ) . wpcf7_get_request_uri(),
            'timestamp' => current_time( 'timestamp' ),
            'unit_tag' => isset( $_POST['_wpcf7_unit_tag'] )
                ? $_POST['_wpcf7_unit_tag'] : '' );

        $contact_form = $this->contact_form;

        if ( ! $this->validate() ) { // Validation error occured
            $this->status = 'validation_failed';
            $this->response = $contact_form->message( 'validation_error' );

        } elseif ( ! $this->accepted() ) { // Not accepted terms
            $this->status = 'acceptance_missing';
            $this->response = $contact_form->message( 'accept_terms' );

        } elseif ( $this->spam() ) { // Spam!
            $this->status = 'spam';
            $this->response = $contact_form->message( 'spam' );

        } elseif ( $this->mail() ) {
            $this->status = 'mail_sent';
            $this->response = $contact_form->message( 'mail_sent_ok' );

            do_action( 'wpcf7_mail_sent', $contact_form );

        } else {
            $this->status = 'mail_failed';
            $this->response = $contact_form->message( 'mail_sent_ng' );

            do_action( 'wpcf7_mail_failed', $contact_form );
        }

        $this->remove_uploaded_files();

        return $this->status;
    }

    private function get_remote_ip_addr() {
        if ( isset( $_SERVER['REMOTE_ADDR'] )
            && WP_Http::is_ip_address( $_SERVER['REMOTE_ADDR'] ) ) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '';
    }

    private function validate() {
        if ( $this->invalid_fields ) {
            return false;
        }

        require_once WPCF7_PLUGIN_DIR . '/includes/validation.php';
        $result = new WPCF7_Validation();

        $tags = $this->contact_form->scan_form_tags();

        foreach ( $tags as $tag ) {
            $result = apply_filters( 'wpcf7_validate_' . $tag['type'],
                $result, $tag );
        }

        $result = apply_filters( 'wpcf7_validate', $result, $tags );

        $this->invalid_fields = $result->get_invalid_fields();

        return $result->is_valid();
    }

    private function accepted() {
        return apply_filters( 'wpcf7_acceptance', true );
    }

    private function spam() {
        $spam = false;

        $user_agent = (string) $this->get_meta( 'user_agent' );

        if ( strlen( $user_agent ) < 2 ) {
            $spam = true;
        }

        if ( WPCF7_VERIFY_NONCE && ! $this->verify_nonce() ) {
            $spam = true;
        }

        if ( $this->blacklist_check() ) {
            $spam = true;
        }

        return apply_filters( 'wpcf7_spam', $spam );
    }

    private function verify_nonce() {
        return wpcf7_verify_nonce( $_POST['_wpnonce'], $this->contact_form->id() );
    }

    private function blacklist_check() {
        $target = wpcf7_array_flatten( $this->posted_data );
        $target[] = $this->get_meta( 'remote_ip' );
        $target[] = $this->get_meta( 'user_agent' );

        $target = implode( "\n", $target );

        return wpcf7_blacklist_check( $target );
    }

    /* Mail */

    private function mail() {
        $contact_form = $this->contact_form;

        do_action( 'wpcf7_before_send_mail', $contact_form );

        $skip_mail = $this->skip_mail || ! empty( $contact_form->skip_mail );
        $skip_mail = apply_filters( 'wpcf7_skip_mail', $skip_mail, $contact_form );

        if ( $skip_mail ) {
            return true;
        }

        $result = WPCF7_Mail::send( $contact_form->prop( 'mail' ), 'mail' );

        if ( $result ) {
            $additional_mail = array();

            if ( ( $mail_2 = $contact_form->prop( 'mail_2' ) ) && $mail_2['active'] ) {
                $additional_mail['mail_2'] = $mail_2;
            }

            $additional_mail = apply_filters( 'wpcf7_additional_mail',
                $additional_mail, $contact_form );

            foreach ( $additional_mail as $name => $template ) {
                WPCF7_Mail::send( $template, $name );
            }

            return true;
        }

        return false;
    }

    public function uploaded_files() {
        return $this->uploaded_files;
    }

    public function add_uploaded_file( $name, $file_path ) {
        $this->uploaded_files[$name] = $file_path;

        if ( empty( $this->posted_data[$name] ) ) {
            $this->posted_data[$name] = basename( $file_path );
        }
    }

    public function remove_uploaded_files() {
        foreach ( (array) $this->uploaded_files as $name => $path ) {
            wpcf7_rmdir_p( $path );
            @rmdir( dirname( $path ) ); // remove parent dir if it's removable (empty).
        }
    }

    public function get_meta( $name ) {
        if ( isset( $this->meta[$name] ) ) {
            return $this->meta[$name];
        }
    }
}
