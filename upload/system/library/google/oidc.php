<?php
namespace Google;

class Oidc {
	const AUTHORIZATION_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
	const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
	const JWKS_ENDPOINT = 'https://www.googleapis.com/oauth2/v3/certs';
	const ISSUER = 'https://accounts.google.com';

	private $cache;

	public function __construct($registry) {
		$this->cache = $registry->get('cache');
	}

	public function getAuthorizationUrl($client_id, $redirect_uri, $state, $nonce, $code_challenge) {
		$params = array(
			'client_id'             => $client_id,
			'redirect_uri'          => $redirect_uri,
			'response_type'         => 'code',
			'scope'                 => 'openid email profile',
			'state'                 => $state,
			'nonce'                 => $nonce,
			'code_challenge'        => $code_challenge,
			'code_challenge_method' => 'S256',
			'prompt'                => 'select_account'
		);

		return self::AUTHORIZATION_ENDPOINT . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
	}

	public function exchangeAuthorizationCode($client_id, $client_secret, $redirect_uri, $code, $code_verifier) {
		$response = $this->request(self::TOKEN_ENDPOINT, array(
			'code'          => $code,
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'redirect_uri'  => $redirect_uri,
			'grant_type'    => 'authorization_code',
			'code_verifier' => $code_verifier
		));

		if (empty($response['id_token']) || !is_string($response['id_token'])) {
			throw new \RuntimeException('Google token response did not contain an ID token.');
		}

		return $response;
	}

	public function verifyIdToken($id_token, $client_id, $nonce) {
		if (!function_exists('openssl_verify')) {
			throw new \RuntimeException('OpenSSL is required for Google authentication.');
		}

		if (!is_string($id_token) || strlen($id_token) > 20000) {
			throw new \RuntimeException('Invalid Google ID token.');
		}

		$segments = explode('.', $id_token);

		if (count($segments) !== 3) {
			throw new \RuntimeException('Invalid Google ID token structure.');
		}

		$header_json = $this->base64UrlDecode($segments[0]);
		$claims_json = $this->base64UrlDecode($segments[1]);
		$signature = $this->base64UrlDecode($segments[2]);

		if ($header_json === false || $claims_json === false || $signature === false) {
			throw new \RuntimeException('Invalid Google ID token encoding.');
		}

		$header = json_decode($header_json, true);
		$claims = json_decode($claims_json, true);

		if (!is_array($header) || !is_array($claims)) {
			throw new \RuntimeException('Invalid Google ID token payload.');
		}

		if (!isset($header['alg'], $header['kid']) || $header['alg'] !== 'RS256' || !is_string($header['kid'])) {
			throw new \RuntimeException('Unsupported Google ID token signature.');
		}

		$jwk = $this->findSigningKey($header['kid']);

		if (!$jwk) {
			$jwk = $this->findSigningKey($header['kid'], true);
		}

		if (!$jwk) {
			throw new \RuntimeException('Google signing key was not found.');
		}

		$public_key = $this->jwkToPem($jwk);
		$verified = openssl_verify($segments[0] . '.' . $segments[1], $signature, $public_key, OPENSSL_ALGO_SHA256);

		if ($verified !== 1) {
			throw new \RuntimeException('Google ID token signature verification failed.');
		}

		$this->validateClaims($claims, $client_id, $nonce);

		return $claims;
	}

	private function validateClaims(array $claims, $client_id, $nonce) {
		$now = time();

		if (empty($claims['iss']) || ($claims['iss'] !== self::ISSUER && $claims['iss'] !== 'accounts.google.com')) {
			throw new \RuntimeException('Invalid Google ID token issuer.');
		}

		$audiences = isset($claims['aud']) ? (array)$claims['aud'] : array();

		if (!in_array($client_id, $audiences, true)) {
			throw new \RuntimeException('Invalid Google ID token audience.');
		}

		if ((count($audiences) > 1 || isset($claims['azp'])) && (!isset($claims['azp']) || $claims['azp'] !== $client_id)) {
			throw new \RuntimeException('Invalid Google ID token authorized party.');
		}

		if (!isset($claims['exp']) || !is_numeric($claims['exp']) || (int)$claims['exp'] < ($now - 60)) {
			throw new \RuntimeException('Expired Google ID token.');
		}

		if (isset($claims['iat']) && (!is_numeric($claims['iat']) || (int)$claims['iat'] > ($now + 300))) {
			throw new \RuntimeException('Invalid Google ID token issue time.');
		}

		if (isset($claims['nbf']) && (!is_numeric($claims['nbf']) || (int)$claims['nbf'] > ($now + 60))) {
			throw new \RuntimeException('Google ID token is not valid yet.');
		}

		if (!isset($claims['nonce']) || !is_string($claims['nonce']) || !hash_equals($nonce, $claims['nonce'])) {
			throw new \RuntimeException('Invalid Google ID token nonce.');
		}

		if (empty($claims['sub']) || !is_string($claims['sub']) || strlen($claims['sub']) > 255) {
			throw new \RuntimeException('Invalid Google account identifier.');
		}

		if (empty($claims['email']) || !is_string($claims['email']) || !filter_var($claims['email'], FILTER_VALIDATE_EMAIL)) {
			throw new \RuntimeException('Google account did not provide a valid email address.');
		}

		$email_verified = isset($claims['email_verified']) && ($claims['email_verified'] === true || $claims['email_verified'] === 'true' || $claims['email_verified'] === 1 || $claims['email_verified'] === '1');

		if (!$email_verified) {
			throw new \RuntimeException('Google email address is not verified.');
		}
	}

	private function findSigningKey($kid, $force_refresh = false) {
		$jwks = false;

		if (!$force_refresh) {
			$jwks = $this->cache->get('google.oidc.jwks');
		}

		if (!is_array($jwks) || empty($jwks['keys'])) {
			$jwks = $this->request(self::JWKS_ENDPOINT);
			$this->cache->set('google.oidc.jwks', $jwks);
		}

		foreach ($jwks['keys'] as $key) {
			if (isset($key['kid'], $key['kty'], $key['n'], $key['e']) && hash_equals((string)$key['kid'], $kid) && $key['kty'] === 'RSA') {
				if (isset($key['alg']) && $key['alg'] !== 'RS256') {
					continue;
				}

				if (isset($key['use']) && $key['use'] !== 'sig') {
					continue;
				}

				return $key;
			}
		}

		return false;
	}

	private function request($url, array $post_data = null) {
		if (!function_exists('curl_init')) {
			throw new \RuntimeException('cURL is required for Google authentication.');
		}

		$curl = curl_init($url);

		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => false,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT        => 12,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_HTTPHEADER     => array('Accept: application/json')
		);

		if ($post_data !== null) {
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = http_build_query($post_data, '', '&', PHP_QUERY_RFC3986);
			$options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
		}

		curl_setopt_array($curl, $options);

		$body = curl_exec($curl);
		$status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$error = curl_error($curl);
		curl_close($curl);

		if ($body === false || $error !== '' || $status < 200 || $status >= 300) {
			throw new \RuntimeException('Google authentication service request failed.');
		}

		$data = json_decode($body, true);

		if (!is_array($data)) {
			throw new \RuntimeException('Invalid response from Google authentication service.');
		}

		return $data;
	}

	private function jwkToPem(array $jwk) {
		$modulus = $this->base64UrlDecode($jwk['n']);
		$exponent = $this->base64UrlDecode($jwk['e']);

		if ($modulus === false || $exponent === false) {
			throw new \RuntimeException('Invalid Google signing key.');
		}

		$rsa_public_key = $this->asn1Sequence($this->asn1Integer($modulus) . $this->asn1Integer($exponent));
		$algorithm_identifier = hex2bin('300d06092a864886f70d0101010500');
		$bit_string = "\x03" . $this->asn1Length(strlen($rsa_public_key) + 1) . "\x00" . $rsa_public_key;
		$subject_public_key_info = $this->asn1Sequence($algorithm_identifier . $bit_string);

		return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($subject_public_key_info), 64, "\n") . "-----END PUBLIC KEY-----\n";
	}

	private function asn1Integer($value) {
		$value = ltrim($value, "\x00");

		if ($value === '') {
			$value = "\x00";
		}

		if ((ord($value[0]) & 0x80) !== 0) {
			$value = "\x00" . $value;
		}

		return "\x02" . $this->asn1Length(strlen($value)) . $value;
	}

	private function asn1Sequence($value) {
		return "\x30" . $this->asn1Length(strlen($value)) . $value;
	}

	private function asn1Length($length) {
		if ($length < 128) {
			return chr($length);
		}

		$encoded = ltrim(pack('N', $length), "\x00");

		return chr(0x80 | strlen($encoded)) . $encoded;
	}

	private function base64UrlDecode($value) {
		if (!is_string($value) || $value === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
			return false;
		}

		$remainder = strlen($value) % 4;

		if ($remainder === 1) {
			return false;
		}

		if ($remainder > 0) {
			$value .= str_repeat('=', 4 - $remainder);
		}

		return base64_decode(strtr($value, '-_', '+/'), true);
	}
}
