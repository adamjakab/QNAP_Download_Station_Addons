<?php

class AddonHelper
{
    public static function UnitSize($unit) {
        switch (strtoupper(trim($unit))) {
            case "KB": return 1000;
            case "MB": return 1000000;
            case "GB": return 1000000000;
            case "TB": return 1000000000000;
            default: return 1;
        }
    }
}