<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox\Macaw;

trait UserAuthenticationTrait
{
    /**
     * Re-authenticates a user session based on previous login credentials.
     *
     * @param string $loginMethod
     * @param array $loginOptions
     * @return array
     */
    public function relogin_user(string $loginMethod, array $loginOptions): array
    {
        if ("login_with_email_address" == $loginMethod) {
            $response = $this->login_with_email_address(
                email: $loginOptions["email"] ?? "",
                password: $loginOptions["password"] ?? "",
                customTags: $loginOptions["customTags"] ?? [],
                infoRequestParameters: $loginOptions["infoRequestParameters"] ?? []
            );
        }

        if ("login_with_google_account" == $loginMethod) {
            $response = $this->login_with_google_account(
                createAccount: $loginOptions["createAccount"] ?? false,
                customTags: $loginOptions["customTags"] ?? [],
                encryptedRequest: $loginOptions["encryptedRequest"] ?? "",
                infoRequestParameters: $loginOptions["infoRequestParameters"] ?? [],
                playerSecret: $loginOptions["playerSecret"] ?? "",
                serverAuthCode: $loginOptions["serverAuthCode"] ?? "",
                setEmail: $loginOptions["setEmail"] ?? true,
            );
        }

        return $response ?? [];
    }
    /**
     * Returns the title's base 64 encoded RSA CSP blob.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/authentication/get-title-public-key?view=playfab-rest
     *
     * @param string $titleId Unique identifier for the title, found in the Settings > Game Properties section of the PlayFab developer site when a title has been selected.
     * @param string $titleSharedSecret The shared secret key for this title
     * @return array
     */
    public function get_title_public_key(string $titleSharedSecret): array
    {
        $postFields = [
            "TitleId" => $this->titleId,
            "TitleSharedSecret" => $titleSharedSecret
        ];

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/GetTitlePublicKey",
            postFields: $postFields);
    }

    /**
     * Registers a new Playfab user account, returning a session identifier that can subsequently be used for API calls which require an authenticated user. You must supply a username and an email address.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/authentication/register-playfab-user?view=playfab-rest
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @return string response
     */
    public function register_playfab_user(string $username, string $email, string $password): array
    {
        $postFields = [
            "TitleId" => $this->titleId,
            "Username" => $username,
            "Email" => $email,
            "Password" => $password
        ];

        return $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/RegisterPlayFabUser",
            postFields: $postFields, useSessionTicket: false);
    }

    /**
     * Signs the user into the PlayFab account, returning a session identifier that can subsequently be used for API calls which require an authenticated user. Unlike most other login API calls, LoginWithEmailAddress does not permit the creation of new accounts via the CreateAccountFlag. Email addresses may be used to create accounts via RegisterPlayFabUser.
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/authentication/login-with-email-address?view=playfab-rest
     *
     * @param string $titleId Unique identifier for the title, found in the Settings > Game Properties section of the PlayFab developer site when a title has been selected.
     * @param string $email Email address for the account.
     * @param string $password Password for the PlayFab account (6-100 characters)
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @param array $infoRequestParameters Flags for which pieces of info to return for the user.
     * @return array
     */
    public function login_with_email_address(string $email, string $password, array $customTags = [],
                                             array $infoRequestParameters = []): array
    {
        $this->loginMethod = "login_with_email_address";
        $this->loginOptions = [
            "Email" => $email,
            "Password" => $password,
            "CustomTags" => $customTags,
            "InfoRequestParameters" => $infoRequestParameters,
        ];

        $postFields = [
            "Email" => $email,
            "Password" => $password,
            "TitleId" => $this->titleId
        ];
        if ($customTags) $postFields["CustomTags"] = $customTags;
        if ($infoRequestParameters) $postFields["InfoRequestParameters"] = $infoRequestParameters;

        $response = $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/LoginWithEmailAddress",
            postFields: $postFields, useSessionTicket: false);

        $_SESSION[$this->macawSessionKey]["data"] = $response["data"] ?? [];

        return $response;
    }

    /**
     * Signs the user in using their Google account credentials
     *
     * https://learn.microsoft.com/en-us/rest/api/playfab/client/authentication/login-with-google-account?view=playfab-rest
     *
     * @param string $titleId Unique identifier for the title, found in the Settings > Game Properties section of the PlayFab developer site when a title has been selected.
     * @param bool $createAccount Automatically create a PlayFab account if one is not currently linked to this ID.
     * @param array $customTags The optional custom tags associated with the request (e.g. build number, external trace identifiers, etc.).
     * @param string $encryptedRequest Base64 encoded body that is encrypted with the Title's public RSA key (Enterprise Only).
     * @param array $infoRequestParameters Flags for which pieces of info to return for the user.
     * @param string $playerSecret Player secret that is used to verify API request signatures (Enterprise Only).
     * @param string $serverAuthCode OAuth 2.0 server authentication code obtained on the client by calling the getServerAuthCode() Google client API.
     * @param bool $setEmail Optional boolean to opt out of setting the MPA email when creating a Google account, defaults to true.
     * @return array
     */
    public function login_with_google_account(bool $createAccount = false, array $customTags = [],
                                              string $encryptedRequest = "", array $infoRequestParameters = [],
                                              string $playerSecret = "", string $serverAuthCode = "",
                                              bool $setEmail = true): array
    {
        $this->loginMethod = "login_with_google_account";
        $this->loginOptions = [
            "createAccount" => $createAccount,
            "customTags" => $customTags,
            "encryptedRequest" => $encryptedRequest,
            "infoRequestParameters" => $infoRequestParameters,
            "playerSecret" => $playerSecret,
            "serverAuthCode" => $serverAuthCode,
            "setEmail" => $setEmail,
        ];

        $postFields = [
            "TitleId" => $this->titleId,
            "CreateAccount" => $createAccount,
            "CustomTags" => $customTags,
            "SetEmail" => $setEmail
        ];

        if ($encryptedRequest) $postFields["EncryptedRequest"] = $encryptedRequest;
        if ($infoRequestParameters) $postFields["InfoRequestParameters"] = $infoRequestParameters;
        if ($playerSecret) $postFields["PlayerSecret"] = $playerSecret;
        if ($serverAuthCode) $postFields["ServerAuthCode"] = $serverAuthCode;

        $response = $this->make_rest_call(endpoint: "https://titleId.playfabapi.com/Client/LoginWithGoogleAccount",
            postFields: $postFields, useSessionTicket: false);

        $_SESSION[$this->macawSessionKey]["data"] = $response["data"] ?? [];

        return $response;
    }
}