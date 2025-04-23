<?php

require 'connection.php';

require __DIR__.'/../auth/JwtHandler.php';

class Login {

    use Connection;

    protected $dbh;

    protected $length;

    protected $token;

    protected $url;

    protected $website;

    protected $site_title;

    public function __construct(){

        $this->dbh = $this->connect(); // Connection with DB

        $this->length = 10; // Auth string partial length

        $this->url = "https://api.smsapi.pl/sms.do"; // External SMS operator API endpoint

        $this->token = 'your_token';  // Access token for external SMS operator        

        $this->website = "http://localhost:3000"; // Full URL on which the app is running

        $this->site_title = "Localhost"; // Site title

        $this->app_location = 'your_domain'; // Like aplikacja.pp-start.pl

    }

    // Check if there is connection to the DB

    public function checkConnection(){

        if(($this->dbh instanceof PDO)){

            return true;

        } else {

            $response = array('message' => 'No connection to DB');

            echo json_encode($response);

            return false;

        }

    }

    // METHOD - MAIL

    // Remove emoji from string
    
    public function remove_emoji($text){

        return preg_replace('/\x{1F3F4}\x{E0067}\x{E0062}(?:\x{E0077}\x{E006C}\x{E0073}|\x{E0073}\x{E0063}\x{E0074}|\x{E0065}\x{E006E}\x{E0067})\x{E007F}|(?:\x{1F9D1}\x{1F3FF}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FF}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FF}\x{200D}\x{1FAF2})[\x{1F3FB}-\x{1F3FE}]|(?:\x{1F9D1}\x{1F3FE}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FE}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FE}\x{200D}\x{1FAF2})[\x{1F3FB}-\x{1F3FD}\x{1F3FF}]|(?:\x{1F9D1}\x{1F3FD}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FD}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FD}\x{200D}\x{1FAF2})[\x{1F3FB}\x{1F3FC}\x{1F3FE}\x{1F3FF}]|(?:\x{1F9D1}\x{1F3FC}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FC}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FC}\x{200D}\x{1FAF2})[\x{1F3FB}\x{1F3FD}-\x{1F3FF}]|(?:\x{1F9D1}\x{1F3FB}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F9D1}|\x{1F469}\x{1F3FB}\x{200D}\x{1F91D}\x{200D}[\x{1F468}\x{1F469}]|\x{1FAF1}\x{1F3FB}\x{200D}\x{1FAF2})[\x{1F3FC}-\x{1F3FF}]|\x{1F468}(?:\x{1F3FB}(?:\x{200D}(?:\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FF}]|\x{1F468}[\x{1F3FB}-\x{1F3FF}])|\x{200D}(?:\x{1F48B}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FF}]|\x{1F468}[\x{1F3FB}-\x{1F3FF}]))|\x{1F91D}\x{200D}\x{1F468}[\x{1F3FC}-\x{1F3FF}]|[\x{2695}\x{2696}\x{2708}]\x{FE0F}|[\x{2695}\x{2696}\x{2708}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]))?|[\x{1F3FC}-\x{1F3FF}]\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FF}]|\x{1F468}[\x{1F3FB}-\x{1F3FF}])|\x{200D}(?:\x{1F48B}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FF}]|\x{1F468}[\x{1F3FB}-\x{1F3FF}]))|\x{200D}(?:\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D})?|\x{200D}(?:\x{1F48B}\x{200D})?)\x{1F468}|[\x{1F468}\x{1F469}]\x{200D}(?:\x{1F466}\x{200D}\x{1F466}|\x{1F467}\x{200D}[\x{1F466}\x{1F467}])|\x{1F466}\x{200D}\x{1F466}|\x{1F467}\x{200D}[\x{1F466}\x{1F467}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FF}\x{200D}(?:\x{1F91D}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FE}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FE}\x{200D}(?:\x{1F91D}\x{200D}\x{1F468}[\x{1F3FB}-\x{1F3FD}\x{1F3FF}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FD}\x{200D}(?:\x{1F91D}\x{200D}\x{1F468}[\x{1F3FB}\x{1F3FC}\x{1F3FE}\x{1F3FF}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FC}\x{200D}(?:\x{1F91D}\x{200D}\x{1F468}[\x{1F3FB}\x{1F3FD}-\x{1F3FF}]|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])\x{FE0F}|\x{200D}(?:[\x{1F468}\x{1F469}]\x{200D}[\x{1F466}\x{1F467}]|[\x{1F466}\x{1F467}])|\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FF}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{200D}[\x{2695}\x{2696}\x{2708}])?|(?:\x{1F469}(?:\x{1F3FB}\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}])|\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}]))|[\x{1F3FC}-\x{1F3FF}]\x{200D}\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}])|\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}])))|\x{1F9D1}[\x{1F3FB}-\x{1F3FF}]\x{200D}\x{1F91D}\x{200D}\x{1F9D1})[\x{1F3FB}-\x{1F3FF}]|\x{1F469}\x{200D}\x{1F469}\x{200D}(?:\x{1F466}\x{200D}\x{1F466}|\x{1F467}\x{200D}[\x{1F466}\x{1F467}])|\x{1F469}(?:\x{200D}(?:\x{2764}(?:\x{FE0F}\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}])|\x{200D}(?:\x{1F48B}\x{200D}[\x{1F468}\x{1F469}]|[\x{1F468}\x{1F469}]))|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FF}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FE}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FD}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FC}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FB}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F9D1}(?:\x{200D}(?:\x{1F91D}\x{200D}\x{1F9D1}|[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F3FF}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FE}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FD}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FC}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}]|\x{1F3FB}\x{200D}[\x{1F33E}\x{1F373}\x{1F37C}\x{1F384}\x{1F393}\x{1F3A4}\x{1F3A8}\x{1F3EB}\x{1F3ED}\x{1F4BB}\x{1F4BC}\x{1F527}\x{1F52C}\x{1F680}\x{1F692}\x{1F9AF}-\x{1F9B3}\x{1F9BC}\x{1F9BD}])|\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466}|\x{1F469}\x{200D}\x{1F469}\x{200D}[\x{1F466}\x{1F467}]|\x{1F469}\x{200D}\x{1F467}\x{200D}[\x{1F466}\x{1F467}]|(?:\x{1F441}\x{FE0F}?\x{200D}\x{1F5E8}|\x{1F9D1}(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FB}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])|\x{1F469}(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FB}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])|\x{1F636}\x{200D}\x{1F32B}|\x{1F3F3}\x{FE0F}?\x{200D}\x{26A7}|\x{1F43B}\x{200D}\x{2744}|(?:[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93D}\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}][\x{1F3FB}-\x{1F3FF}]|[\x{1F46F}\x{1F9DE}\x{1F9DF}])\x{200D}[\x{2640}\x{2642}]|[\x{26F9}\x{1F3CB}\x{1F3CC}\x{1F575}](?:[\x{FE0F}\x{1F3FB}-\x{1F3FF}]\x{200D}[\x{2640}\x{2642}]|\x{200D}[\x{2640}\x{2642}])|\x{1F3F4}\x{200D}\x{2620}|[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93C}-\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}]\x{200D}[\x{2640}\x{2642}]|[\xA9\xAE\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}\x{21AA}\x{231A}\x{231B}\x{2328}\x{23CF}\x{23ED}-\x{23EF}\x{23F1}\x{23F2}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}\x{25AB}\x{25B6}\x{25C0}\x{25FB}\x{25FC}\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}\x{2615}\x{2618}\x{2620}\x{2622}\x{2623}\x{2626}\x{262A}\x{262E}\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{265F}\x{2660}\x{2663}\x{2665}\x{2666}\x{2668}\x{267B}\x{267E}\x{267F}\x{2692}\x{2694}-\x{2697}\x{2699}\x{269B}\x{269C}\x{26A0}\x{26A7}\x{26AA}\x{26B0}\x{26B1}\x{26BD}\x{26BE}\x{26C4}\x{26C8}\x{26CF}\x{26D1}\x{26D3}\x{26E9}\x{26F0}-\x{26F5}\x{26F7}\x{26F8}\x{26FA}\x{2702}\x{2708}\x{2709}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2733}\x{2734}\x{2744}\x{2747}\x{2763}\x{27A1}\x{2934}\x{2935}\x{2B05}-\x{2B07}\x{2B1B}\x{2B1C}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F170}\x{1F171}\x{1F17E}\x{1F17F}\x{1F202}\x{1F237}\x{1F321}\x{1F324}-\x{1F32C}\x{1F336}\x{1F37D}\x{1F396}\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}\x{1F39F}\x{1F3CD}\x{1F3CE}\x{1F3D4}-\x{1F3DF}\x{1F3F5}\x{1F3F7}\x{1F43F}\x{1F4FD}\x{1F549}\x{1F54A}\x{1F56F}\x{1F570}\x{1F573}\x{1F576}-\x{1F579}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F5A5}\x{1F5A8}\x{1F5B1}\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}\x{1F6CB}\x{1F6CD}-\x{1F6CF}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6F0}\x{1F6F3}])\x{FE0F}|\x{1F441}\x{FE0F}?\x{200D}\x{1F5E8}|\x{1F9D1}(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FB}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])|\x{1F469}(?:\x{1F3FF}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FE}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FD}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FC}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{1F3FB}\x{200D}[\x{2695}\x{2696}\x{2708}]|\x{200D}[\x{2695}\x{2696}\x{2708}])|\x{1F3F3}\x{FE0F}?\x{200D}\x{1F308}|\x{1F469}\x{200D}\x{1F467}|\x{1F469}\x{200D}\x{1F466}|\x{1F636}\x{200D}\x{1F32B}|\x{1F3F3}\x{FE0F}?\x{200D}\x{26A7}|\x{1F635}\x{200D}\x{1F4AB}|\x{1F62E}\x{200D}\x{1F4A8}|\x{1F415}\x{200D}\x{1F9BA}|\x{1FAF1}(?:\x{1F3FF}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{1F3FB})?|\x{1F9D1}(?:\x{1F3FF}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{1F3FB})?|\x{1F469}(?:\x{1F3FF}|\x{1F3FE}|\x{1F3FD}|\x{1F3FC}|\x{1F3FB})?|\x{1F43B}\x{200D}\x{2744}|(?:[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93D}\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}][\x{1F3FB}-\x{1F3FF}]|[\x{1F46F}\x{1F9DE}\x{1F9DF}])\x{200D}[\x{2640}\x{2642}]|[\x{26F9}\x{1F3CB}\x{1F3CC}\x{1F575}](?:[\x{FE0F}\x{1F3FB}-\x{1F3FF}]\x{200D}[\x{2640}\x{2642}]|\x{200D}[\x{2640}\x{2642}])|\x{1F3F4}\x{200D}\x{2620}|\x{1F1FD}\x{1F1F0}|\x{1F1F6}\x{1F1E6}|\x{1F1F4}\x{1F1F2}|\x{1F408}\x{200D}\x{2B1B}|\x{2764}(?:\x{FE0F}\x{200D}[\x{1F525}\x{1FA79}]|\x{200D}[\x{1F525}\x{1FA79}])|\x{1F441}\x{FE0F}?|\x{1F3F3}\x{FE0F}?|[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93C}-\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}]\x{200D}[\x{2640}\x{2642}]|\x{1F1FF}[\x{1F1E6}\x{1F1F2}\x{1F1FC}]|\x{1F1FE}[\x{1F1EA}\x{1F1F9}]|\x{1F1FC}[\x{1F1EB}\x{1F1F8}]|\x{1F1FB}[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F3}\x{1F1FA}]|\x{1F1FA}[\x{1F1E6}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1FE}\x{1F1FF}]|\x{1F1F9}[\x{1F1E6}\x{1F1E8}\x{1F1E9}\x{1F1EB}-\x{1F1ED}\x{1F1EF}-\x{1F1F4}\x{1F1F7}\x{1F1F9}\x{1F1FB}\x{1F1FC}\x{1F1FF}]|\x{1F1F8}[\x{1F1E6}-\x{1F1EA}\x{1F1EC}-\x{1F1F4}\x{1F1F7}-\x{1F1F9}\x{1F1FB}\x{1F1FD}-\x{1F1FF}]|\x{1F1F7}[\x{1F1EA}\x{1F1F4}\x{1F1F8}\x{1F1FA}\x{1F1FC}]|\x{1F1F5}[\x{1F1E6}\x{1F1EA}-\x{1F1ED}\x{1F1F0}-\x{1F1F3}\x{1F1F7}-\x{1F1F9}\x{1F1FC}\x{1F1FE}]|\x{1F1F3}[\x{1F1E6}\x{1F1E8}\x{1F1EA}-\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F4}\x{1F1F5}\x{1F1F7}\x{1F1FA}\x{1F1FF}]|\x{1F1F2}[\x{1F1E6}\x{1F1E8}-\x{1F1ED}\x{1F1F0}-\x{1F1FF}]|\x{1F1F1}[\x{1F1E6}-\x{1F1E8}\x{1F1EE}\x{1F1F0}\x{1F1F7}-\x{1F1FB}\x{1F1FE}]|\x{1F1F0}[\x{1F1EA}\x{1F1EC}-\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1FC}\x{1F1FE}\x{1F1FF}]|\x{1F1EF}[\x{1F1EA}\x{1F1F2}\x{1F1F4}\x{1F1F5}]|\x{1F1EE}[\x{1F1E8}-\x{1F1EA}\x{1F1F1}-\x{1F1F4}\x{1F1F6}-\x{1F1F9}]|\x{1F1ED}[\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F9}\x{1F1FA}]|\x{1F1EC}[\x{1F1E6}\x{1F1E7}\x{1F1E9}-\x{1F1EE}\x{1F1F1}-\x{1F1F3}\x{1F1F5}-\x{1F1FA}\x{1F1FC}\x{1F1FE}]|\x{1F1EB}[\x{1F1EE}-\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F7}]|\x{1F1EA}[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F7}-\x{1F1FA}]|\x{1F1E9}[\x{1F1EA}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1FF}]|\x{1F1E8}[\x{1F1E6}\x{1F1E8}\x{1F1E9}\x{1F1EB}-\x{1F1EE}\x{1F1F0}-\x{1F1F5}\x{1F1F7}\x{1F1FA}-\x{1F1FF}]|\x{1F1E7}[\x{1F1E6}\x{1F1E7}\x{1F1E9}-\x{1F1EF}\x{1F1F1}-\x{1F1F4}\x{1F1F6}-\x{1F1F9}\x{1F1FB}\x{1F1FC}\x{1F1FE}\x{1F1FF}]|\x{1F1E6}[\x{1F1E8}-\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F4}\x{1F1F6}-\x{1F1FA}\x{1F1FC}\x{1F1FD}\x{1F1FF}]|[#\*0-9]\x{FE0F}?\x{20E3}|\x{1F93C}[\x{1F3FB}-\x{1F3FF}]|\x{2764}\x{FE0F}?|[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93D}\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}][\x{1F3FB}-\x{1F3FF}]|[\x{26F9}\x{1F3CB}\x{1F3CC}\x{1F575}][\x{FE0F}\x{1F3FB}-\x{1F3FF}]?|\x{1F3F4}|[\x{270A}\x{270B}\x{1F385}\x{1F3C2}\x{1F3C7}\x{1F442}\x{1F443}\x{1F446}-\x{1F450}\x{1F466}\x{1F467}\x{1F46B}-\x{1F46D}\x{1F472}\x{1F474}-\x{1F476}\x{1F478}\x{1F47C}\x{1F483}\x{1F485}\x{1F48F}\x{1F491}\x{1F4AA}\x{1F57A}\x{1F595}\x{1F596}\x{1F64C}\x{1F64F}\x{1F6C0}\x{1F6CC}\x{1F90C}\x{1F90F}\x{1F918}-\x{1F91F}\x{1F930}-\x{1F934}\x{1F936}\x{1F977}\x{1F9B5}\x{1F9B6}\x{1F9BB}\x{1F9D2}\x{1F9D3}\x{1F9D5}\x{1FAC3}-\x{1FAC5}\x{1FAF0}\x{1FAF2}-\x{1FAF6}][\x{1F3FB}-\x{1F3FF}]|[\x{261D}\x{270C}\x{270D}\x{1F574}\x{1F590}][\x{FE0F}\x{1F3FB}-\x{1F3FF}]|[\x{261D}\x{270A}-\x{270D}\x{1F385}\x{1F3C2}\x{1F3C7}\x{1F408}\x{1F415}\x{1F43B}\x{1F442}\x{1F443}\x{1F446}-\x{1F450}\x{1F466}\x{1F467}\x{1F46B}-\x{1F46D}\x{1F472}\x{1F474}-\x{1F476}\x{1F478}\x{1F47C}\x{1F483}\x{1F485}\x{1F48F}\x{1F491}\x{1F4AA}\x{1F574}\x{1F57A}\x{1F590}\x{1F595}\x{1F596}\x{1F62E}\x{1F635}\x{1F636}\x{1F64C}\x{1F64F}\x{1F6C0}\x{1F6CC}\x{1F90C}\x{1F90F}\x{1F918}-\x{1F91F}\x{1F930}-\x{1F934}\x{1F936}\x{1F93C}\x{1F977}\x{1F9B5}\x{1F9B6}\x{1F9BB}\x{1F9D2}\x{1F9D3}\x{1F9D5}\x{1FAC3}-\x{1FAC5}\x{1FAF0}\x{1FAF2}-\x{1FAF6}]|[\x{1F3C3}\x{1F3C4}\x{1F3CA}\x{1F46E}\x{1F470}\x{1F471}\x{1F473}\x{1F477}\x{1F481}\x{1F482}\x{1F486}\x{1F487}\x{1F645}-\x{1F647}\x{1F64B}\x{1F64D}\x{1F64E}\x{1F6A3}\x{1F6B4}-\x{1F6B6}\x{1F926}\x{1F935}\x{1F937}-\x{1F939}\x{1F93D}\x{1F93E}\x{1F9B8}\x{1F9B9}\x{1F9CD}-\x{1F9CF}\x{1F9D4}\x{1F9D6}-\x{1F9DD}]|[\x{1F46F}\x{1F9DE}\x{1F9DF}]|[\xA9\xAE\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}\x{21AA}\x{231A}\x{231B}\x{2328}\x{23CF}\x{23ED}-\x{23EF}\x{23F1}\x{23F2}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}\x{25AB}\x{25B6}\x{25C0}\x{25FB}\x{25FC}\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}\x{2615}\x{2618}\x{2620}\x{2622}\x{2623}\x{2626}\x{262A}\x{262E}\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{265F}\x{2660}\x{2663}\x{2665}\x{2666}\x{2668}\x{267B}\x{267E}\x{267F}\x{2692}\x{2694}-\x{2697}\x{2699}\x{269B}\x{269C}\x{26A0}\x{26A7}\x{26AA}\x{26B0}\x{26B1}\x{26BD}\x{26BE}\x{26C4}\x{26C8}\x{26CF}\x{26D1}\x{26D3}\x{26E9}\x{26F0}-\x{26F5}\x{26F7}\x{26F8}\x{26FA}\x{2702}\x{2708}\x{2709}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2733}\x{2734}\x{2744}\x{2747}\x{2763}\x{27A1}\x{2934}\x{2935}\x{2B05}-\x{2B07}\x{2B1B}\x{2B1C}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F170}\x{1F171}\x{1F17E}\x{1F17F}\x{1F202}\x{1F237}\x{1F321}\x{1F324}-\x{1F32C}\x{1F336}\x{1F37D}\x{1F396}\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}\x{1F39F}\x{1F3CD}\x{1F3CE}\x{1F3D4}-\x{1F3DF}\x{1F3F5}\x{1F3F7}\x{1F43F}\x{1F4FD}\x{1F549}\x{1F54A}\x{1F56F}\x{1F570}\x{1F573}\x{1F576}-\x{1F579}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F5A5}\x{1F5A8}\x{1F5B1}\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}\x{1F6CB}\x{1F6CD}-\x{1F6CF}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6F0}\x{1F6F3}]|[\x{23E9}-\x{23EC}\x{23F0}\x{23F3}\x{25FD}\x{2693}\x{26A1}\x{26AB}\x{26C5}\x{26CE}\x{26D4}\x{26EA}\x{26FD}\x{2705}\x{2728}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2795}-\x{2797}\x{27B0}\x{27BF}\x{2B50}\x{1F0CF}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F236}\x{1F238}-\x{1F23A}\x{1F250}\x{1F251}\x{1F300}-\x{1F320}\x{1F32D}-\x{1F335}\x{1F337}-\x{1F37C}\x{1F37E}-\x{1F384}\x{1F386}-\x{1F393}\x{1F3A0}-\x{1F3C1}\x{1F3C5}\x{1F3C6}\x{1F3C8}\x{1F3C9}\x{1F3CF}-\x{1F3D3}\x{1F3E0}-\x{1F3F0}\x{1F3F8}-\x{1F407}\x{1F409}-\x{1F414}\x{1F416}-\x{1F43A}\x{1F43C}-\x{1F43E}\x{1F440}\x{1F444}\x{1F445}\x{1F451}-\x{1F465}\x{1F46A}\x{1F479}-\x{1F47B}\x{1F47D}-\x{1F480}\x{1F484}\x{1F488}-\x{1F48E}\x{1F490}\x{1F492}-\x{1F4A9}\x{1F4AB}-\x{1F4FC}\x{1F4FF}-\x{1F53D}\x{1F54B}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F5A4}\x{1F5FB}-\x{1F62D}\x{1F62F}-\x{1F634}\x{1F637}-\x{1F644}\x{1F648}-\x{1F64A}\x{1F680}-\x{1F6A2}\x{1F6A4}-\x{1F6B3}\x{1F6B7}-\x{1F6BF}\x{1F6C1}-\x{1F6C5}\x{1F6D0}-\x{1F6D2}\x{1F6D5}-\x{1F6D7}\x{1F6DD}-\x{1F6DF}\x{1F6EB}\x{1F6EC}\x{1F6F4}-\x{1F6FC}\x{1F7E0}-\x{1F7EB}\x{1F7F0}\x{1F90D}\x{1F90E}\x{1F910}-\x{1F917}\x{1F920}-\x{1F925}\x{1F927}-\x{1F92F}\x{1F93A}\x{1F93F}-\x{1F945}\x{1F947}-\x{1F976}\x{1F978}-\x{1F9B4}\x{1F9B7}\x{1F9BA}\x{1F9BC}-\x{1F9CC}\x{1F9D0}\x{1F9E0}-\x{1F9FF}\x{1FA70}-\x{1FA74}\x{1FA78}-\x{1FA7C}\x{1FA80}-\x{1FA86}\x{1FA90}-\x{1FAAC}\x{1FAB0}-\x{1FABA}\x{1FAC0}-\x{1FAC2}\x{1FAD0}-\x{1FAD9}\x{1FAE0}-\x{1FAE7}]/u', '', $text);

    }

    // Validate password strength

    public function validatePasswordStrength($password){

        return preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password);

    }

    // Generate authentication hash for password reset link

    public function encryptAuth($hash){

        $random_1 = rand(5, 15);

        $random_2 = rand(20, 30);

        $random_3 = rand(35, 45);

        $length = $this->length;

        $part_1 = substr($hash, $random_1, $length);

        $part_2 = substr($hash, $random_2, $length);

        $part_3 = substr($hash, $random_3, $length);

        $code = $part_1 . $part_2 . $part_3;

        return $code;

    }

    // Verify authentication hash for password reset link

    public function decryptAuth($hash, $auth){

        $sub_1 = substr($hash, 5, 20);

        $sub_2 = substr($hash, 20, 20);

        $sub_3 = substr($hash, 35, 20);

        $chunks = str_split($auth, 10);

        if(str_contains($sub_1, $chunks[0]) && str_contains($sub_2, $chunks[1]) && str_contains($sub_3, $chunks[2])){

            return true;

        } else {

            return false;

        }

    }

    // Verify login credentials

    public function checkCredentials($username_email, $password){

        if(!$this->checkConnection()){

            return;

        }

        $response = array();

        if(filter_var($username_email, FILTER_VALIDATE_EMAIL)){

            $email = array('email' => $username_email);

            $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE email = :email");

            $stmt->execute($email);

        } else {

            $username = array('username' => $username_email);

            $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE username = :username");

            $stmt->execute($username);

        }

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        if(!$result){

            $response['message'] = "User doesn't exist";

        } else {

            $verified = $result[0]['verified'];

            if(!$verified){

                $response['message'] = "You need to activate your account before you will be able to log in.";

            } else {

                $hash = $result[0]['password'];

                if(password_verify($password, $hash)){

                    $jwt = new JwtHandler();

                    $token = $jwt->jwtEncodeData('php_auth_api/', array("user_id"=> $result[0]['user_id']));

                    $user_id = $result[0]['user_id'];

                    $username = $result[0]['username'];

                    $email = $result[0]['email'];

                    $role = $result[0]['role'];

                    $message = "Redirecting...";

                    $response = array('token' => $token, 'username' => $username, 'user_id' => $user_id, 'role' => $role, 'email' => $email, 'message' => $message);

                } else {

                    $response['message'] = 'Password is incorrect';

                }

            }

        }

        echo json_encode($response);

    }

    // Handle password reset request

    public function initiatePasswordReset($email_remind){

        if(!$this->checkConnection()){

            return;

        }

        if(!filter_var($email_remind, FILTER_VALIDATE_EMAIL)){

            $response = array('message' => 'Invalid email address entered.');

            echo json_encode($response);

            return;
            
        }

        $email = array('email' => $email_remind);

        $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE email = :email");

        $stmt->execute($email);

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        if(!$result){

            $response = array('message' => 'Email address is not registered.');

            echo json_encode($response);

        } else {

            $verified = $result[0]['verified'];

            if(!$verified){

                $response = array('message' => 'You need to activate your account before you will be able to change password. If you want to resend activation link please click below.', 'resend' => true);

                echo json_encode($response);

            } else {

                $id = $result[0]['user_id'];

                $email = $result[0]['email'];

                $username = $result[0]['username'];

                $hash = $result[0]['password'];

                $auth = $this->encryptAuth($hash);

                $this->sendPasswordResetMessage($email, $username, $auth, $id);

            }

        }

    }

    // Send mail with password reset link

    public function sendPasswordResetMessage($email, $username, $auth, $id){

        $response = array();

        $headers = "Reply-To: Serwis PP Start <serwis@pp-start.pl>\r\n"; 
        $headers .= "Return-Path: Serwis PP Start <serwis@pp-start.pl>\r\n"; 
        $headers .= "From: Serwis PP Start <serwis@pp-start.pl>\r\n";  
        $headers .= "Organization: Sender Organization\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "X-Priority: 3\r\n";
        $headers .= "X-Mailer: PHP". phpversion() ."\r\n" ;
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";  

        $email_subject = "Restore access to " .$this->site_title;

        $email_message =  "<html>
                                    <head>
                                        <link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
                                        <style>
                                            body  {background-color:#f0edeb;padding-top:25px;padding-bottom:25px;} 
                                            .pp-page  {max-width:600px;display:flex;flex-direction:column;margin:0 auto;background-color:#fff;border-radius:10px;-webkit-box-shadow: 0px 0px 20px -5px rgba(76, 126, 254, 1);-moz-box-shadow: 0px 0px 20px -5px rgba(76, 126, 254, 1);box-shadow: 0px 0px 20px -5px rgba(76, 126, 254, 1);font-family:'Roboto';}
                                            .pp-header {display:flex;padding:25px;justify-content:center;align-items:center;border-bottom:5px solid #e8e8e8;}
                                            .pp-logo  {max-width: 250px;}
                                            #footer-link-paragraph a  {text-decoration:none;color:#2196f3;}
                                        </style>
                                    </head>
                                    <body>
                                        <div class='pp-page'>
                                            <div class='pp-header'>
                                                <a href='https://pp-start.pl'><img class='pp-logo' src='https://pp-start.pl/images/logo.webp' alt='logo'></a>
                                            </div>
                                            <p style='font-size:22px;font-weight:600;text-align:center;margin:20px;'>Hello " .$username . "!</p>
                                            <p style='font-size:18px;font-weight:500;text-align:center;margin:15px;margin-top:0;margin-bottom:5px;'>Somebody made a request to set new password for your account on website <a style='text-decoration:underline;' href='".$this->website."'>".$this->site_title."</a></p>
                                            <p style='font-size:18px;font-weight:500;text-align:center;margin-top:15px;margin-bottom:5px;'>If that wasn't you just ignore this message. Your password will remain unchanged and account secured.</p>
                                            <p style='font-size:18px;font-weight:500;text-align:center;margin-top:15px;margin-bottom:5px;'>To set new password for your account click below:</p>
                                            <p style='font-size:18px;font-weight:600;text-align:center;font-style:italic;margin-top:5px;margin-bottom:15px;margin-left:15px;margin-right:15px;line-height:26px;'><a style='text-decoration:underline;' href='" . $this->website . "/?user_id=" . $id . "&auth=" . $auth . "'>click here</a></p>
                                            <div class='pp-footer' style='background-color:#4d4545;border-bottom-left-radius:10px;border-bottom-right-radius:10px;'>
                                                <p style='text-align:center;color:#fff;font-size:18px;font-weight:500;margin-top:25px;user-select:none;'>Best regards</p>
                                                <p style='text-align:center;user-select:none;margin:15px;'><img style='max-width:200px;' src='https://pp-start.pl/images/signature.webp' alt='Paweł Pokrywka'><p>
                                                <p style='font-size:18px;font-weight:600;text-align:center;text-decoration:none;margin:15px;'><a style='text-decoration:none;color:#2196f3;'href='tel:662890561'>662-890-561</a></p>
                                                <p id='footer-link-paragraph' style='font-size:18px;font-weight:600;text-align:center;text-decoration:none;margin-top:15px;margin-bottom:25px;'><a style='text-decoration:none;color:#2196f3;' href='mailto:serwis@pp-start.pl'>serwis@pp-start.pl</a></p>
                                            </div>
                                        </div>
                                    <body>
                                </html>";

        // Sending message

        $send_mail = mail($email, $email_subject, $email_message, $headers);

        if($send_mail === true){

            $response['success'] = true;

            $response['message'] = 'A message has been sent to your inbox. Use it to set new password for your account.';

        } else {

            $response['message'] = 'There was an error when sending message. Please try again later.';

        }

        echo json_encode($response);

    }

    // Set new password

    public function resetPassword($user_id, $auth, $new_password){

        if(!$this->checkConnection()){

            return;

        }

        $user_id = array('user_id' => $user_id);

        $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE `user_id` = :user_id");

        $stmt->execute($user_id);

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        if(!empty($result)){

            $hash = $result[0]['password'];

            if($this->decryptAuth($hash, $auth)){

                if($this->validatePasswordStrength($new_password)){

                    $password = password_hash($new_password, PASSWORD_DEFAULT);

                    $stmt = $this->dbh->prepare("UPDATE `users_mail` SET `password` = '$password' WHERE `user_id` = :user_id");

                    $stmt->execute($user_id);

                    $response['success'] = true;

                    $response['message'] = 'Password was changed. You can now log in.';

                } else {

                    $response['message'] = 'Password must have at least 8 characters, one capital letter, one digit and a special character.';

                }

            } else {

                $response['redirect'] = true;

                $response['message'] = 'This link is not longer active. If you still need to change password please request it again.';

            }

        } else {

            $response['message'] = "Can't change password. User doesn't exist.";

        }

        echo json_encode($response);

    }

    // Check if username is free

    public function checkUsername($username){

        if(!$this->checkConnection()){

            return null;

        }

        $username = array('username' => $username);

        $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE `username` = :username");

        $stmt->execute($username);

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        if(empty($result)){

            return true;

        } else {

            return false;

        }

    }

    // Register new user

    public function registerUser($register_username, $register_email, $register_password, $register_policy_acceptation){

        if(!$this->checkConnection()){

            return null;

        }

        $error = "";

        $response = array();

        $username = trim($this->remove_emoji($register_username));

        $username_length = strlen($username);

        if($username_length === 0){

            $error .= "Username can't be empty.\n";

        } else if($username_length < 4){

            $error .= "Username must have at least 4 characters.\n";

        }

        if(!filter_var($register_email, FILTER_VALIDATE_EMAIL)){

            $error .= "Invalid email address entered.\n";

        }

        if(!$this->validatePasswordStrength($register_password)){

            $error .= "Password must have at least 8 characters, one capital letter, one digit and a special character.\n";

        }

        if($register_policy_acceptation !== true){

            $error .= "You need to accept data processing and privacy policy.\n";

        }

        if(!empty($error)){

            $response['message'] = $error;

            echo json_encode($response);

        } else {

            $username_check = $this->checkUsername($username);

            if(!$username_check){

                $response['message'] = "Username already exists. Please choose a different username.";

                echo json_encode($response);

                return;

            }

            $email = array('email' => $register_email);

            $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE `email` = :email");

            $stmt->execute($email);

            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $stmt->closeCursor();

            if(!empty($result)){

                $response['message'] = "Email is already registered. If you don't remember your password please reset it by clicking 'Forgot password' button on sign-in screen.";

                echo json_encode($response);

                return;

            }

            $stmt_user_id = $this->dbh->prepare("SELECT MAX(CAST(SUBSTRING(user_id, 2) AS UNSIGNED)) AS max_user_id FROM `users_mail` WHERE user_id != '-'");

            $stmt_user_id->execute();

            $result = $stmt_user_id->fetchAll(\PDO::FETCH_ASSOC);

            $stmt_user_id->closeCursor();

            $max_id = $result[0]['max_user_id'];

            $user_id = 'U' . str_pad($max_id + 1, 3, '0', STR_PAD_LEFT);

            $password = password_hash($register_password, PASSWORD_DEFAULT);

            $params = array('user_id' => $user_id, 'username' => $username, 'email' => $register_email, 'password' => $password, 'role' => 'user', 'verified' => 0);

            $columns = implode(", ", array_keys($params));

            $placeholders = ":" . implode(", :", array_keys($params));

            $stmt_insert = $this->dbh->prepare("INSERT INTO `users_mail` ($columns) VALUES ($placeholders)");

            $stmt_insert->execute($params);

            if($stmt_insert->rowCount() !== 1){

                $response['message'] = 'Database connection error. Please try again later.';
    
            } else {

                $response['success'] = true;

                $response['message'] = 'Registration was successful. Before you will be able to log in you need to activate account by clicking activation link in the message sent to your inbox.';

                $this->sendActivationMessage($params);

            }

            echo json_encode($response);

        }

    }

    // Resend account activation link

    public function resendActivationLink($data){

        if(!$this->checkConnection()){

            return;

        }

        $response = array();

        if(filter_var($data, FILTER_VALIDATE_EMAIL)){

            $email = array('email' => $data);

            $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE email = :email");

            $stmt->execute($email);

        } else {

            $user_id = array('user_id' => $data);

            $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE user_id = :user_id");

            $stmt->execute($user_id);

        }

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        if(!empty($result)){

            $verified = $result[0]['verified'];

            if($verified){

                $response['message'] = 'Account has already been activated.';

            } else {

                $data = array('user_id' => $result[0]['user_id'], 'username' => $result[0]['username'], 'email' => $result[0]['email'], 'password' => $result[0]['password']);

                $this->sendActivationMessage($data);

                $response['success'] = true;

                $response['message'] = 'Activation message has been re-send to your email.';

            }

        } else {

            $response['message'] = "Email address is not registered.";

        }

        echo json_encode($response);

    }

    // Send message with activation link

    public function sendActivationMessage($data){

        $user_id = $data['user_id'];

        $username = $data['username'];

        $email = $data['email'];

        $password_hash = $data['password'];

        $auth = $this->encryptAuth($password_hash);

        $headers = "Reply-To: Serwis PP Start <serwis@pp-start.pl>\r\n"; 
        $headers .= "Return-Path: Serwis PP Start <serwis@pp-start.pl>\r\n"; 
        $headers .= "From: Serwis PP Start <serwis@pp-start.pl>\r\n";  
        $headers .= "Organization: Sender Organization\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "X-Priority: 3\r\n";
        $headers .= "X-Mailer: PHP". phpversion() ."\r\n" ;
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";  

        $email_subject = "Activate your account - " .$this->site_title;

        $email_message =  "<html>
                                    <head>
                                        <link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
                                        <style>
                                            body  {background-color:#f0edeb;padding-top:25px;padding-bottom:25px;} 
                                            .pp-page  {max-width:600px;display:flex;flex-direction:column;margin:0 auto;background-color:#fff;border-radius:10px;-webkit-box-shadow: 0px 0px 20px -5px rgba(76, 126, 254, 1);-moz-box-shadow: 0px 0px 20px -5px rgba(76, 126, 254, 1);box-shadow: 0px 0px 20px -5px rgba(76, 126, 254, 1);font-family:'Roboto';}
                                            .pp-header {display:flex;padding:25px;justify-content:center;align-items:center;border-bottom:5px solid #e8e8e8;}
                                            .pp-logo  {max-width: 250px;}
                                            #footer-link-paragraph a  {text-decoration:none;color:#2196f3;}
                                        </style>
                                    </head>
                                    <body>
                                        <div class='pp-page'>
                                            <div class='pp-header'>
                                                <a href='https://pp-start.pl'><img class='pp-logo' src='https://pp-start.pl/images/logo.webp' alt='logo'></a>
                                            </div>
                                            <p style='font-size:22px;font-weight:600;text-align:center;margin:20px;'>Hello " .$username . "!</p>
                                            <p style='font-size:18px;font-weight:500;text-align:center;margin:15px;margin-top:0;margin-bottom:5px;'>Thank you for registering your account on website <a style='text-decoration:underline;' href='".$this->website."'>".$this->site_title."</a></p>
                                            <p style='font-size:18px;font-weight:500;text-align:center;margin-top:15px;margin-bottom:5px;'>Before you will be able to log in you need to activate your account.</p>
                                            <p style='font-size:18px;font-weight:500;text-align:center;margin-top:15px;margin-bottom:5px;'>You can do it by clicking link below:</p>
                                            <p style='font-size:18px;font-weight:600;text-align:center;font-style:italic;margin-top:5px;margin-bottom:15px;margin-left:15px;margin-right:15px;line-height:26px;'><a style='text-decoration:underline;' href='" . $this->website . "/?user_id=" . $user_id . "&auth_reg=" . $auth . "'>click here</a></p>
                                            <div class='pp-footer' style='background-color:#4d4545;border-bottom-left-radius:10px;border-bottom-right-radius:10px;'>
                                                <p style='text-align:center;color:#fff;font-size:18px;font-weight:500;margin-top:25px;user-select:none;'>Best regards</p>
                                                <p style='text-align:center;user-select:none;margin:15px;'><img style='max-width:200px;' src='https://pp-start.pl/images/signature.webp' alt='Paweł Pokrywka'><p>
                                                <p style='font-size:18px;font-weight:600;text-align:center;text-decoration:none;margin:15px;'><a style='text-decoration:none;color:#2196f3;'href='tel:662890561'>662-890-561</a></p>
                                                <p id='footer-link-paragraph' style='font-size:18px;font-weight:600;text-align:center;text-decoration:none;margin-top:15px;margin-bottom:25px;'><a style='text-decoration:none;color:#2196f3;' href='mailto:serwis@pp-start.pl'>serwis@pp-start.pl</a></p>
                                            </div>
                                        </div>
                                    <body>
                                </html>";

        // Sending message

        $send_mail = mail($email, $email_subject, $email_message, $headers);

    }

    // Verification of registered email

    public function activateRegisteredUser($activate_user_id, $auth_reg){

        if(!$this->checkConnection()){

            return;

        }

        $response = array();

        $user_id = array('user_id' => $activate_user_id);

        $stmt = $this->dbh->prepare("SELECT * FROM `users_mail` WHERE user_id = :user_id");

        $stmt->execute($user_id);

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        if(!empty($result)){

            $verified = $result[0]['verified'];

            if(!$verified){

                $hash = $result[0]['password'];

                if($this->decryptAuth($hash, $auth_reg)){

                    $stmt = $this->dbh->prepare("UPDATE `users_mail` SET verified = 1 WHERE user_id = :user_id");

                    $stmt->execute($user_id);

                    if($stmt->rowCount() === 1){

                        $response['success'] = true;

                        $response['message'] = "Your account has been activated. You can now log in.";

                    } else {

                        $response['message'] = "Database update problem. Please try again later.";

                        $response['redirect'] = true;

                    }

                } else {
    
                    $response['message'] = "Invalid activation link. You can get new link by clicking below:";

                    $response['resend'] = true;
    
                }

            } else {

                $response['message'] = "Your account has been activated before.";

                $response['redirect'] = true;

            }

        } else {

            $response['message'] = "Can't activate, user doesn't exist.";

            $response['redirect'] = true;

        }

        echo json_encode($response);

    }

    // METHOD - PHONE

    // Login with phone number

    public function generateOTP($phone_number, $policy_acceptation, $verification_code){

        if(!$this->checkConnection()){

            return;

        }

        $response = array();

        $error = "";

        if(!ctype_digit($phone_number)){

            $error .= "Invalid phone number format.\n";

        }

        if($policy_acceptation !== true){

            $error .= "You need to accept data processing and privacy policy.\n";

        }

        if(!$verification_code){

            $error .= "Your request is corrupted. Please refresh page and try again.\n";

        }

        if(!empty($error)){

            $response['message'] = $error;

            echo json_encode($response);

        } else {

            // Checking if phone number exists in DB

            $phone = array('phone_number' => $phone_number);

            $stmt_check = $this->dbh->prepare("SELECT * FROM `users_phone` WHERE `phone_number` = :phone_number");

            $stmt_check->execute($phone);

            $result = $stmt_check->fetchAll(\PDO::FETCH_ASSOC);

            $stmt_check->closeCursor();

            // Creating new user if phone number was not found

            if(empty($result)){

                $stmt_user_id = $this->dbh->prepare("SELECT MAX(CAST(SUBSTRING(user_id, 2) AS UNSIGNED)) AS max_user_id FROM `users_phone` WHERE user_id != '-'");

                $stmt_user_id->execute();

                $result = $stmt_user_id->fetchAll(\PDO::FETCH_ASSOC);

                $stmt_user_id->closeCursor();

                $max_id = $result[0]['max_user_id'];

                $user_id = 'U' . str_pad($max_id + 1, 3, '0', STR_PAD_LEFT);

                $stmt_insert = $this->dbh->prepare("INSERT INTO `users_phone` (user_id, phone_number) VALUES (:user_id, :phone_number)");

                $params = array(
                    'user_id' => $user_id,
                    'phone_number' => $phone_number
                );

                $stmt_insert->execute($params);

                $stmt_insert->closeCursor();

            } else {

                $user_id = $result[0]['user_id'];

                $current_time = date('Y-m-d H:i:s');

                $prev_otp_time = $result[0]['otp_time'];

                if(!empty($prev_otp_time)){

                    $date1 = new DateTime($current_time);

                    $date2 = new DateTime($prev_otp_time);

                    $seconds = $date1->getTimestamp() - $date2->getTimestamp();

                    if($seconds < 120){

                        $response['time'] = $prev_otp_time;

                        $response['message'] = 'New verification SMS can be only send after 2 minutes has passed since previous request.';

                        echo json_encode($response);

                        return;

                    }

                }

            }

            // Generating one time password

            $one_time_password = $this->generateCode();

            $otp_time = date('Y-m-d H:i:s');

            $stmt_otp = $this->dbh->prepare("UPDATE `users_phone` SET `verification_code` = :verification_code, `one_time_password` = :one_time_password, `otp_time` = :otp_time WHERE `user_id` = :user_id AND `phone_number` = :phone_number");

            $params = array(
                'verification_code' => $verification_code,
                'one_time_password' => $one_time_password,
                'otp_time' => $otp_time,
                'user_id' => $user_id,
                'phone_number' => $phone_number
            );

            $stmt_otp->execute($params);

            $stmt_otp->closeCursor();

            if($stmt_otp->rowCount() !== 1){

                $response['message'] = 'Database connection error. Please try again later.';

                echo json_encode($response);

                return;

            }

            $message = 'Your verification code: ' . $one_time_password . '. @' . $this->app_location . ' #' . $one_time_password . ' ' . $verification_code;

            /*

            // Uncomment if using smsapi.pl provider and want to test live SMS sending

            $data = [
                'to' => $phone_number,
                'message' => $message,
                'from' => 'PP-Start',
                'format' => 'json',
                'encoding' => 'utf-8',
                'access_token' => $this->token
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);

            curl_close($ch);

            */

            // Simulating correct result(SMS sent, no errors)

            $result = '{"count":1,"list":[{"id":"67A4747337303292412C498D","points":0.17,"number":"48662754739","date_sent":1738830963,"submitted_number":"+48662754739","status":"QUEUE","error":null,"idx":null,"parts":1}]}';

            $output = json_decode($result, true);

            if(array_key_exists('error', $output)){

                $response['message'] = 'Unable to send verification message. Please try again later.';

            } else {

                $response['success'] = true;

            }

            echo json_encode($response);

        }

    }

    // Generate One Time Password

    public function generateCode(){

        $code = '';

        for($i = 1 ; $i <= 6 ; $i++){

            $code .= strval(mt_rand(0, 9));

        }

        return $code;

    }

    // Verify One Time Password

    public function verifyOTP($phone_number, $one_time_password, $verification_code){

        if(!$this->checkConnection()){

            return;

        }

        $response = array();

        // Getting data from DB

        $phone_number = array('phone_number' => $phone_number);

        $stmt_check = $this->dbh->prepare("SELECT * FROM `users_phone` WHERE `phone_number` = :phone_number");

        $stmt_check->execute($phone_number);

        $result = $stmt_check->fetchAll(\PDO::FETCH_ASSOC);

        $stmt_check->closeCursor();

        if(empty($result)){

            $response['message'] = 'Incorrect phone number. Please log again.';

            $response['return'] = true;

            echo json_encode($response);

            return;

        }

        // Checking password data

        $data = $result[0];

        $user_id = $data['user_id'];

        $prev_one_time_password = $data['one_time_password'];

        $prev_verification_code = $data['verification_code'];

        $prev_otp_time = $data['otp_time'];

        if(empty($prev_one_time_password) || empty($prev_verification_code) || empty($prev_otp_time)){

            $response['message'] = 'Error reading data. Please log again.';

            $response['return'] = true;

            echo json_encode($response);

            return;

        }

        // Checking if password matches:

        if($prev_one_time_password !== $one_time_password){

            $response['message'] = 'Password is incorrect. Please try again.';

            echo json_encode($response);

            return;

        }

        // Checking time difference - set to 5 minutes(300 seconds). You can set your own password expiration time modifying the 300 number:

        $current_time = date('Y-m-d H:i:s');

        $date1 = new DateTime($current_time);

        $date2 = new DateTime($prev_otp_time);

        $seconds = $date1->getTimestamp() - $date2->getTimestamp();

        if($seconds > 300){ 

            $response['message'] = 'Password expired. Please log again.';

            $response['return'] = true;

            echo json_encode($response);

            return;

        }

        // Checking verification code(invisible for user)

        if($prev_verification_code !== $verification_code){

            $response['message'] = 'Invalid verification code. Please log again.';

            $response['return'] = true;

            echo json_encode($response);

            return;

        }

        $jwt = new JwtHandler();

        $token = $jwt->jwtEncodeData('php_auth_api/', array("user_id"=> $user_id));

        $response = array('token' => $token, 'user_id' => $user_id, 'phone_number' => $phone_number, 'role' => 'user', 'success' => true);

        echo json_encode($response);

    }


}

if($_SERVER['REQUEST_METHOD'] === "POST"){

    $input = json_decode(file_get_contents("php://input"), true);

    $data = $input['formData'];

    $request_type = $data['request_type'];

    $login = new Login();

    switch ($request_type){

        // METHOD - MAIL

        // Verify login credentials

        case 'mail_login':

            $username_email = $data['username_email'];

            $password = $data['password'];

            $login->checkCredentials($username_email, $password);

            break;

        // Handle password reset request

        case "initiate_password_reset":

            $email_remind = $data['email_remind'];
        
            $login->initiatePasswordReset($email_remind);

            break;

        // Setting new password

        case 'reset_password':

            $new_password = $data['new_password'];
    
            $user_id = $data['user_id'];
    
            $auth = $data['auth'];

            $login->resetPassword($user_id, $auth, $new_password);
        
            break;

        // Check if username is free

        case 'username_check':

            $username = $data['username_check'];

            $check = $login->checkUsername($username);

            $response = ['result' => $check];

            echo json_encode($response);
        
            break;

        // Register new user

        case 'register_user':

            $register_username = $data['register_username'];
    
            $register_email = $data['register_email'];
    
            $register_password = $data['register_password'];
    
            $register_policy_acceptation = $data['register_policy_acceptation'];
    
            $login->registerUser($register_username, $register_email, $register_password, $register_policy_acceptation);
        
            break;

        // Resend account activation link

        case 'resend_activation':

            $input_data = $data['resend_activation'];
        
            $login->resendActivationLink($input_data);
        
            break;

        // Verification of registered email

        case 'verify_user':

            $user_id = $data['user_id'];

            $auth_reg = $data['auth_reg'];

            $login->activateRegisteredUser($user_id, $auth_reg);
        
            break;

        // METHOD - PHONE

        case 'generate_OTP':

            $phone_number = $data['phone_number'];

            $policy_acceptation = $data['policy_acceptation'];

            $verification_code = $data['verification_code'];

            $login->generateOTP($phone_number, $policy_acceptation, $verification_code);
        
            break;

        case 'verify_OTP':

            $phone_number = $data['phone_number'];

            $one_time_password = $data['one_time_password'];

            $verification_code = $data['verification_code'];

            $login->verifyOTP($phone_number, $one_time_password, $verification_code);
        
            break;

    }

}