<?php

class cPanelAPI
{

	private $username;
	private $key;
	private $http;
	private $hostname;
	public $apiversion = "1";
	private $port;

	public function __construct($username, $key, $http, $hostname, $port)
	{
		$this->username = $username;
		$this->key = $key;
		$this->http = $http;
		$this->hostname = $hostname;
		$this->port = $port;
	}

	public function createacct($param)
	{
		return $this->apiQuery("createacct", $param);
	}

	public function setacls($param)
	{
		return $this->apiQuery("setacls", $param);
	}

	public function changepackage($param)
	{
		return $this->apiQuery("changepackage", $param);
	}

	public function modifyacct($username, $param)
	{
		$param['user'] = $username;
		return $this->apiQuery("modifyacct", $param);
	}

	public function listips()
	{
		return $this->apiQuery("listips", array());
	}

	public function setsiteip($param)
	{
		return $this->apiQuery("setsiteip", $param);
	}

	public function passwd($username, $pass)
	{
		return $this->apiQuery("passwd", array('user' => $username, 'password' => $pass));
	}

	public function suspendacct($param)
	{
		return $this->apiQuery("suspendacct", $param);
	}

	public function unsuspendacct($param)
	{
		return $this->apiQuery("unsuspendacct", $param);
	}

	public function removeacct($param)
	{
		return $this->apiQuery("removeacct", $param);
	}

	public function suspendreseller($param)
	{
		return $this->apiQuery("suspendreseller", $param);
	}

	public function unsuspendreseller($param)
	{
		return $this->apiQuery("unsuspendreseller", $param);
	}

	public function terminatereseller($param)
	{
		return $this->apiQuery("terminatereseller", $param);
	}

	public function showbw($params = NULL)
	{
		return $this->apiQuery("showbw", $params);
	}

	public function getResellerAccessKey($params = NULL)
	{
		$query = $this->apiQuery("get_remote_access_hash", $params);

		if (isset($query->data->accesshash) && !empty($query->data->accesshash)) {
			return $query->data->accesshash;
		} else {
			return FALSE;
		}
	}

	public function listaccts($searchtype = NULL, $search = NULL)
	{
		$searchArray = array();
		if (!empty($searchtype) && !empty($search)) {
			$searchArray["searchtype"] = $searchtype;
			$searchArray["search"] = $search;
		}
		$query = $this->apiQuery("listaccts", $searchArray);
		if (!empty($query->data->acct)) {
			return $query->data->acct;
		} else {
			return FALSE;
		}
	}

	public function accountsummary($user)
	{

		$query = $this->apiQuery("accountsummary", array("user" => "{$user}"));
		if (!empty($query->data->acct)) {
			return $query->data->acct;
		} else {
			return FALSE;
		}
	}

	public function resellerstats($user)
	{

		$query = $this->apiQuery("resellerstats", array("user" => "{$user}"));
		if (!empty($query->data->reseller)) {
			return $query->data->reseller;
		} else {
			return FALSE;
		}
	}

	public function systemloadavg()
	{

		$query = $this->apiQuery("systemloadavg", array());
		if (!empty($query->data)) {
			return $query->data;
		} else {
			return FALSE;
		}
	}

	public function serverloadavg()
	{

		$query = $this->apiQuery("loadavg", array());
		if (!empty($query)) {
			return $query;
		} else {
			return FALSE;
		}
	}

	public function getdiskusage()
	{

		$query = $this->apiQuery("getdiskusage", array());
		if (!empty($query->data->partition)) {
			return $query->data->partition;
		} else {
			return FALSE;
		}
	}

	public function listacls()
	{

		$query = $this->apiQuery("listacls", array());
		if (!empty($query->data->acl)) {
			return $query->data->acl;
		} else {
			return FALSE;
		}
	}

	public function listpkgs()
	{

		$query = $this->apiQuery("listpkgs", array());
		if (!empty($query->data->pkg)) {
			return $query->data->pkg;
		} else {
			return FALSE;
		}
	}

	public function getpkginfo($params)
	{

		$query = $this->apiQuery("getpkginfo", $params);
		if (!empty($query->data->pkg)) {
			return $query->data->pkg;
		} else {
			return FALSE;
		}
	}

	public function killpkg(array $param)
	{
		return $this->apiQuery("killpkg", $param);
	}

	public function addpkg(array $param)
	{
		return $this->apiQuery("addpkg", $param);
	}

	public function editpkg(array $param)
	{
		return $this->apiQuery("editpkg", $param);
	}

	public function reboot($force)
	{

		if ($force = "forceful") {
			$type = "1";
		} else if ($force = "graceful") {
			$type = "0";
		}
		$query = $this->apiQuery("reboot", array("force " => "{$type}"));
		if ($query->metadata->result == TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function servicestatus()
	{

		$query = $this->apiQuery("servicestatus", array());
		if (!empty($query->data->service)) {
			return $query->data->service;
		} else {
			return FALSE;
		}
	}

	public function remoteTransfer($param)
	{
		if (!isset($param['host']) || !isset($param['password'])) {
			error_log("Username & Password information are required to transfer account.");
			return false;
		} else {

			$param['unrestricted_restore'] = "1";
			$param['permit_ftp_fallback'] = "1";
			return $this->apiQuery("create_remote_user_transfer_session", $param);
		}
	}

	public function remoteTransferStatus($param)
	{
		if (!isset($param['transfer_session_id'])) {
			error_log("Session ID information are required to check status.");
			return false;
		} else {

			return $this->apiQuery("get_transfer_session_state", $param);
		}
	}

	public function firewallQuery($params = array())
	{

		$params = "?" . http_build_query($params);
		$query = "$this->http://{$this->hostname}:{$this->port}/cgi/configserver/csf.cgi{$params}";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$header[0] = "Authorization: WHM $this->username:" . preg_replace("'(\r|\n)'", "", $this->key);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_URL, $query);
		$result = curl_exec($curl);
		curl_close($curl);
		if ($result == false) {
			error_log("curl_exec threw error \"" . curl_error($curl) . "\" for $query");
			return false;
		} else {
			return json_decode($result);
		}
	}

	public function apiQuery($function, $params = array())
	{
		$params = "?" . "api.version={$this->apiversion}&" . http_build_query($params);
		$query = "$this->http://{$this->hostname}:{$this->port}/json-api/{$function}{$params}";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$header[0] = "Authorization: WHM $this->username:" . preg_replace("'(\r|\n)'", "", $this->key);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_URL, $query);
		$result = curl_exec($curl);
		curl_close($curl);
		if ($result == false) {
			error_log("curl_exec threw error \"" . curl_error($curl) . "\" for $query");
			return false;
		} else {
			return json_decode($result);
		}
	}

	public function api1_query($user, $module, $function, $params = array())
	{

		$argcount = "";
		foreach ($params as $key => $value) {
			$params['arg-' . $argcount] = $value;
			$argcount++;
		}
		$params = http_build_query($params);

		$query = "{$this->http}://{$this->hostname}:{$this->port}/json-api/cpanel/?user={$user}&cpanel_jsonapi_module={$module}&cpanel_jsonapi_func={$function}&cpanel_jsonapi_apiversion=1&{$params}";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$header[0] = "Authorization: WHM $this->username:" . preg_replace("'(\r|\n)'", "", $this->key);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_URL, $query);
		$result = curl_exec($curl);
		curl_close($curl);
		if ($result == false) {
			return false;
		} else {
			return json_decode($result);
		}
	}

	public function api2_query($user, $module, $function, $params = array())
	{

		$params = http_build_query($params);
		$query = "{$this->http}://{$this->hostname}:{$this->port}/json-api/cpanel/?user={$user}&cpanel_jsonapi_module={$module}&cpanel_jsonapi_func={$function}&cpanel_jsonapi_version=2&{$params}";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$header[0] = "Authorization: WHM $this->username:" . preg_replace("'(\r|\n)'", "", $this->key);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_URL, $query);
		$result = curl_exec($curl);
		curl_close($curl);
		if ($result == false) {
			return false;
		} else {
			return json_decode($result);
		}
	}
}
