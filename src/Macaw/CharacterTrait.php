<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Macaw;

trait CharacterTrait
{
    /**
     * Retrieves the title-specific custom data for the character which is readable and writable by the client
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/character-data/get-character-data?view=playfab-rest
     *
     * @param string $characterId Unique PlayFab assigned ID for a specific character owned by a user
     * @param int $ifChangedFromDataVersion The version that currently exists according to the caller. The call will return the data for all of the keys if the version in the system is greater than this.
     * @param array $keys Specific keys to search for in the custom user data.
     * @param string|null $playFabId Unique PlayFab identifier of the user to load data for. Optional, defaults to yourself if not set.
     * @return string API response
     */
    public function get_character_data(string $characterId, int $ifChangedFromDataVersion = 0, array $keys = [],
                                       string $playFabId = null): array
    {
        $postFields = ["CharacterId" => $characterId];
        if (0 < $ifChangedFromDataVersion) $postFields["ifChangedFromDataVersion"] = $ifChangedFromDataVersion;
        if ($keys) $postFields["Keys"] = $keys;
        if ($playFabId) $postFields["PlayFabId"] = $playFabId;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetCharacterData",
            postFields: $postFields);
    }

    /**
     * Retrieves the title-specific custom data for the character which can only be read by the client
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/character-data/get-character-read-only-data?view=playfab-rest
     *
     * @param string $characterId Unique PlayFab assigned ID for a specific character owned by a user
     * @param int $ifChangedFromDataVersion The version that currently exists according to the caller. The call will return the data for all of the keys if the version in the system is greater than this.
     * @param array $keys Specific keys to search for in the custom user data.
     * @param string|null $playFabId Unique PlayFab identifier of the user to load data for. Optional, defaults to yourself if not set.
     * @return string API response
     */
    public function get_character_read_only_data(string $characterId, int $ifChangedFromDataVersion = 0,
                                                 array $keys = [], string $playFabId = null): array
    {
        $postFields = ["CharacterId" => $characterId];
        if (0 < $ifChangedFromDataVersion) $postFields["ifChangedFromDataVersion"] = $ifChangedFromDataVersion;
        if ($keys) $postFields["Keys"] = $keys;
        if ($playFabId) $postFields["PlayFabId"] = $playFabId;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetCharacterReadOnlyData",
            postFields: $postFields);
    }

    /**
     * Creates and updates the title-specific custom data for the user's character which is readable and writable by the client
     *
     * @param string $characterId Unique PlayFab assigned ID for a specific character owned by a user
     * @param array $data Key-value pairs to be written to the custom data. Note that keys are trimmed of whitespace, are limited in size, and may not begin with a '!' character or be null.
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @param array $keysToRemove Optional list of Data-keys to remove from UserData. Some SDKs cannot insert null-values into Data due to language constraints. Use this to delete the keys directly.
     * @param bool $isPublic Permission to be applied to all user data keys written in this request. Defaults to "private" if not set.
     * @return array
     */
    public function update_character_data(string $characterId, array $data, array $customTags = [],
                                          array $keysToRemove = [], bool $isPublic = False): array
    {
        $postFields = [
            "CharacterId" => $characterId,
            "Data" => $data,
            "Permission" => ($isPublic) ? "Public" : "Private"
        ];
        if ($customTags) $postFields["CustomTags"] = $customTags;
        if ($keysToRemove) $postFields["KeysToRemove"] = $keysToRemove;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/UpdateCharacterData",
            postFields: $postFields);
    }

    /**
     * Lists all of the characters that belong to a specific user. CharacterIds are not globally unique; characterId must be evaluated with the parent PlayFabId to guarantee uniqueness.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/characters/get-all-users-characters?view=playfab-rest
     *
     * @param string $playFabId
     * @return array Unique PlayFab assigned ID of the user on whom the operation will be performed.
     */
    public function get_all_users_characters(string $playFabId): array
    {
        $postFields = ["PlayFabId" => $playFabId];
        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetAllUsersCharacters",
            postFields: $postFields);
    }

    /**
     * Retrieves a list of ranked characters for the given statistic, starting from the indicated point in the leaderboard
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/characters/get-character-leaderboard?view=playfab-rest
     *
     * @param int $startPosition First entry in the leaderboard to be retrieved.
     * @param string $statisticName Unique identifier for the title-specific statistic for the leaderboard.
     * @param int $maxResultsCount Maximum number of entries to retrieve. Default 10, maximum 100.
     * @return array
     */
    public function get_character_leaderboard(int $startPosition, string $statisticName,
                                              int $maxResultsCount = 10): array
    {
        $postFields = [
            "StartPosition" => $startPosition,
            "StatisticName" => $statisticName,
            "MaxResultsCount" => max(10, min($maxResultsCount, 100))
        ];

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetCharacterLeaderboard",
            postFields: $postFields);
    }

    /**
     * Retrieves the details of all title-specific statistics for the user
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/characters/get-character-statistics?view=playfab-rest
     *
     * @param string $characterId Unique PlayFab assigned ID for a specific character owned by a user
     * @return array
     */
    public function get_character_statistics(string $characterId): array
    {
        $postFields = ["CharacterId" => $characterId];
        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetCharacterStatistics",
            postFields: $postFields);
    }

    /**
     * Retrieves a list of ranked characters for the given statistic, centered on the requested Character ID
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/characters/get-leaderboard-around-character?view=playfab-rest
     *
     * @param string $characterId Unique PlayFab assigned ID for a specific character on which to center the leaderboard.
     * @param string $statisticName Unique PlayFab assigned ID for a specific character on which to center the leaderboard.
     * @param int $maxResultsCount Maximum number of entries to retrieve. Default 10, maximum 100.
     * @return array
     */
    public function get_leaderboard_around_character(string $characterId, string $statisticName,
                                                     int $maxResultsCount = 10): array
    {
        $postFields = [
            "CharacterId" => $characterId,
            "StatisticName" => $statisticName,
            "MaxResultsCount" => max(10, min($maxResultsCount, 100))
        ];
        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetLeaderboardAroundCharacter",
            postFields: $postFields);
    }

    /**
     * Retrieves a list of all of the user's characters for the given statistic.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/characters/get-leaderboard-for-user-characters?view=playfab-rest
     *
     * @param string $statisticName Unique identifier for the title-specific statistic for the leaderboard.
     * @return array
     */
    public function get_leaderboard_for_user_characters(string $statisticName): array
    {
        $postFields = ["StatisticName" => $statisticName];
        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetLeaderboardForUserCharacters",
            postFields: $postFields);
    }

    /**
     * Grants the specified character type to the user. CharacterIds are not globally unique; characterId must be evaluated with the parent PlayFabId to guarantee uniqueness.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/characters/grant-character-to-user?view=playfab-rest
     *
     * @param string $characterName Non-unique display name of the character being granted (1-40 characters in length).
     * @param string $itemId Catalog item identifier of the item in the user's inventory that corresponds to the character in the catalog to be created.
     * @param string $catalogVersion Catalog version from which items are to be granted.
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @return array
     */
    public function grant_character_to_user(string $characterName, string $itemId, string $catalogVersion = "",
                                            array $customTags = []): array
    {
        $postFields = [
            "CharacterName" => $characterName,
            "ItemId" => $itemId
        ];
        if ($catalogVersion) $postFields["CatalogVersion"] = $catalogVersion;
        if ($customTags) $postFields["CustomTags"] = $customTags;
        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GrantCharacterToUser",
            postFields: $postFields);
    }

    /**
     * Updates the values of the specified title-specific statistics for the specific character. By default, clients are not permitted to update statistics. Developers may override this setting in the Game Manager > Settings > API Features.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/characters/update-character-statistics?view=playfab-rest
     *
     * @param string $characterId Unique PlayFab assigned ID for a specific character owned by a user
     * @param array $characterStatistics Statistics to be updated with the provided values, in the Key(string), Value(int) pattern.
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @return array
     */
    public function update_character_statistics(string $characterId, array $characterStatistics = [],
                                                array $customTags = []): array
    {
        $postFields = ["CharacterId"];
        if ($characterStatistics) $postFields["CharacterStatistics"] = $characterStatistics;
        if ($customTags) $postFields["CustomTags"] = $customTags;
        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/UpdateCharacterStatistics",
            postFields: $postFields);
    }
}