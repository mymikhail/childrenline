<?php


interface IProcess
{
  public function onProcess($data);
}


abstract class Process 
{
	const LIMIT = 1;

	public function execute()
	{
		$this->log('Приступим...');
		
		$this->init();
		
		$this->process();
			
		$this->log('конец...');		
	}

	abstract protected function init();
	
	abstract protected function process();

	public function log()
	{
		$args = func_get_args();

		foreach ($args as & $arg)
		{
			echo date('H:i:s'), ' ', $arg, "\n";
		}
	}
}


abstract class ProcessUnLimit extends Process
{
	
	abstract protected function getData();

	protected function process()
	{
		$str = '';
		$i = 0;
		
		while (($data = $this->getData()) !== false)
		{

			$str .= $data;

			if (++$i >= self::LIMIT) 
			{
				$this->onProcess($str);
				$i = 0; 
				$str = '';
				//break;
			}
		}
	}
}



class Parser extends ProcessUnLimit implements IProcess
{		
	
	protected $fl;

	public function __construct($file)
	{
		$this->file = $file;
		$this->str = array();		
	}

	public function __destruct()
	{
		@fclose($this->fl);
	}
		
	protected function init()
	{
		$this->fl = fopen($this->file, "r");
	}
	
	protected function getData()
	{
		return fgets($this->fl, 2048);
	}

	public function onProcess($data)
	{
		//preg_match_all("~\[([^]]*?)\](.*?)-=([0-9]*)=-~i", $data, $m, PREG_SET_ORDER);
		//preg_match("~\[([^]]*?)\](.*?)-=([0-9]*)=-~i", $data, $m);

		$temp = array();
		$temp = explode('', $data); 		

		if (!empty($temp)) 
		{
			$this->str[] = array(
				'author'	=>	$temp[0],
				'title'		=>	$temp[2],
				'external_id'	=> $temp[5],
				'format' => $temp[9],
				'date' => $temp[10],
				'lan' => $temp[11],				
			);
		}
	}
	
	public function getStr()
	{
		return $this->str;
	}
	
}


$dir = '/home/mmytarev/scripts/Librusec/librusec_local_fb2.inpx_FILES/';

try
{
	$time1 = microtime(true);
	$sum = array();

	foreach (glob($dir."*.inp") as $filename) 
	{
		$r = '';
	    echo "$filename size " . filesize($filename) . "\n";
	
		$parser = new Parser($filename);
		$parser->execute();

		$r = $parser->getStr();

		$parser->__destruct();
	
		$sum[] = count($r);
	}

	$time2 = microtime(true);
	print_r($sum);
	echo array_sum($sum);
	$parser->log('time: '. ($time2-$time1));
	
} 
catch (Exception $e)
{
	$parser->log('Caught exception: '. $e->getMessage());
}


