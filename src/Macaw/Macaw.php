<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Macaw;

use Supergnaw\Nestbox\Nestbox;

class Macaw extends Nestbox
{
    final protected const string PACKAGE_NAME = 'macaw';

    public int $macawStaleHoursNews = 1;
    public int $macawStaleHoursTitleData = 1;
    public int $macawStaleHoursCatalog = 168;
    public int $macawStaleHoursLeaderboard = 24;
    public int $macawClient2MinLimit = 1000;
    public int $macawServer2MinLimit = 12000;
    public string $macawSessionKey = 'playfab';

    use ClassTablesTrait;

    use CallHandlerTrait;

    use SessionTokenTrait;

    use UserAuthenticationTrait;

    use CharacterTrait;

    use ContentTrait;

    use FriendListTrait;

    use PlayerDataTrait;

    use PlayerItemTrait;

    use SharedGroupDataTrait;

    use TitleWideDataTrait;

    use TradingTrait;

    protected string $loginMethod = "login_with_email_address";
    protected array $loginOptions = [];
    protected string $titleId = "";

    public function __construct(string $titleId = null, string $host = null, string $user = null, string $pass = null,
                                string $name = null)
    {
        parent::__construct($host, $user, $pass, $name);

        $this->titleId = trim(string: strval(value: $titleId));

        if (!$this->titleId && defined(constant_name: "MACAW_TITLE_ID")) $this->titleId = constant(name: "MACAW_TITLE_ID");

        if (empty($this->titleId)) throw new MacawMisingTitleIdException('Missing \$titleId or `MACAW_TITLE_ID` constant.');
    }

    public function __invoke(string $titleId = null, string $host = null, string $user = null, string $pass = null,
                             string $name = null): void
    {
        $this->__construct($titleId, $host, $user, $pass, $name);
    }

    protected function make_rest_call(string $endpoint, array $headers = [], array $params = [], array $postFields = [],
                                      string $method = "POST", bool $useSessionTicket = true): array
    {
        // API call limiting
        $this->api_call_limiter();

        // input validation
        if (!in_array(needle: "Content-Type: application/json", haystack: $headers)) {
            $headers[] = "Content-Type: application/json";
        }
        if ($useSessionTicket) {
            $headers[] = "X-Authorization: " . $_SESSION[$this->macawSessionKey]["data"]["SessionTicket"] ?? "";
        }

        $params = $this->compile_url_params($params);
        $method = (in_array(strtoupper($method), ["POST", "GET", "PUT", "PATCH", "DELETE"]))
            ? strtoupper($method) : "POST";

        // curl options
        $options = [
            CURLOPT_URL => ("GET" == $method) ? $endpoint . $params : $endpoint,
            CURLOPT_POSTFIELDS => ("POST" == $method) ? json_encode($postFields) : null,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_VERBOSE => true,
            CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)",
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];

        // make the call *epic music starts*
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);

        // log it
        $this->log_api_call(endpoint: $endpoint, statusCode: json_decode(json: $response ?: [])->code ?? 0);

        return json_decode($response, associative: true);

    }

    protected function api_call_limiter(bool $isServer = false): void
    {
        // get total calls per second
        $limitCPS = ($isServer) ? $this->macawServer2MinLimit : $this->macawClient2MinLimit;
        $currentCPS = $this->last_x_minute_calls_per_second(minutes: 2);

        // calculate microsecond values for calls per second
        $limitMicroseconds = 1 / $limitCPS * 1000000;
        $currentMicroseconds = 1 / max($currentCPS, $limitCPS) * 1000000;

        // calculate call to limit ratio and adjust sleep time accordingly
        $callRatio = 1 / ($currentMicroseconds / $limitMicroseconds);
        $sleepTime = intval($limitMicroseconds * $callRatio);

        // developers hate this one weird trick! https://www.php.net/manual/en/function.usleep.php
        usleep(microseconds: $sleepTime);
    }

    protected function log_api_call(string $endpoint, int $statusCode, bool $isServer = false): void
    {
        $sql = "INSERT INTO `macaw_api_calls` (`call_endpoint`, `call_client`, `status_code`)
                VALUES (:endpoint, :client, :status_code);";

        $params = [
            "endpoint" => preg_replace(pattern: '/^(.*?(?=com))com/', replacement: "", subject: $endpoint),
            "client" => ($isServer) ? $_SERVER['SERVER_ADDR'] : $_SERVER['REMOTE_ADDR'],
            "status_code" => $statusCode
        ];

        $this->query_execute($sql, $params);
    }

    protected function compile_url_params(array $params = []): string
    {
        $p = [];
        foreach ($params as $key => $value) {
            $p[] = (is_string($value)) ? "$key=$value" : "$key=" . json_encode($value);
        }
        return (!$p) ? "" : "?" . implode("&", $p);
    }

    public function last_x_minute_calls_per_second(int $minutes): int
    {
        $sql = "SELECT
                    IFNULL(
                        COUNT(*)
                        DIV TIMESTAMPDIFF(
                            SECOND
                            , CURRENT_TIMESTAMP - INTERVAL {$minutes} MINUTE
                            , CURRENT_TIMESTAMP
                        )
                        , 0
                    ) AS `calls_per_second`
                FROM `macaw_api_calls`
                WHERE `call_time` > (CURRENT_TIMESTAMP - INTERVAL {$minutes} MINUTE);";

        if (!$this->query_execute()) {
            return 0;
        }

        return intval($this->results(firstResultOnly: true)["calls_per_second"]);
    }
}
