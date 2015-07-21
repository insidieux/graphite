<?php
namespace Graphite\Helper;

class Hash
{
    /**
     * @param $string
     *
     * @return string
     */
    public static function hash($string)
    {
        // from php.net

        /* To generate the salt, first generate enough random bytes. Because
         * base64 returns one character for each 6 bits, the we should generate
         * at least 22*6/8=16.5 bytes, so we generate 17. Then we get the first
         * 22 base64 characters
         */
        $salt = substr(base64_encode(openssl_random_pseudo_bytes(17)), 0, 22);

        /* As blowfish takes a salt with the alphabet ./A-Za-z0-9 we have to
         * replace any '+' in the base64 string with '.'. We don't have to do
         * anything about the '=', as this only occurs when the b64 string is
         * padded, which is always after the first 22 characters.
         */
        $salt = str_replace("+", ".", $salt);
        $salt = '$2y$07$' . $salt;

        return crypt($string, $salt);
    }

    /**
     * @param $string
     * @param $hash
     *
     * @return bool
     */
    public static function verify($string, $hash)
    {
        return crypt($string, $hash) == $hash;
    }
}
