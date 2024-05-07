<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Macaw;

trait FriendListTrait
{
    /**
     * Adds the PlayFab user, based upon a match against a supplied unique identifier, to the friend list of the local user. At least one of FriendPlayFabId, FriendUsername, FriendEmail, or FriendTitleDisplayName should be initialized.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/friend-list-management/add-friend?view=playfab-rest
     *
     * @param string|null $email Email address of the user to attempt to add to the local user's friend list.
     * @param string|null $playFabId PlayFab identifier of the user to attempt to add to the local user's friend list.
     * @param string|null $titleDisplayName Title-specific display name of the user to attempt to add to the local user's friend list.
     * @param string|null $username PlayFab username of the user to attempt to add to the local user's friend list.
     * @return array
     */
    public function add_friend(string $email = null, string $playFabId = null, string $titleDisplayName = null,
                               string $username = null): array
    {
        $postFields = [];

        if ($email) $postFields["FriendEmail"] = $email;
        if ($playFabId) $postFields["FriendPlayFabId"] = $playFabId;
        if ($titleDisplayName) $postFields["FriendTitleDisplayName"] = $titleDisplayName;
        if ($username) $postFields["FriendUsername"] = $username;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/AddFriend",
            postFields: $postFields);
    }

    /**
     * Retrieves the current friend list for the local user, constrained to users who have PlayFab accounts. Friends from linked accounts (Facebook, Steam) are also included. You may optionally exclude some linked services' friends.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/friend-list-management/get-friends-list?view=playfab-rest
     *
     * @param array $customTags
     * @param array $externalPlatformFriends
     * @param array $profileConstraints
     * @param string $xboxToken
     * @return array
     */
    public function get_friends_list(array $customTags = [], array $externalPlatformFriends = [],
                                     array $profileConstraints = [], string $xboxToken = ""): array
    {
        $postFields = [];

        if ($customTags) $postFields["CustomTags"] = $customTags;
        if ($externalPlatformFriends) $postFields["ExternalPlatformFriends"] = $externalPlatformFriends;
        if ($profileConstraints) $postFields["ProfileConstraints"] = $profileConstraints;
        if ($xboxToken) $postFields["XboxToken"] = $xboxToken;

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetFriendsList",
            postFields: $postFields);
    }

    /**
     * Removes a specified user from the friend list of the local user
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/friend-list-management/remove-friend?view=playfab-rest
     *
     * @param string $playFabId
     * @return array
     */
    public function remove_friend(string $playFabId): array
    {
        $postFields = ["FriendPlayFabId" => $playFabId];

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/RemoveFriend",
            postFields: $postFields);
    }

    /**
     * Updates the tag list for a specified user in the friend list of the local user
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/friend-list-management/set-friend-tags?view=playfab-rest
     *
     * @param string $playFabId PlayFab identifier of the friend account to which the tag(s) should be applied.
     * @param array $tags Array of tags to set on the friend account.
     * @return array
     */
    public function set_friend_tags(string $playFabId, array $tags = []): array
    {
        $postFields = [
            "FriendPlayFabId" => $playFabId,
            "Tags" => $tags
        ];

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/SetFriendTags",
            postFields: $postFields);
    }
}