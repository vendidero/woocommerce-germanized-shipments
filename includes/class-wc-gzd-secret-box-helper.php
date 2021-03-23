<?php

if ( ! class_exists( 'WC_GZD_Secret_Box_Helper' ) ) {

	class WC_GZD_Secret_Box_Helper {

		public static function get_encryption_key_constant( $encryption_type = '' ) {
			return apply_filters( 'woocommerce_gzd_encryption_key_constant', 'WC_GZD_ENCRYPTION_KEY', $encryption_type );
		}

		/**
		 * @param string $salt
		 * @param string $encryption_type
		 *
		 * @return array|WP_Error
		 */
		public static function get_encryption_key_data( $salt = '', $encryption_type = '' ) {
			$result = array(
				'key'  => '',
				'salt' => ! empty( $salt ) ? $salt : random_bytes( SODIUM_CRYPTO_PWHASH_SALTBYTES ),
			);

			if ( self::has_valid_encryption_key( $encryption_type ) ) {
				$result['key'] = base64_decode( constant( self::get_encryption_key_constant( $encryption_type ) ) );
			} else {
				try {
					$pw             = LOGGED_IN_KEY;
					$result['key']  = sodium_crypto_pwhash(
						SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
						$pw,
						$result['salt'],
						SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
						SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
					);

					sodium_memzero( $pw );
				} catch ( \Exception $e ) {
					return self::log_error( new WP_Error( 'encrypt-key-error', sprintf( 'Error while retrieving encryption key: %s', wc_print_r( $e, true ) ) ) );
				}
			}

			return $result;
		}

		public static function has_valid_encryption_key( $encryption_type = '' ) {
			return defined( self::get_encryption_key_constant( $encryption_type ) );
		}

		/**
		 * @param $message
		 * @param string $encryption_type
		 *
		 * @return string|WP_Error
		 */
		public static function encrypt( $message, $encryption_type = '' ) {
			try {
				$key_data = self::get_encryption_key_data( $encryption_type );
				$nonce    = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

				return base64_encode( $key_data['salt'] . $nonce . sodium_crypto_secretbox( $message, $nonce, $key_data['key'] ) );
			} catch ( \Exception $e ) {
				return self::log_error( new WP_Error( 'encrypt-error', sprintf( 'Error while encrypting data: %s', wc_print_r( $e, true ) ) ) );
			}
		}

		/**
		 * Decrypts a message of a certain type.
		 *
		 * @param $cipher
		 * @param string $encryption_type
		 *
		 * @return WP_Error|mixed
		 */
		public static function decrypt( $cipher, $encryption_type = '' ) {
			$decoded = base64_decode( $cipher );
			$error   = new \WP_Error();

			if ( $decoded === false ) {
				$error->add( 'decrypt-decode', 'Error while decoding the encrypted message.' );
				return self::log_error( $error );
			}

			try {
				if ( mb_strlen( $decoded, '8bit' ) < ( SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) ) {
					$error->add( 'decrypt-truncate', 'Message was truncated.' );
					return self::log_error( $error );
				}

				$salt       = mb_substr( $decoded, 0, SODIUM_CRYPTO_PWHASH_SALTBYTES, '8bit' );
				$key_data   = self::get_encryption_key_data( $salt, $encryption_type );
				$key        = $key_data['key'];
				$nonce      = mb_substr( $decoded, SODIUM_CRYPTO_PWHASH_SALTBYTES, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' );
				$ciphertext = mb_substr( $decoded, SODIUM_CRYPTO_PWHASH_SALTBYTES + SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit' );

				$plain = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

				if ( $plain === false ) {
					$error->add( 'decrypt', 'Message could not be decrypted.' );
					return self::log_error( $error );
				}

				sodium_memzero( $ciphertext );
				sodium_memzero( $key );

				return $plain;
			} catch ( \Exception $e ) {
				$error->add( 'decrypt-error', sprintf( 'Error while decrypting data: %s', wc_print_r( $e, true ) ) );
				return self::log_error( $error );
			}
		}

		/**
		 * @return string|WP_Error
		 */
		public static function get_new_encryption_key() {
			try {
				$secret_key = sodium_crypto_secretbox_keygen();

				return base64_encode( $secret_key );
			} catch ( \Exception $e ) {
				return self::log_error( new WP_Error( 'encrypt-key-error', sprintf( 'Error while creating new encryption key: %s', wc_print_r( $e, true ) ) ) );
			}
		}

		/**
		 * @param \WP_Error $error
		 */
		protected static function log_error( $error ) {
			update_option( 'woocommerce_gzd_has_encryption_error', 'yes' );

			if ( apply_filters( 'woocommerce_gzd_encryption_enable_logging', true ) && ( $logger = wc_get_logger() ) ) {
				foreach( $error->get_error_messages() as $message ) {
					$logger->error( $message, array( 'source' => apply_filters( 'woocommerce_gzd_encryption_log_context', 'wc-gzd-encryption' ) ) );
				}
			}

			return $error;
		}

		public static function has_errors() {
			return 'yes' === get_option( 'woocommerce_gzd_has_encryption_error', 'no' );
		}
	}
}