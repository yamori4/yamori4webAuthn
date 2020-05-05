<?php
class Algorithm
{
    // cf. https://www.iana.org/assignments/cose/cose.xhtml
    public const ES256 = -7;
    public const ES384 = -35;
    public const ES512 = -36;
    public const PS256 = -37;
    public const PS384 = -38;
    public const PS512 = -39;
    public const RSAES = -40;
    public const RSAES256 = -41;
    public const RSAES512 = -42;
    public const RS256 = -257;
    public const RS384 = -258;
    public const RS512 = -259;
    public const RS1 = -65535;

    public function getHashAlgName($intAlg)
    {
        switch ($intAlg) {
            case self::RS1:
            case self::RSAES:
                return "sha1";
            case self::ES384:
            case self::PS384:
            case self::RS384:
                return "sha384";
            case self::ES512:
            case self::PS512:
            case self::RSAES512:
            case self::RS512:
                return "sha512";
            case self::ES256:
            case self::PS256:
            case self::RSAES256:
            case self::RS256:
            default:
                return "sha256";
        }
    }
}
