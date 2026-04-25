<?php

namespace App\Services\Scraping;

use App\Models\Zdroj;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * HTTP klient s podporou login session pro zdroje, které vyžadují přihlášení.
 *
 * Generický — zdroj v DB má:
 *   - login_url             — URL kam POST credentials
 *   - login_credentials     — šifrovaný JSON (např. {"login":"x","heslo":"y"})
 *   - login_session         — cache cookie jar (per request lifetime)
 *
 * Při scraping fetch používá session cookie. Pokud session expirovala,
 * provede nový login.
 */
class AuthenticatedHttp
{
    protected const UA = 'Mozilla/5.0 (compatible; RajonBot/1.0; +https://rajon.tuptudu.cz/)';
    protected const SESSION_TTL_HOURS = 6;

    /** Cache session cookie jars per zdroj (in-memory, jen pro current request). */
    protected array $sessions = [];

    /**
     * Fetch HTML — pro autentizovaný zdroj projde přes login session.
     */
    public function fetchHtml(string $url, ?Zdroj $zdroj = null): ?string
    {
        $jar = ($zdroj && $zdroj->vyzaduje_login && $zdroj->login_credentials)
            ? $this->getSession($zdroj)
            : null;

        try {
            $client = new Client([
                'cookies' => $jar ?: true,
                'timeout' => 30,
                'http_errors' => false,
                'headers' => [
                    'User-Agent' => self::UA,
                    'Accept-Language' => 'cs,en;q=0.5',
                ],
            ]);

            $response = $client->get($url);
            if ($response->getStatusCode() < 400) {
                return (string) $response->getBody();
            }
        } catch (\Exception $e) {
            Log::warning("AuthenticatedHttp fetch failed: {$url} — {$e->getMessage()}");
        }
        return null;
    }

    /**
     * Získej (nebo vytvoř) session pro zdroj.
     * Cache:
     *   - in-memory (per request)
     *   - DB (per zdroj, do login_session_until)
     */
    protected function getSession(Zdroj $zdroj): CookieJar
    {
        if (isset($this->sessions[$zdroj->id])) {
            return $this->sessions[$zdroj->id];
        }

        // 1. Zkus načíst z DB cache
        if ($zdroj->login_session && $zdroj->login_session_until && $zdroj->login_session_until > now()) {
            try {
                $cookies = json_decode(Crypt::decryptString($zdroj->login_session), true);
                if (is_array($cookies)) {
                    $jar = $this->jarFromArray($cookies, $zdroj->url);
                    return $this->sessions[$zdroj->id] = $jar;
                }
            } catch (\Exception) {
                // Cache poškozená — bude re-login níže
            }
        }

        // 2. Login
        $jar = $this->login($zdroj);

        // 3. Cache do DB (zašifrovaně)
        if ($jar) {
            $zdroj->update([
                'login_session' => Crypt::encryptString(json_encode($this->jarToArray($jar))),
                'login_session_until' => now()->addHours(self::SESSION_TTL_HOURS),
            ]);
        }

        return $this->sessions[$zdroj->id] = $jar ?: new CookieJar();
    }

    /** Provede login POST → vrátí cookie jar. */
    protected function login(Zdroj $zdroj): ?CookieJar
    {
        if (empty($zdroj->login_url) || empty($zdroj->login_credentials)) {
            return null;
        }

        try {
            $formData = json_decode(Crypt::decryptString($zdroj->login_credentials), true);
        } catch (\Exception $e) {
            Log::error("Cannot decrypt login_credentials for zdroj {$zdroj->id}: {$e->getMessage()}");
            return null;
        }

        if (!is_array($formData)) return null;

        $jar = new CookieJar();

        try {
            $client = new Client([
                'cookies' => $jar,
                'timeout' => 30,
                'http_errors' => false,
                'headers' => [
                    'User-Agent' => self::UA,
                    'Accept-Language' => 'cs,en;q=0.5',
                ],
                'allow_redirects' => true,
            ]);

            // GET login page (kvůli případnému CSRF/cookies)
            $client->get($zdroj->login_url);

            // POST credentials
            $response = $client->post($zdroj->login_url, [
                'form_params' => $formData,
            ]);

            if ($response->getStatusCode() >= 400) {
                Log::warning("Login failed for zdroj {$zdroj->id}: HTTP " . $response->getStatusCode());
                return null;
            }

            return $jar;
        } catch (\Exception $e) {
            Log::error("Login exception for zdroj {$zdroj->id}: {$e->getMessage()}");
            return null;
        }
    }

    /** Zruší cache session — vynutí nový login při příštím fetch. */
    public function vynutOdhlaseni(Zdroj $zdroj): void
    {
        unset($this->sessions[$zdroj->id]);
        $zdroj->update([
            'login_session' => null,
            'login_session_until' => null,
        ]);
    }

    /** Helper: zašifrovat credentials pro uložení do DB. */
    public static function zasifrujCredentials(array $formData): string
    {
        return Crypt::encryptString(json_encode($formData));
    }

    protected function jarToArray(CookieJar $jar): array
    {
        return $jar->toArray();
    }

    protected function jarFromArray(array $cookies, string $baseUrl): CookieJar
    {
        $host = parse_url($baseUrl, PHP_URL_HOST) ?: '';
        $jar = new CookieJar();
        foreach ($cookies as $c) {
            $jar->setCookie(new SetCookie([
                'Name' => $c['Name'] ?? '',
                'Value' => $c['Value'] ?? '',
                'Domain' => $c['Domain'] ?: $host,
                'Path' => $c['Path'] ?? '/',
                'Max-Age' => $c['Max-Age'] ?? null,
                'Expires' => $c['Expires'] ?? null,
                'Secure' => $c['Secure'] ?? false,
                'HttpOnly' => $c['HttpOnly'] ?? false,
            ]));
        }
        return $jar;
    }
}
