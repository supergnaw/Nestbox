<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Macaw;

trait ContentTrait
{
    /**
     * This API retrieves a pre-signed URL for accessing a content file for the title. A subsequent HTTP GET to the returned URL will attempt to download the content. A HEAD query to the returned URL will attempt to retrieve the metadata of the content. Note that a successful result does not guarantee the existence of this content - if it has not been uploaded, the query to retrieve the data will fail.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/content/get-content-download-url?view=playfab-rest
     *
     * @param string $key Key of the content item to fetch, usually formatted as a path, e.g. images/a.png
     * @param string $httpMethod HTTP method to fetch item - GET or HEAD. Use HEAD when only fetching metadata. Default is GET.
     * @param bool $thruCDN True to download through CDN. CDN provides higher download bandwidth and lower latency. However, if you want the latest, non-cached version of the content during development, set this to false. Default is true.
     * @return array
     */
    public function get_content_download_url(string $key, string $httpMethod = "GET", bool $thruCDN = true): array
    {
        $postFields = [
            "Key" => $key,
            "HttpMethod" => (in_array(needle: strtoupper($httpMethod), haystack: ["GET", "HEAD"]))
                ? strtoupper($httpMethod) : "GET",
            "ThruCDN" => $thruCDN
        ];

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetContentDownloadUrl",
            postFields: $postFields);
    }
}