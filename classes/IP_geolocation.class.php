<?php


class IP_geolocation {
        private $errStr = "";
        private $context = "";
        private $request_result = "";
        private $ip = "";
        private $countryCode, $countryName, $regionCode, $regionName, $city, $zipcode, $latitude, $longitude;
        private $url_to_call = "http://freegeoip.net/json/";


        public function __construct($ip,$timeout=3) {
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $this->errStr = "Invalid IP address";
                return false;
            }
            $this->IP = $ip;
           $this->request_result =  $this->go($ip,$timeout);
        }



        private function go ($ip,$timeout) {
            $context = stream_context_create(array(
                                                'http' => array(
                                                    'method' => 'GET',
                                                    'timeout' => $timeout,
                                                )
                                            ));
            if (!($source = file_get_contents($this->url_to_call . $ip, 0, $context))) {
                return false;
            };
            //CONVERT JSON TO ARRAY
            $source = json_decode($source, true);
            if (is_null($source)) {
                //CONVERSION FAILED
                return false;
            }

            //EXTRACT INFORMATION AND PUT THEM IN VARIABLES...
            $this->countryCode = $source["country_code"];
            $this->countryName = $source["country_name"];
            $this->regionCode = $source["region_code"];
            $this->regionName = $source["region_name"];
            $this->city = $source["city"];
            $this->zipcode = $source["zipcode"];
            $this->latitude = $source["latitude"];
            $this->longitude = $source["longitude"];



            return true;
        } //END GO METHOD

        public function getRequestResult() {
            return $this->request_result;
         }

        public function getIP() {
            $this->ip;
        }
        public function getCountryCode () {
            return $this->countryCode;
        }

        public function getCountryName () {
            return $this->countryName;
        }

        public function getRegionCode () {
            return $this->regionCode;
        }

        public function getRegionName () {
            return $this->regionName;
        }

        public function getCity () {
            return $this->city;
        }

        public function getZipcode () {
            return $this->zipcode;
        }

        public function getLatitude () {
            return $this->latitude;
        }

        public function getLongitude () {
            return $this->longitude;
        }



}  //END OF IP GEOLOCATION CLASS