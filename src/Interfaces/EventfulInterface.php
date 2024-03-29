<?php

namespace Sandeepchowdary7\Laraeventful\Interfaces;

interface EventfulInterface
{
    public function getCityEvents($cityName);
    public function makeRequest($url, $params = array(), $req_method = self::GET);
	public function getRequestInfo();
	public function getErrorMessage();
    public function getErrorCode();
	public function _parseError($res);
}