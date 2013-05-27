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


abstract class ProcessLimit extends Process
{

	abstract protected function getAmount();
	abstract protected function getValues($limit, $offset);

	protected function process()
	{
		$amount = $this->getAmount();		
		
		for ($i = 1; $i < $amount; $i += self::LIMIT)
		{
			$offset = $i;
			$limit= self::LIMIT;
			
			$values = $this->getValues($limit, $offset);

			foreach ($values as $data)
			{
				$this->onProcess($data);
								
			}			
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
	
	private $fl;

	public function __construct($file)
	{
		$this->file = $file;
		$this->users = array();
		$this->date = array();
	}

	public function __destruct()
	{
		fclose($this->fl);
	}
		
	protected function init()
	{
		$this->fl = fopen($this->file, "r");
	}
	
	protected function getData()
	{
		return fgets($this->fl, 4096);
	}

	public function onProcess($data)
	{
		//preg_match_all("~\[([^]]*?)\](.*?)-=([0-9]*)=-~i", $data, $m, PREG_SET_ORDER);
		preg_match("~\[([^]]*?)\](.*?)-=([0-9]*)=-~i", $data, $m);
		
		if ($m[1] && $m[3]) 
		{
			$date = date("Y-m-d", strtotime($m[1]));
			$user_id = $m[3];

			$this->users[$user_id] = 1;	
			$this->date[$date][$user_id] = 1;
		}
	}
	
	public function getUsers()
	{
		return $this->users;
	}

	public function getDate()
	{
		return $this->date;
	}

	
}


try
{
	$time1 = microtime(true);

	$parser = new Parser('result.log');
	$parser->execute();

	$r = $parser->getDate();
	ksort($r); 

	foreach ($r as $data => $users)
	{
		echo $data.','.count($users)."\n";
	}

	echo 'users = '.count($parser->getUsers());

	$time2 = microtime(true);
	
	$parser->log('time: '. ($time2-$time1));
	
} 
catch (Exception $e)
{
	$parser->log('Caught exception: '. $e->getMessage());
}


