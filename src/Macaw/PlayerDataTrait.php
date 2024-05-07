<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Macaw;

trait PlayerDataTrait
{
    /**
     * Retrieves a list of ranked friends of the current player for the given statistic, starting from the indicated point in the leaderboard
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/player-data-management/get-friend-leaderboard?view=playfab-rest
     *
     * @param int $startPosition Position in the leaderboard to start this listing (defaults to the first entry).
     * @param string $statisticName Statistic used to rank friends for this leaderboard.
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @param array $externalPlatformFriends Indicates which other platforms' friends should be included in the response. In HTTP, it is represented as a comma-separated list of platforms.
     * @param int $maxResultsCount Maximum number of entries to retrieve. Default 10, maximum 100.
     * @param array $profileConstraints If non-null, this determines which properties of the resulting player profiles to return. For API calls from the client, only the allowed client profile properties for the title may be requested. These allowed properties are configured in the Game Manager "Client Profile Options" tab in the "Settings" section.
     * @param bool $useSpecificVersion If set to false, Version is considered null. If true, uses the specified Version
     * @param int|null $version The version of the leaderboard to get.
     * @param string $xboxToken Xbox token if Xbox friends should be included. Requires Xbox be configured on PlayFab.
     * @return array
     */
    public function get_friend_leaderboard(int   $startPosition, string $statisticName, array $customTags = [],
                                           array $externalPlatformFriends = [], int $maxResultsCount = 10,
                                           array $profileConstraints = [], bool $useSpecificVersion = false,
                                           int   $version = null, string $xboxToken = ""): array
    {
        $postFields = [
            "StartPosition" => $startPosition,
            "StatisticName" => $statisticName,
            "MaxResultsCount" => max(10, min($maxResultsCount, 100))
        ];
        if ($customTags) $postFields["CustomTags"] = $customTags;
        if ($externalPlatformFriends) $postFields["ExternalPlatformFriends"] = $externalPlatformFriends;
        if ($profileConstraints) $postFields["ProfileConstraints"] = $profileConstraints;
        if ($useSpecificVersion) $postFields["UseSpecificVersion"] = $useSpecificVersion;
        if ($version) $postFields["Version"] = $version;
        if ($xboxToken) $postFields["XboxToken"] = $xboxToken;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetFriendLeaderboard",
            postFields: $postFields);
    }

    /**
     * Retrieves a list of ranked friends of the current player for the given statistic, centered on the requested PlayFab user. If PlayFabId is empty or null will return currently logged in user.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/player-data-management/get-friend-leaderboard-around-player?view=playfab-rest
     *
     * @param string $statisticName Statistic used to rank players for this leaderboard.
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @param array $externalPlatformFriends Indicates which other platforms' friends should be included in the response. In HTTP, it is represented as a comma-separated list of platforms.
     * @param int $maxResultsCount Maximum number of entries to retrieve. Default 10, maximum 100.
     * @param string|null $playFabId PlayFab unique identifier of the user to center the leaderboard around. If null will center on the logged in user.
     * @param array $profileConstraints If non-null, this determines which properties of the resulting player profiles to return. For API calls from the client, only the allowed client profile properties for the title may be requested. These allowed properties are configured in the Game Manager "Client Profile Options" tab in the "Settings" section.
     * @param bool $useSpecificVersion If set to false, Version is considered null. If true, uses the specified Version
     * @param int|null $version The version of the leaderboard to get.
     * @param string $xboxToken Xbox token if Xbox friends should be included. Requires Xbox be configured on PlayFab.
     * @return array
     */
    public function get_friend_leaderboard_around_player(string $statisticName, array $customTags = [],
                                                         array  $externalPlatformFriends = [], int $maxResultsCount = 10,
                                                         string $playFabId = null, array $profileConstraints = [],
                                                         bool   $useSpecificVersion = false, int $version = null,
                                                         string $xboxToken = ""): array
    {
        $postFields = [
            "StatisticName" => $statisticName,
            "MaxResultsCount" => max(10, min($maxResultsCount, 100))
        ];
        if ($customTags) $postFields["CustomTags"] = $customTags;
        if ($externalPlatformFriends) $postFields["ExternalPlatformFriends"] = $externalPlatformFriends;
        if ($playFabId) $postFields["PlayFabId"] = $playFabId;
        if ($profileConstraints) $postFields["ProfileConstraints"] = $profileConstraints;
        if ($useSpecificVersion) $postFields["UseSpecificVersion"] = $useSpecificVersion;
        if ($version) $postFields["Version"] = $version;
        if ($xboxToken) $postFields["XboxToken"] = $xboxToken;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetFriendLeaderboardAroundPlayer",
            postFields: $postFields);
    }

    /**
     * Retrieves a list of ranked users for the given statistic, starting from the indicated point in the leaderboard
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/player-data-management/get-leaderboard?view=playfab-rest
     *
     * @param int $startPosition Position in the leaderboard to start this listing (defaults to the first entry).
     * @param string $statisticName Statistic used to rank players for this leaderboard.
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @param int $maxResultsCount Maximum number of entries to retrieve. Default 10, maximum 100.
     * @param array $profileConstraints If non-null, this determines which properties of the resulting player profiles to return. For API calls from the client, only the allowed client profile properties for the title may be requested. These allowed properties are configured in the Game Manager "Client Profile Options" tab in the "Settings" section.
     * @param bool $useSpecificVersion If set to false, Version is considered null. If true, uses the specified Version
     * @param int $version The version of the leaderboard to get.
     * @return array
     */
    public function get_leaderboard(int  $startPosition, string $statisticName, array $customTags = [],
                                    int  $maxResultsCount = 10, array $profileConstraints = [],
                                    bool $useSpecificVersion = false, int $version = 0): array
    {
        $postFields = [
            "StartPosition" => max(0, $startPosition),
            "StatisticName" => $statisticName,
            "MaxResultsCount" => max(10, min($maxResultsCount, 100))
        ];
        if ($customTags) $postFields["IustomTags"] = $customTags;
        if ($profileConstraints) $postFields["ProfileConstraints"] = $profileConstraints;
        if ($useSpecificVersion) $postFields["UseSpecificVersion"] = $useSpecificVersion;
        if ($version) $postFields["Version"] = $version;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetLeaderboard",
            postFields: $postFields);
    }

    /**
     * Retrieves a list of ranked users for the given statistic, centered on the requested player. If PlayFabId is empty or null will return currently logged in user.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/player-data-management/get-leaderboard-around-player?view=playfab-rest
     *
     * @param string $statisticName Statistic used to rank players for this leaderboard.
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @param int $maxResutlsCount Maximum number of entries to retrieve. Default 10, maximum 100.
     * @param string $playFabId PlayFab unique identifier of the user to center the leaderboard around. If null will center on the logged in user.
     * @param array $profileConstraints If non-null, this determines which properties of the resulting player profiles to return. For API calls from the client, only the allowed client profile properties for the title may be requested. These allowed properties are configured in the Game Manager "Client Profile Options" tab in the "Settings" section.
     * @param bool $useSpecificVersion If set to false, Version is considered null. If true, uses the specified Version
     * @param int|null $version The version of the leaderboard to get.
     * @return array
     */
    public function get_leaderboard_around_player(string $statisticName, array $customTags = [],
                                                  int    $maxResutlsCount = 10, string $playFabId = "",
                                                  array  $profileConstraints = [], bool $useSpecificVersion = false,
                                                  int    $version = null): array
    {
        $postFields = [
            "StatisticName" => $statisticName,
            "MaxResultsCount" => max(10, min($maxResutlsCount, 100))
        ];
        if ($customTags) $postFields["CustomTags"] = $customTags;
        if ($playFabId) $postFields["PlayFabId"] = $playFabId;
        if ($profileConstraints) $postFields["ProfileConstraints"] = $profileConstraints;
        if ($useSpecificVersion) $postFields["UseSpecificVersion"] = $useSpecificVersion;
        if ($version) $postFields["Version"] = $version;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetLeaderboardAroundPlayer",
            postFields: $postFields);
    }

    /**
     * Retrieves the information on the available versions of the specified statistic.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/player-data-management/get-player-statistic-versions?view=playfab-rest
     *
     * @param string $statisticName unique name of the statistic
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @return array
     */
    public function get_player_statistic_versions(string $statisticName, array $customTags = []): array
    {
        $postFields = ["StatisticName" => $statisticName];
        if ($customTags) $postFields["CustomTags"] = $customTags;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetPlayerStatisticVersions",
            postFields: $postFields);
    }

    /**
     * Retrieves the indicated statistics (current version and values for all statistics, if none are specified), for the local player.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/player-data-management/get-player-statistics?view=playfab-rest
     *
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @param array $statisticNameVersions statistics to return, if StatisticNames is not set (only statistics which have a version matching that provided will be returned)
     * @param array $statisticNames statistics to return (current version will be returned for each)
     * @return array
     */
    public function get_player_statistics(array $customTags = [], array $statisticNameVersions = [], array $statisticNames = []): array
    {
        $postFields = [];
        if ($customTags) $postFields["CustomTags"] = $customTags;
        if ($statisticNameVersions) $postFields["StatisticNameVersions"] = $statisticNameVersions;
        if ($statisticNames) $postFields["StatisticNames"] = $statisticNames;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetPlayerStatistics",
            postFields: $postFields);
    }

    /**
     * Retrieves the indicated statistics (current version and values for all statistics, if none are specified), for the local player.
     *
     * https://titleId.playfabapi.com/Client/GetPlayerStatistics
     *
     * @param int $ifChangedFromDataVersion The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @param array $keys statistics to return, if StatisticNames is not set (only statistics which have a version matching that provided will be returned)
     * @param string|null $playFabId statistics to return (current version will be returned for each)
     * @return array
     */
    public function get_user_data(int $ifChangedFromDataVersion = 0, array $keys = [], string $playFabId = null): array
    {
        $postFields = [];
        if ($ifChangedFromDataVersion) $postFields["IfChangedFromDataVersion"] = $ifChangedFromDataVersion;
        if ($keys) $postFields["Keys"] = $keys;
        if ($playFabId) $postFields["PlayFabId"] = $playFabId;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetUserData",
            postFields: $postFields);
    }

    /**
     * Retrieves the publisher-specific custom data for the user which is readable and writable by the client
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/player-data-management/get-user-publisher-data?view=playfab-rest
     *
     * @param int $ifChangedFromDataVersion
     * @param array $keys
     * @param string|null $playFabId
     * @return array
     */
    public function get_user_publisher_data(int $ifChangedFromDataVersion = 0, array $keys = [], string $playFabId = null): array
    {
        $postFields = [];
        if ($ifChangedFromDataVersion) $postFields["IfChangedFromDataVersion"] = $ifChangedFromDataVersion;
        if ($keys) $postFields["Keys"] = $keys;
        if ($playFabId) $postFields["PlayFabId"] = $playFabId;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetUserPublisherData",
            postFields: $postFields);
    }

    /**
     * Retrieves the publisher-specific custom data for the user which can only be read by the client
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/player-data-management/get-user-publisher-read-only-data?view=playfab-rest
     *
     * @param int $ifChangedFromDataVersion The version that currently exists according to the caller. The call will return the data for all of the keys if the version in the system is greater than this.
     * @param array $keys List of unique keys to load from.
     * @param string|null $playFabId Unique PlayFab identifier of the user to load data for. Optional, defaults to yourself if not set. When specified to a PlayFab id of another player, then this will only return public keys for that account.
     * @return array
     */
    public function get_user_publisher_read_only_data(int $ifChangedFromDataVersion = 0, array $keys = [],
                                                      string $playFabId = null): array
    {
        $postFields = [];
        if ($ifChangedFromDataVersion) $postFields["IfChangedFromDataVersion"] = $ifChangedFromDataVersion;
        if ($keys) $postFields["Keys"] = $keys;
        if ($playFabId) $postFields["PlayFabId"] = $playFabId;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetUserPublisherReadOnlyData",
            postFields: $postFields);
    }

    /**
     * Retrieves the title-specific custom data for the user which can only be read by the client
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/player-data-management/get-user-read-only-data?view=playfab-rest
     *
     * @param int $ifChangedFromDataVersion The version that currently exists according to the caller. The call will return the data for all of the keys if the version in the system is greater than this.
     * @param array $keys List of unique keys to load from.
     * @param string|null $playFabId Unique PlayFab identifier of the user to load data for. Optional, defaults to yourself if not set. When specified to a PlayFab id of another player, then this will only return public keys for that account.
     * @return array
     */
    public function get_user_read_only_data(int $ifChangedFromDataVersion = 0, array $keys = [],
                                            string $playFabId = null): array
    {
        $postFields = [];
        if ($ifChangedFromDataVersion) $postFields["IfChangedFromDataVersion"] = $ifChangedFromDataVersion;
        if ($keys) $postFields["Keys"] = $keys;
        if ($playFabId) $postFields["PlayFabId"] = $playFabId;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetUserReadOnlyData",
            postFields: $postFields);
    }

    /**
     * Updates the values of the specified title-specific statistics for the user. By default, clients are not permitted to update statistics. Developers may override this setting in the Game Manager > Settings > API Features.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/player-data-management/update-player-statistics?view=playfab-rest#statisticupdate
     *
     * @param array $statistics Statistics to be updated with the provided values
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @return array
     */
    public function update_player_statistics(array $statistics, array $customTags = []): array
    {
        $postFields = ["Statistics" => $statistics];
        if ($customTags) $postFields["CustomTags"] = $customTags;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/UpdatePlayerStatistics",
            postFields: $postFields);
    }

    /**
     * Creates and updates the title-specific custom data for the user which is readable and writable by the client
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/player-data-management/update-user-data?view=playfab-rest
     *
     * @param array $data Key-value pairs to be written to the custom data. Note that keys are trimmed of whitespace, are limited in size, and may not begin with a '!' character or be null.
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @param array $keysToRemove Optional list of Data-keys to remove from UserData. Some SDKs cannot insert null-values into Data due to language constraints. Use this to delete the keys directly.
     * @param bool $isPublic Permission to be applied to all user data keys written in this request. Defaults to "private" if not set. This is used for requests by one player for information about another player; those requests will only return Public keys.
     * @return array
     */
    public function update_user_data(array $data, array $customTags = [], array $keysToRemove = [],
                                     bool $isPublic = false): array
    {
        $postFields = ["Data" => $data];
        if ($customTags) $postFields["CustomTags"] = $customTags;
        if ($keysToRemove) $postFields["KeysToRemove"] = $keysToRemove;
        if ($isPublic) $postFields["Permission"] = "public";

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/UpdateUserData",
            postFields: $postFields);
    }

    /**
     * Creates and updates the publisher-specific custom data for the user which is readable and writable by the client
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/player-data-management/update-user-publisher-data?view=playfab-rest
     *
     * @param array $data Key-value pairs to be written to the custom data. Note that keys are trimmed of whitespace, are limited in size, and may not begin with a '!' character or be null.
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @param array $keysToRemove Optional list of Data-keys to remove from UserData. Some SDKs cannot insert null-values into Data due to language constraints. Use this to delete the keys directly.
     * @param bool $isPublic Permission to be applied to all user data keys written in this request. Defaults to "private" if not set. This is used for requests by one player for information about another player; those requests will only return Public keys.
     * @return array
     */
    public function update_user_publisher_data(array $data, array $customTags = [], array $keysToRemove = [],
                                               bool $isPublic = false): array
    {
        $postFields = ["Data" => $data];
        if ($customTags) $postFields["CustomTags"] = $customTags;
        if ($keysToRemove) $postFields["KeysToRemove"] = $keysToRemove;
        if ($isPublic) $postFields["Permission"] = "public";

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/UpdateUserPublisherData",
            postFields: $postFields);
    }
}