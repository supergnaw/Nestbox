<?php

declare(strict_types=1);

namespace NestboxPHP\Nestbox\Cuckoo;

use NestboxPHP\Nestbox\Nestbox;

// https://medium.com/@london.lingo.01/unlocking-the-power-of-php-encryption-secure-data-transmission-and-encryption-algorithms-c5ed7a2cb481
// https://stackoverflow.com/questions/18616573/how-to-check-fips-140-2-support-in-openssl
// https://paragonie.com/blog/2022/06/recap-our-contributions-more-secure-internet
// https://github.com/supergnaw/cyphper/blob/main/cyphper_static.php
// https://stackoverflow.com/questions/19031540/does-php-use-a-fips-140-compliant-rng-to-generate-session-ids
// https://wiki.openssl.org/index.php/FIPS_mode()
// https://crypto.stackexchange.com/questions/105840/ecdh-security-vs-type-of-elliptic-curve
//  - X25519 and P256: about 128-bit, P384: 192-bit, X448: 224-bit, P521: approximately 256-bit

/*
 * FIPS Compliant Algorithms:
 *
 *  AES – 128-bit or higher
 *  RSA – 2048 bits or higher
 *  TDES/TDEA – triple-length keys
 *  DSA/D-H – 2048/224 bits or higher
 *  ECC – 224 bit or higher
 */

/*
 * HMAC is obslute, use RSASSA or ECDSA; possible alternatives:
 *  https://www.php.net/manual/en/function.openssl-sign.php
 *  https://www.php.net/manual/en/function.openssl-verify.php
 */

class Cuckoo extends Nestbox
{
    final protected const string PACKAGE_NAME = 'cuckoo';
    public bool $cuckooFipsCapable = false;
    public bool $cuckooSitnagureAthentication = true;
    public string $cuckooSignatureAlgorithm = ""; // https://www.php.net/manual/en/openssl.signature-algos.php
    public string $symmetricKeyLocation = "";
    public string $asymmetricKeyLocation = "";
    private array $encryptionAlgorithms = [];

    public function __construct(string $host = null, string $user = null, string $pass = null, string $name = null)
    {
        parent::__construct($host, $user, $pass, $name); // TODO: Change the autogenerated stub


    }

    public function __invoke(string $host = null, string $user = null, string $pass = null, string $name = null): void
    {
        parent::__invoke($host, $user, $pass, $name); // TODO: Change the autogenerated stub


    }

    private function create_class_table_encryption_schema(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `cuckoo_encryption_schema` (
                    `schema_id` INT NOT NULL AUTO_INCREMENT ,
                    `table_name` VARCHAR( 128 ) NOT NULL ,
                    `column_name` VARCHAR( 128 ) NOT NULL ,
                    `encryption` VARCHAR( 16 ) NOT NULL ,
                    PRIMARY KEY ( `schema_id` ) ,
                    UNIQUE KEY `schema_key` ( `table_name`, `column_name` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";
        return $this->query_execute($sql);
    }

    private function check_fips_mode(): bool
    {
        return $this->cuckooFipsMode = false;
    }

    private function valid_algorithm(string $algorithm): bool
    {
        return array_key_exists($algorithm, $this->encryptionAlgorithms);
    }

    public function generate_bytes(int $length, int $maxRetries = 5): string|bool
    {
        $bytes = openssl_random_pseudo_bytes(length: $length, strong_result: $strongResult);
        if (!$strongResult) {
            if (0 < $maxRetries) {
                return $this->generate_bytes($length, $maxRetries - 1);
            }
            return false;
        }
        return $bytes;
    }

    public function generate_hexadecimal(int $chars): string
    {
        return bin2hex($this->generate_bytes(length: $chars / 2));
    }

    public function generate_iv(string $input): string
    {
        return "";
    }

    public function generate_key(string $input): string
    {
        return "";
    }

    public static function hmac_sign($cipherText, $key): string
    {
        return hash_hmac('sha256', $cipherText, $key) . $cipherText;
    }

    public static function hmac_authenticate($signedCipherText, $key): bool
    {
        $hmac = substr($signedCipherText, 0, 64);
        $cipherText = substr($signedCipherText, 64);
        return hash_equals(hash_hmac('sha256', $cipherText, $key), $hmac);
    }

    public function encrypt(string $input, string $algorithm, string $passphrase, string $iv): string|bool
    {
        // verify encryption algorithm
        if (!$this->valid_algorithm($algorithm)) {
            return false;
        }

        // require an ecryption password
        if (empty(trim($passphrase))) {
            return false;
        }

        // trim the initialzation vector
        $iv = substr(hash(algo: 'sha512', data: $iv), offset: 0, length: $this->encryptionAlgorithms[$algorithm]);

        // encrypt the data
        return openssl_encrypt(data: $input, cipher_algo: $algorithm, passphrase: $passphrase, iv: $iv);
    }

    public function decrypt(string $input, string $algorithm, string $key, string $iv): string|bool
    {
        // verify dencryption algorithm
        if (!$this->valid_algorithm($algorithm)) {
            return false;
        }

        // require a decryption password
        if (empty(trim($key))) {
            return false;
        }

        // trim the initialzation vector
        $iv = substr(hash(algo: 'sha512', data: $iv), offset: 0, length: $this->encryptionAlgorithms[$algorithm]);

        // decrypt the data
        return openssl_decrypt(data: $input, cipher_algo: $algorithm, passphrase: $key, options: 0, iv: $iv);
    }

    public function roll_encryption_key(string $oldKey, string $newKey): bool
    {
        return true;
    }

    public function change_signature_algorithm(string $algorithm): bool
    {
        return true;
    }

    public function query_execute(string $query, array $params = [], bool $close = false, bool $retry = true): bool
    {
        return parent::query_execute($query, $params, $close, $retry); // TODO: Change the autogenerated stub
    }

    public function insert(string $table, array $params, bool $updateOnDuplicate = true): int
    {
        return parent::insert($table, $params, $updateOnDuplicate); // TODO: Change the autogenerated stub
    }

    public function update(string $table, array $params, array $where, string $conjunction = "AND"): int
    {
        return parent::update($table, $params, $where, $conjunction); // TODO: Change the autogenerated stub
    }

    public function delete(string $table, array $where, $conjunction = "AND"): int
    {
        return parent::delete($table, $where, $conjunction); // TODO: Change the autogenerated stub
    }

    public function select(string $table, array $where = [], string $conjunction = "AND"): array
    {
        return parent::select($table, $where, $conjunction); // TODO: Change the autogenerated stub
    }
//    public function query_execute(string $query, array $params = [], bool $close = false): bool
//    {
//        try {
//            return parent::query_execute($query, $params, $close);
//        } catch (InvalidTableException) {
//            $this->create_tables();
//            return parent::query_execute($query, $params, $close);
//        }
//    }

//    public function create_tables(): void
//    {
//        $this->create_permissions_table();
//        $this->create_permission_assignments_table();
//    }

//    public function create_permissions_table($permissionsTable): bool
//    {
//        $sql = "CREATE TABLE IF NOT EXISTS `{$permissionsTable}` (
//                    `permission_id` INT NOT NULL AUTO_INCREMENT ,
//                    `permission_name` VARCHAR(63) NOT NULL ,
//                    `permission_description` VARCHAR(255) NOT NULL ,
//                    `permission_group` VARCHAR(31) NOT NULL ,
//                    PRIMARY KEY (`permission_id`)) ENGINE = InnoDB;
//                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";
//
//        return $this->query_execute(query: $sql);
//    }

//    public function create_permission_assignments_table(): bool
//    {
//        $sql = "CREATE TABLE IF NOT EXISTS `{$this->permissionAssignmentsTable}` (
//                    `assignment_id` INT NOT NULL AUTO_INCREMENT ,
//                    `permission_id` INT NOT NULL ,
//                    `user_id` VARCHAR( 125 ) NOT NULL ,
//                    PRIMARY KEY ( `assignment_id` )
//                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";
//
//        return $this->query_execute(query: $sql);
//    }
}