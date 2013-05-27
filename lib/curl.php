`
class CURL
{
  private $user_agent = 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.1 (KHTML, like Gecko) Ubuntu/10.10 Chromium/14.0.835.202 Chrome/14.0.835.202 Safari/535.1';

	private $curl;

	private $opts = array(
		CURLOPT_HEADER          => 1,   //показывать заголовки страницы
		CURLINFO_HEADER_OUT     => 1,
		CURLOPT_RETURNTRANSFER  => 1,
		CURLOPT_FOLLOWLOCATION  => 1,
		CURLOPT_NOBODY          => 0,   //показывать страницу
//      CURLOPT_COOKIEFILE      => 'cookies_file_path',
//      CURLOPT_COOKIEJAR       => 'cookies_file_path',
	);

	public function __construct($opts = array())
	{
		$this->curl = curl_init();
		if (!empty($opts))
			$this->opts = $opts;
	}

	public function __destruct()
	{
		curl_close($this->curl);
	}

	public function setIp($ip)
	{
		$this->setOpt(array(CURLOPT_INTERFACE => $this->ip));
	}

	public function setUserAgent($user_agent = '')
	{
		if ($user_agent)
			$this->user_agent = $user_agent;

		$this->setOpt(array(CURLOPT_USERAGENT => $this->user_agent));
	}

	public function setUrl($url)
	{
		$this->setOpt(array(CURLOPT_URL => $url));
	}

	public function setReferer($referer)
	{
		$this->setOpt(array(CURLOPT_REFERER => $referer));
	}

	private function setOpt($params = array())
	{
		if (!empty($params))
		{
			foreach ($params as $k => $v)
			{
				curl_setopt($this->curl, $k, $v);
			}
		}
	}

	public function exec()
	{
		$this->setOpt($this->opts);
		$response = curl_exec($this->curl);

		if ($response !== false)
		{
			$header_size = 0;
			$header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);

			$result['header'] = substr($response, 0, $header_size);
			$result['body'] = substr( $response, $header_size );
		}
		else
			print_r(curl_errno($this->curl));

		return $result;
	}
}

try
{
	if (strpos($url, "www") !== false)
	{
		$referrer = preg_replace("~\\/[\\w]+$~i", "", $url);

		$opts = array(
			CURLOPT_HEADER  => 1,
			CURLOPT_NOBODY  => 0,
			CURLOPT_RETURNTRANSFER  => 1,
			CURLOPT_FOLLOWLOCATION  => 1,
			CURLOPT_CONNECTTIMEOUT  => 8,
		);

		$curl = new CURL($opts);
		$curl->setUrl($url);
		$curl->setUserAgent();
		$curl->setReferer($referrer);

		$i = 0;

		while (!$result = $curl->exec())
		{
			if( ++$i == 3)
				throw new Exception("Not file!!!");
		}

		if ($result['header'])
		{
			$headers = array(
				'HTTP/1.1' => '',
				'Content-Type' => '',
				'Content-Length' => '',
				'Location'  => '',
				'Content-Disposition' => ''
			);

			$temp = explode("\n", $result['header']);

			foreach ($temp as $value)
			{
				$data = explode(" ", $value, 2);
				foreach ($headers as $header => $v)
				{
					if (strpos($data[0], $header) !== false)
					{
						$headers[$header] = trim($data[1]);
					}
				}
			}

			if ($headers['HTTP/1.1'] != '200 OK' ||  $headers['Content-Length'] > $max)
				throw new Exception("Error page!!!");
		}

		if ($result['body'])
		{
			if ($headers["Content-Disposition"] && preg_match("~filename\\=([^;]+)~i", $headers["Content-Disposition"], $m))
				$filename = $m[1];
			else
			{
				$location = explode("/",$headers['Location']);
				$filename = array_pop($location);
			}

			#header('HTTP/1.1 200 OK');
			header("Content-Type: " . $headers["Content-Type"]);
			header("Content-Length: " . $headers["Content-Length"]);
			header("Content-Disposition: attachment; filename=$filename");
			header("Connection: close");
			echo $result['body'];
			exit();
		}
		else
			throw new Exception("Not file!!!");

	}
	else
		throw new Exception("Not www.net!!!");

} catch (Exception $e) {
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $url);
	exit;
}
`
