<?php
// Plik: tna/src/lib/AllegroApiClient.php

// Wymaga zainstalowanej biblioteki GuzzleHttp: composer require guzzlehttp/guzzle
require_once __DIR__ . '/../../vendor/autoload.php'; // Zakładając użycie Composera

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class AllegroApiClient {
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $accessToken;
    private $refreshToken;
    private $isSandbox; // Flaga do używania środowiska testowego Allegro (sandbox)
    private $httpClient;

    const API_URL = 'https://api.allegro.pl';
    const API_URL_SANDBOX = 'https://api.allegro.pl.sandbox';
    const AUTH_URL = 'https://allegro.pl/auth/oauth';
    const AUTH_URL_SANDBOX = 'https://allegro.pl.sandbox/auth/oauth';

    public function __construct(string $clientId, string $clientSecret, string $redirectUri, bool $isSandbox = false) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->isSandbox = $isSandbox;

        $this->httpClient = new Client([
            'base_uri' => $this->isSandbox ? self::API_URL_SANDBOX : self::API_URL,
            'timeout'  => 10.0,
        ]);

        // TODO: Wczytaj zapisane tokeny (accessToken, refreshToken) z pliku/bazy danych
        // $this->accessToken = ...;
        // $this->refreshToken = ...;
    }

    private function getAuthUrl(): string {
        return $this->isSandbox ? self::AUTH_URL_SANDBOX : self::AUTH_URL;
    }

    public function getAuthorizationUrl(): string {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'prompt' => 'confirm' // Wymuś ekran zgody Allegro
        ]);
        return $this->getAuthUrl() . '/authorize?' . $params;
    }

    public function exchangeCodeForToken(string $code): bool {
        try {
            $response = $this->httpClient->post($this->getAuthUrl() . '/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                ],
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $this->accessToken = $data['access_token'];
            $this->refreshToken = $data['refresh_token'];
            $expiresIn = $data['expires_in']; // Czas ważności tokena w sekundach

            // TODO: Zapisz $this->accessToken, $this->refreshToken i czas wygaśnięcia
            // np. time() + $expiresIn

            return true;
        } catch (RequestException $e) {
            // TODO: Logowanie błędu
            error_log("Allegro Token Exchange Error: " . $e->getMessage());
            if ($e->hasResponse()) {
                error_log("Response Body: " . $e->getResponse()->getBody()->getContents());
            }
            return false;
        }
    }

    private function refreshAccessToken(): bool {
         if (!$this->refreshToken) {
             error_log("Allegro Refresh Error: No refresh token available.");
             return false;
         }
        try {
             // Logika odświeżania tokena (grant_type=refresh_token)
             // ... (podobne do exchangeCodeForToken, ale z refresh_token) ...
             // Zapisz nowy access token i refresh token (czasem refresh token też się zmienia)
             return true;
         } catch (RequestException $e) {
             error_log("Allegro Refresh Token Error: " . $e->getMessage());
              // Jeśli refresh token zawiedzie, może być potrzebna ponowna autoryzacja użytkownika
             $this->accessToken = null;
             $this->refreshToken = null;
             // TODO: Usuń zapisane tokeny
             return false;
         }
    }


    public function makeApiRequest(string $method, string $endpoint, array $options = []) {
        // TODO: Sprawdź, czy accessToken istnieje i nie wygasł.
        // Jeśli wygasł lub nie istnieje, spróbuj go odświeżyć za pomocą refreshAccessToken().
        // Jeśli odświeżenie się nie powiedzie, rzuć wyjątek lub zwróć błąd (wymagana ponowna autoryzacja).

        if (!$this->accessToken) {
             throw new \Exception("Allegro API Error: Missing Access Token. Please authorize the application.");
        }

        $defaultOptions = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/vnd.allegro.public.v1+json',
                'Content-Type' => 'application/vnd.allegro.public.v1+json', // Ważne dla PUT/POST
            ]
        ];

        // Łączenie domyślnych opcji z przekazanymi
        // Dla 'headers' i 'json'/'form_params' trzeba zrobić głębokie łączenie
         $requestOptions = array_replace_recursive($defaultOptions, $options);


        try {
            $response = $this->httpClient->request($method, $endpoint, $requestOptions);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                 $response = $e->getResponse();
                 $statusCode = $response->getStatusCode();
                 $body = $response->getBody()->getContents();

                 if ($statusCode === 401) { // Unauthorized - token wygasł lub niepoprawny
                     error_log("Allegro API Error 401: Unauthorized. Attempting token refresh...");
                     if ($this->refreshAccessToken()) {
                         // Spróbuj ponownie wykonać zapytanie z nowym tokenem
                         error_log("Token refreshed successfully. Retrying API request.");
                         // Zaktualizuj token w $requestOptions i ponów zapytanie
                         $requestOptions['headers']['Authorization'] = 'Bearer ' . $this->accessToken;
                         try {
                            $response = $this->httpClient->request($method, $endpoint, $requestOptions);
                            return json_decode($response->getBody()->getContents(), true);
                         } catch (RequestException $e2) {
                             error_log("Allegro API Error after refresh: " . $e2->getMessage());
                             // Rzuć wyjątek lub zwróć błąd - ponowne zapytanie też się nie udało
                              throw new \Exception("Allegro API Error after refresh: " . $e2->getMessage() . " Body: " . ($e2->hasResponse() ? $e2->getResponse()->getBody()->getContents() : 'N/A'), $e2->getCode(), $e2);
                         }

                     } else {
                         // Odświeżenie się nie powiodło
                         error_log("Token refresh failed. User needs to re-authorize.");
                         // Rzuć wyjątek lub zwróć błąd
                          throw new \Exception("Allegro API Error: Token refresh failed. Re-authorization required.", $statusCode, $e);
                     }
                 } else {
                     // Inny błąd API (np. 400 Bad Request, 404 Not Found, 422 Unprocessable Entity)
                     error_log("Allegro API Error {$statusCode}: " . $e->getMessage() . " Body: " . $body);
                      throw new \Exception("Allegro API Error {$statusCode}: " . $e->getMessage() . " Body: " . $body, $statusCode, $e);
                 }

            } else {
                 // Błąd połączenia itp.
                 error_log("Allegro Network Error: " . $e->getMessage());
                  throw new \Exception("Allegro Network Error: " . $e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    // --- Przykładowe metody API ---

    /**
     * Pobiera listę ofert sprzedającego.
     * @param array $params Opcjonalne parametry filtrowania (np. ['publication.status' => 'ACTIVE'])
     * @return array|null Lista ofert lub null w przypadku błędu.
     */
    public function getMyOffers(array $params = []): ?array {
        try {
             // Endpoint Allegro do pobierania ofert
             $endpoint = '/sale/offers?' . http_build_query($params);
             return $this->makeApiRequest('GET', $endpoint);
        } catch (\Exception $e) {
            error_log("Error fetching Allegro offers: " . $e->getMessage());
            return null;
        }
    }

     /**
      * Tworzy nową ofertę.
      * @param array $offerData Dane oferty zgodne ze schematem Allegro API.
      * @return array|null Dane utworzonej oferty lub null w przypadku błędu.
      */
     public function createOffer(array $offerData): ?array {
         try {
             // Endpoint do tworzenia ofert
             $endpoint = '/sale/offers';
             return $this->makeApiRequest('POST', $endpoint, ['json' => $offerData]);
         } catch (\Exception $e) {
             error_log("Error creating Allegro offer: " . $e->getMessage());
             return null;
         }
     }

     /**
      * Aktualizuje cenę w ofercie za pomocą komendy zmiany ceny.
      * @param string $offerId ID oferty
      * @param string $newPrice Nowa cena jako string (np. "123.45")
      * @return array|null Odpowiedź z API (status komendy) lub null w przypadku błędu.
      */
     public function updateOfferPrice(string $offerId, string $newPrice): ?array {
         $commandId = uniqid(); // Unikalne ID dla komendy
         $endpoint = "/sale/offers/{$offerId}/change-price-commands/{$commandId}";
         $data = [
             'input' => [
                 'buyNowPrice' => [
                     'amount' => $newPrice,
                     'currency' => 'PLN' // Upewnij się, że waluta jest poprawna
                 ]
             ],
             'scheduledAt' => null // null = natychmiastowa zmiana
         ];
         try {
             return $this->makeApiRequest('PUT', $endpoint, ['json' => $data]);
         } catch (\Exception $e) {
             error_log("Error updating Allegro price for offer {$offerId}: " . $e->getMessage());
             return null;
         }
     }

     /**
      * Aktualizuje stan magazynowy w ofercie za pomocą komendy zmiany ilości.
      * @param string $offerId ID oferty
      * @param int $newQuantity Nowa ilość
      * @return array|null Odpowiedź z API (status komendy) lub null w przypadku błędu.
      */
      public function updateOfferQuantity(string $offerId, int $newQuantity): ?array {
          $commandId = uniqid(); // Unikalne ID dla komendy
          $endpoint = "/sale/offer-quantity-change-commands/{$commandId}";
          $data = [
              'modification' => [
                  'changeType' => 'FIXED', // FIXED = ustaw konkretną wartość
                  'value' => $newQuantity
              ],
              'offerCriteria' => [
                  [
                      'offers' => [['id' => $offerId]],
                      'type' => 'CONTAINS_OFFERS'
                  ]
              ],
              // 'scheduledAt' => null // Można opcjonalnie zaplanować zmianę
          ];
           try {
               return $this->makeApiRequest('PUT', $endpoint, ['json' => $data]);
           } catch (\Exception $e) {
               error_log("Error updating Allegro quantity for offer {$offerId}: " . $e->getMessage());
               return null;
           }
      }


    // TODO: Dodać więcej metod do obsługi innych endpointów API (np. pobieranie kategorii, parametrów, kończenie ofert, aktualizacja pełnej oferty)
}