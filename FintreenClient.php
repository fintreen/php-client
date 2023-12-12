<?php

declare(strict_types=1);

namespace Fintreen;

class FintreenClient {
    public const DEFAULT_FIAT_CODE = 'EUR';

    protected $baseUrl = 'https://fintreen.com/';

    protected $suffix = 'api/v1/';

    protected bool $ignoreSslVerif = false;

    private string $token;
    private string $email;

    protected bool $isTest;

    static private $fintreenCurrencies = [];

    public function __construct(string|null $token = null, string|null $email = null, bool $isTest = false, $ignoreSslVerif = false)
    {
        $this->token = $token;
        $this->email = $email;
        $this->isTest = $isTest;
        $this->ignoreSslVerif = $ignoreSslVerif;
    }

    public function getCurrenciesList(): array|null {
        if (!self::$fintreenCurrencies) {
            $resp = $this->sendRequest('currencies');
            $currencies = @json_decode($resp, true);
            self::$fintreenCurrencies = $currencies;
        }
        return self::$fintreenCurrencies;
    }

    public function getOrderStatusList(): array|null {
        $resp = $this->sendRequest('order/statuses');
        return @json_decode($resp, true);
    }

    public function calculate(float $amount, string|array $cryptoCodes, string $fiatCode = self::DEFAULT_FIAT_CODE): array|null {
        if (is_array($cryptoCodes)) {
            $cryptoCodes = implode(',', $cryptoCodes);
        }
        $cryptoCodes = trim($cryptoCodes, ',');
        $params = [
            'fiatAmount' => $amount,
            'fiatCode' => $fiatCode,
            'cryptoCodes' => $cryptoCodes,
        ];
        $calcData = $this->sendRequest('calculate', 'GET', $params);
        return @json_decode($calcData, true);
    }

    public function createTransaction(float $amount, string $cryptoCode, string $fiatCode = self::DEFAULT_FIAT_CODE ): array|null {
        $params = [];
        $params['fiatAmount'] = $amount;
        $params['fiatCode'] = $fiatCode;
        $params['cryptoCode'] = $cryptoCode;
        $response = $this->sendRequest('create', 'POST', $params);
        return @json_decode($response, true);
    }

    public function checkTransaction(int $orderId): array|null {
        $params['orderId'] = $orderId;
        $response = $this->sendRequest('check', 'GET', $params);
        return @json_decode($response, true);
    }

    public function getTransactionsList(array $filters = []): array|null {
        if (!isset($filters['isTest'])) {
            $filters['isTest'] = (int)$this->isTest;
        }
        $response = $this->sendRequest('transactions', 'GET', $filters);
        return @json_decode($response, true);
    }


    private function createSignature(): string {
        return sha1($this->token . $this->email);
    }

    /**
     * Yes it set to public to allow developers send everything they need whn api will be extended.
     *
     * @param string $endpoint
     * @param string $method
     * @param array $params
     * @return string|null|bool
     */
    public function sendRequest(string $endpoint, string $method = 'GET', array $params = []): string|null|bool {
        $urlToSend = $this->baseUrl . $this->suffix . $endpoint;
        if ($params) {
            ksort($params);
        }
        if ($this->isTest) {
            $params['isTest'] = 1;
        }

        if ($params) {
            $buildedParams = http_build_query($params);
            $urlToSend .= '?' . $buildedParams;
        }

        $curl = curl_init($urlToSend);

        $signature = $this->createSignature();

        $headers = [
            'fintreen_auth: ' . $this->token,
            'fintreen_signature: ' . $signature,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $curl_options = [
            CURLOPT_URL => $urlToSend,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers
        ];
        if ($this->ignoreSslVerif) {
            $curl_options[CURLOPT_SSL_VERIFYHOST] = 0;
            $curl_options[CURLOPT_SSL_VERIFYPEER] = false;
        } else {
            $curl_options[CURLOPT_SSL_VERIFYHOST] = 2;
            $curl_options[CURLOPT_SSL_VERIFYPEER] = true;
        }

        if ($method == 'GET') {
            $curl_options[CURLOPT_HTTPGET] = true;
        } else {
            $curl_options[CURLOPT_POSTFIELDS] = $buildedParams;
            $curl_options[CURLOPT_POST] = true;
        }

        curl_setopt_array($curl, $curl_options);
        $this->response = curl_exec($curl);
        $this->info = curl_getinfo($curl);
        $this->errno = curl_errno($curl);
        $this->error = curl_error($curl);

        return $this->response;
    }
}
